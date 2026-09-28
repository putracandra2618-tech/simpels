<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\ReturnRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $borrowings = Borrowing::whereHas('students', fn ($q) => $q->whereKey($user->id))
            ->with(['laptop', 'students', 'returnRequests'])
            ->latest('borrowed_at')
            ->get();

        return view('kembali.index', compact('borrowings'));
    }

    public function request(Borrowing $borrowing): RedirectResponse
    {
        $user = Auth::user();

        $isMember = $borrowing->students()->whereKey($user->id)->exists();

        abort_unless($isMember, 403, 'Anda bukan anggota sesi peminjaman ini.');
        abort_unless($borrowing->status === 'aktif', 422, 'Sesi tidak dalam status aktif.');

        $returnRequest = ReturnRequest::create([
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
            'requested_at' => now(),
        ]);

        $borrowing->update(['status' => 'menunggu']);

        $admins = User::whereIn('role', ['admin', 'superadmin'])->get();

        Notification::send($admins, new ReturnRequestNotification(
            title: 'Permintaan Pengembalian',
            message: "{$user->name} mengajukan pengembalian laptop \"{$borrowing->laptop->nama}\" untuk diverifikasi.",
            url: route('admin.verifikasi.index'),
        ));

        return redirect()->route('kembali.index')
            ->with('success', 'Pengembalian dikirim ke Admin. Menunggu verifikasi.');
    }
}
