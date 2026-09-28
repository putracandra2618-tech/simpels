<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\ReturnRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminController extends Controller
{
    public function verifikasi(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate([
            'keputusan' => ['required', 'in:disetujui,ditolak'],
            'condition' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $borrowing = $returnRequest->borrowing;

        if ($validated['keputusan'] === 'disetujui') {
            DB::transaction(function () use ($validated, $returnRequest, $borrowing) {
                $returnRequest->update([
                    'status' => 'disetujui',
                    'condition' => $validated['condition'] ?? null,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'responded_at' => now(),
                ]);

                $borrowing->update([
                    'status' => 'dikembalikan',
                    'returned_at' => now(),
                    'condition' => $validated['condition'] ?? null,
                ]);

                $borrowing->laptop->update(['status' => 'tersedia']);
            });

            $this->notifyStudents(
                $borrowing,
                title: 'Pengembalian Berhasil',
                message: "Laptop \"{$borrowing->laptop->nama}\" telah dikembalikan dan diverifikasi Admin.",
            );

            return back()->with('success', 'Pengembalian disetujui. Laptop kembali Tersedia.');
        }

        $returnRequest->update([
            'status' => 'ditolak',
            'admin_notes' => $validated['admin_notes'] ?? null,
            'responded_at' => now(),
        ]);

        $borrowing->update(['status' => 'aktif']);

        $this->notifyStudents(
            $borrowing,
            title: 'Pengembalian Ditolak',
            message: "Pengembalian laptop \"{$borrowing->laptop->nama}\" ditolak: ".($validated['admin_notes'] ?? 'Silakan hubungi Admin.'),
        );

        return back()->with('success', 'Pengembalian ditolak. Siswa dapat mengajukan kembali.');
    }

    public function verifikasiIndex(): View
    {
        $returnRequests = ReturnRequest::where('status', 'menunggu')
            ->with(['borrowing.laptop', 'borrowing.students'])
            ->latest('requested_at')
            ->get();

        $menungguVerifikasi = $returnRequests->count();

        return view('admin.verifikasi', compact('returnRequests', 'menungguVerifikasi'));
    }

    public function laptopIndex(): View
    {
        $laptops = Laptop::with('borrowings.students')
            ->latest()
            ->get();

        return view('admin.laptop', compact('laptops'));
    }

    public function laptopCreate(): View
    {
        return view('admin.laptop-form', ['laptop' => null]);
    }

    public function laptopStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'merek' => ['nullable', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string', 'max:2000'],
        ]);

        $token = Str::uuid()->toString();

        $laptop = Laptop::create($validated + ['qr_token' => $token, 'status' => 'tersedia']);

        $this->generateQrImage($token);

        return redirect()->route('admin.laptop.index')
            ->with('success', "Laptop \"{$laptop->nama}\" ditambahkan dan QR Code dibuat.");
    }

    public function laptopEdit(Laptop $laptop): View
    {
        return view('admin.laptop-form', compact('laptop'));
    }

    public function laptopUpdate(Request $request, Laptop $laptop): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'merek' => ['nullable', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string', 'max:2000'],
        ]);

        $laptop->update($validated);

        $this->generateQrImage($laptop->qr_token);

        return redirect()->route('admin.laptop.index')
            ->with('success', "Data laptop \"{$laptop->nama}\" diperbarui.");
    }

    public function laptopDestroy(Laptop $laptop): RedirectResponse
    {
        $name = $laptop->nama;

        $laptop->delete();
        $this->deleteQrImage($laptop->qr_token);

        return redirect()->route('admin.laptop.index')
            ->with('success', "Laptop \"{$name}\" dihapus.");
    }

    public function laptopQr(Laptop $laptop): View
    {
        return view('admin.qr-print', ['laptops' => collect([$laptop])]);
    }

    public function laptopPrint(Request $request): View|RedirectResponse
    {
        $raw = $request->query('ids');

        $ids = is_array($raw)
            ? collect($raw)
            : collect(explode(',', (string) $raw));

        $ids = $ids
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('admin.laptop.index')
                ->with('error', 'Pilih minimal satu laptop untuk dicetak.');
        }

        $laptops = $ids
            ->map(fn ($id) => Laptop::find($id))
            ->filter()
            ->values();

        if ($laptops->isEmpty()) {
            return redirect()->route('admin.laptop.index')
                ->with('error', 'Laptop yang dipilih tidak ditemukan.');
        }

        return view('admin.qr-print', ['laptops' => $laptops]);
    }

    public function userIndex(Request $request): View
    {
        $scope = $this->userScope($request);
        $query = User::query();

        if ($scope === 'admin') {
            $query->whereIn('role', ['admin', 'superadmin']);
        } else {
            $query->where('role', 'siswa');
        }

        $users = $query
            ->withCount(['borrowings as active_borrowings_count' => fn ($query) => $query->whereIn('status', ['aktif', 'menunggu'])])
            ->latest()
            ->get();

        return view('admin.user', compact('users', 'scope'));
    }

    public function userCreate(Request $request): View
    {
        $scope = $this->userScope($request);

        return view('admin.user-form', ['user' => null, 'scope' => $scope]);
    }

    public function userStore(Request $request): RedirectResponse
    {
        $scope = $this->userScope($request);
        $validated = $this->validateUser($request, null, $scope);

        if ($scope === 'admin' && ! $this->canAssignRole($request->user(), $validated['role'])) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Hanya Super Admin yang dapat membuat akun Super Admin.']);
        }

        $user = User::create($validated);

        return redirect()->route($this->userIndexRoute($scope))
            ->with('success', "Akun \"{$user->name}\" ({$user->username}) berhasil dibuat.");
    }

    public function userEdit(Request $request, User $user): View
    {
        $scope = $this->userScope($request);

        abort_if(! $this->userMatchesScope($user, $scope), 404);

        return view('admin.user-form', compact('user', 'scope'));
    }

    public function userUpdate(Request $request, User $user): RedirectResponse
    {
        $scope = $this->userScope($request);

        if (! $this->userMatchesScope($user, $scope)) {
            return redirect()->route($this->userIndexRoute($scope))
                ->with('error', 'Akun tidak sesuai dengan halaman ini.');
        }

        $validated = $this->validateUser($request, $user, $scope);

        if ($scope === 'admin' && $user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Hanya Super Admin yang dapat mengelola akun Super Admin.']);
        }

        if ($scope === 'admin' && ! $this->canAssignRole($request->user(), $validated['role'])) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Hanya Super Admin yang dapat membuat akun Super Admin.']);
        }

        if ($user->is($request->user()) && $validated['role'] !== $user->role) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Anda tidak dapat mengubah role akun sendiri.']);
        }

        $data = $validated;

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route($this->userIndexRoute($scope))
            ->with('success', "Akun \"{$user->name}\" diperbarui.");
    }

    public function userDestroy(Request $request, User $user): RedirectResponse
    {
        $scope = $this->userScope($request);
        $name = $user->name;

        if (! $this->userMatchesScope($user, $scope)) {
            return redirect()->route($this->userIndexRoute($scope))
                ->with('error', 'Akun tidak sesuai dengan halaman ini.');
        }

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            return back()->with('error', 'Hanya Super Admin yang dapat menghapus akun Super Admin.');
        }

        if ($scope === 'admin' && $user->isAdmin() && $this->privilegedUserCount() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun Admin/Super Admin.');
        }

        $user->delete();

        return redirect()->route($this->userIndexRoute($scope))
            ->with('success', "Akun \"{$name}\" dihapus.");
    }

    private function userScope(Request $request): string
    {
        return $request->route('scope', 'siswa') === 'admin' ? 'admin' : 'siswa';
    }

    private function userIndexRoute(string $scope): string
    {
        return $scope === 'admin' ? 'admin.adminuser.index' : 'admin.user.index';
    }

    private function userMatchesScope(User $user, string $scope): bool
    {
        return $scope === 'admin' ? $user->isAdmin() : $user->isSiswa();
    }

    private function validateUser(Request $request, ?User $user, string $scope): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'password' => $user === null
                ? ['required', 'string', 'min:8', 'confirmed']
                : ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($scope === 'admin') {
            $rules['role'] = ['required', Rule::in(['admin', 'superadmin'])];
            $rules['kelas'] = ['nullable', 'string', 'max:255'];
            $rules['jurusan'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules['role'] = ['required', Rule::in(['siswa'])];
            $rules['kelas'] = ['required', Rule::in(['X', 'XI', 'XII'])];
            $rules['jurusan'] = ['required', Rule::in(['TKJ', 'RPL', 'TEI', 'TPSB', 'TB', 'TKR', 'TP'])];
        }

        return $request->validate($rules);
    }

    private function canAssignRole(User $actor, string $role): bool
    {
        return $role !== 'superadmin' || $actor->isSuperAdmin();
    }

    private function privilegedUserCount(): int
    {
        return User::whereIn('role', ['admin', 'superadmin'])->count();
    }

    private function notifyStudents(Borrowing $borrowing, string $title, string $message): void
    {
        foreach ($borrowing->students as $student) {
            $student->notify(new ReturnRequestNotification(
                title: $title,
                message: $message,
                url: route('kembali.index'),
            ));
        }
    }

    private function qrDirectory(): string
    {
        return storage_path('app/public/qrcodes');
    }

    private function generateQrImage(string $token): void
    {
        $directory = $this->qrDirectory();

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        QrCode::format('png')
            ->size(512)
            ->margin(1)
            ->generate($token, $directory.DIRECTORY_SEPARATOR.$token.'.png');
    }

    private function deleteQrImage(string $token): void
    {
        $file = $this->qrDirectory().DIRECTORY_SEPARATOR.$token.'.png';

        if (is_file($file)) {
            unlink($file);
        }
    }
}
