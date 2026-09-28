<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSiswa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isSiswa()) {
            abort(403, 'Hanya siswa yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
