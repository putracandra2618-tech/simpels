<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->siswaDashboard($user);
    }

    private function adminDashboard(): View
    {
        $totalLaptops = Laptop::count();
        $laptopsTersedia = Laptop::where('status', 'tersedia')->count();
        $laptopsDipinjam = Laptop::where('status', 'dipinjam')->count();
        $menungguVerifikasi = ReturnRequest::where('status', 'menunggu')->count();

        $returnRequests = ReturnRequest::where('status', 'menunggu')
            ->with(['borrowing.laptop', 'borrowing.students'])
            ->latest('requested_at')
            ->get();

        $recentBorrowings = Borrowing::with(['laptop', 'students'])
            ->latest('borrowed_at')
            ->take(8)
            ->get();

        return view('dashboard.admin', compact(
            'totalLaptops',
            'laptopsTersedia',
            'laptopsDipinjam',
            'menungguVerifikasi',
            'returnRequests',
            'recentBorrowings',
        ));
    }

    private function siswaDashboard($user): View
    {
        $myBorrowings = Borrowing::whereHas('students', fn ($q) => $q->whereKey($user->id))
            ->with(['laptop', 'students'])
            ->latest('borrowed_at')
            ->get();

        $sessionsAktif = $myBorrowings->filter(fn ($b) => in_array($b->status, ['aktif', 'menunggu'], true));
        $sessionsSelesai = $myBorrowings->filter(fn ($b) => $b->status === 'dikembalikan');

        return view('dashboard.siswa', compact('myBorrowings', 'sessionsAktif', 'sessionsSelesai'));
    }

    public function markNotificationsRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }
}
