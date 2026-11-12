<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param array<int, string> $roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Memeriksa apakah pengguna sudah login
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Mengambil nama role pengguna
        $namaRole = $request->user()->role?->nama_role;

        // Memeriksa apakah role pengguna diizinkan
        if (!$namaRole || !in_array($namaRole, $roles)) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses.',
            ], 403);
        }

        return $next($request);
    }
}
