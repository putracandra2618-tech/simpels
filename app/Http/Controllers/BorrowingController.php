<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\BorrowingStudent;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    public const MAX_STUDENTS = 5;

    public function scan(): View
    {
        return view('pinjam.scan');
    }

    public function validasi(Request $request): RedirectResponse
    {
        $qrToken = trim((string) $request->input('qr_token'));

        if ($qrToken === '') {
            return redirect()->route('pinjam.scan')->with('error', 'QR Tidak Terbaca. Coba scan ulang.');
        }

        $laptop = Laptop::where('qr_token', $qrToken)->first();

        if ($laptop === null) {
            return redirect()->route('pinjam.scan')->with('error', 'QR Tidak Terbaca. QR tidak terdaftar.');
        }

        if ($laptop->activeBorrowing() !== null) {
            return redirect()->route('pinjam.scan')->with('error', "Laptop \"{$laptop->nama}\" sedang dipinjam.");
        }

        return redirect()->route('pinjam.konfirmasi', $laptop);
    }

    public function konfirmasi(Laptop $laptop): View
    {
        $user = Auth::user();

        $activeStudentIds = BorrowingStudent::whereIn('borrowing_id', function ($query) {
            $query->select('id')
                ->from('borrowings')
                ->whereIn('status', ['aktif', 'menunggu']);
        })->pluck('user_id');

        $students = User::where('role', 'siswa')
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', $activeStudentIds)
            ->orderBy('username')
            ->get();

        return view('pinjam.konfirmasi', compact('laptop', 'students', 'user'));
    }

    public function store(Request $request, Laptop $laptop): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_STUDENTS],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('role', 'siswa')],
        ]);

        $memberIds = collect($validated['member_ids'])->map(fn ($id) => (int) $id);

        if (! $memberIds->contains($user->id)) {
            $memberIds->push($user->id);
        }

        if ($memberIds->count() > self::MAX_STUDENTS) {
            return back()->with('error', 'Maksimal '.self::MAX_STUDENTS.' siswa dalam satu sesi peminjaman.');
        }

        $memberIds = $memberIds->unique();

        $students = User::whereIn('id', $memberIds)->where('role', 'siswa')->get();
        $students = $students->concat([$user])->unique('id');

        $result = DB::transaction(function () use ($laptop, $students, $user): array {
            $lockedLaptop = Laptop::query()->whereKey($laptop->id)->lockForUpdate()->firstOrFail();

            if ($lockedLaptop->activeBorrowing() !== null) {
                return ['error' => "Laptop \"{$lockedLaptop->nama}\" sudah dipinjam sesi lain."];
            }

            $busyStudent = $students->first(function (User $student) {
                return $student->borrowings()
                    ->whereIn('status', ['aktif', 'menunggu'])
                    ->exists();
            });

            if ($busyStudent !== null) {
                return ['error' => "{$busyStudent->name} sudah terdaftar di sesi peminjaman lain."];
            }

            $borrowing = Borrowing::create([
                'laptop_id' => $lockedLaptop->id,
                'created_by' => $user->id,
                'borrowed_at' => now(),
                'status' => 'aktif',
            ]);

            $borrowing->students()->attach($students->pluck('id'));

            $lockedLaptop->update(['status' => 'dipinjam']);

            return ['borrowing' => $borrowing];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('pinjam.bukti', $result['borrowing'])
            ->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function bukti(Borrowing $borrowing): View
    {
        $borrowing->load(['laptop', 'students', 'leader']);

        $isMember = $borrowing->students->contains(fn ($student) => $student->id === Auth::id());

        abort_if($borrowing->status === 'aktif' && ! $isMember, 403);

        return view('pinjam.bukti', compact('borrowing'));
    }
}
