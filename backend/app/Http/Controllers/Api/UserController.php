<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'pegawai'])
            ->orderBy('id_user', 'desc')
            ->get();

        return response()->json([
            'message' => 'Data akun pengguna berhasil diambil.',
            'data' => $users,
        ], 200);
    }

    public function show(int $id)
    {
        $user = User::with(['role', 'pegawai'])->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Data akun pengguna tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail akun pengguna berhasil diambil.',
            'data' => $user,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => [
                'required',
                'exists:roles,id_role',
                Rule::in(
                    Role::whereIn('nama_role', ['Pegawai', 'Kepala Balai'])
                        ->pluck('id_role')->toArray()
                ),
            ],
            'pegawai_id' => [
                'nullable',
                'required_if:role_id,' . Role::where('nama_role', 'Pegawai')->value('id_role'),
                'exists:pegawais,id_pegawai',
                Rule::unique('users', 'pegawai_id'),
            ],
            'username' => [
                'required', 'string', 'max:100', 'unique:users,username',
            ],
            'password' => [
                'required', 'string', 'min:8',
            ],
        ], [
            'role_id.required' => 'Role akun wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            'pegawai_id.required_if' => 'Akun Pegawai wajib dihubungkan dengan data pegawai.',
            'pegawai_id.exists' => 'Data pegawai tidak ditemukan.',
            'pegawai_id.unique' => 'Pegawai tersebut sudah memiliki akun.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
        ]);

        $role = Role::findOrFail($request->role_id);

        if ($role->nama_role === 'Kepala Balai' && $request->filled('pegawai_id')) {
            return response()->json([
                'message' => 'Akun Kepala Balai tidak perlu dihubungkan dengan data pegawai.',
            ], 422);
        }

        if ($role->nama_role === 'Pegawai') {
            $pegawai = Pegawai::findOrFail($request->pegawai_id);

            if ($pegawai->status_pegawai !== 'Aktif') {
                return response()->json([
                    'message' => 'Pegawai yang dipilih tidak aktif.',
                ], 422);
            }
        }

        $user = DB::transaction(function () use ($request) {
            return User::create([
                'pegawai_id' => $request->pegawai_id,
                'role_id' => $request->role_id,
                'username' => $request->username,
                'password' => $request->password,
                'status_akun' => 'Aktif',
            ]);
        });

        return response()->json([
            'message' => 'Akun pengguna berhasil dibuat.',
            'data' => $user->load(['role', 'pegawai']),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Data akun pengguna tidak ditemukan.',
            ], 404);
        }

        if ($user->role->nama_role === 'Admin') {
            return response()->json([
                'message' => 'Akun Admin tidak dapat diubah melalui fitur ini.',
            ], 403);
        }

        $rolePegawaiId = Role::where('nama_role', 'Pegawai')->value('id_role');
        $roleKepalaBalaiId = Role::where('nama_role', 'Kepala Balai')->value('id_role');

        $request->validate([
            'role_id' => [
                'required',
                'exists:roles,id_role',
                Rule::in([$rolePegawaiId, $roleKepalaBalaiId]),
            ],
            'pegawai_id' => [
                'nullable',
                Rule::requiredIf((int) $request->role_id === (int) $rolePegawaiId),
                'exists:pegawais,id_pegawai',
                Rule::unique('users', 'pegawai_id')->ignore($user->id_user, 'id_user'),
            ],
            'username' => [
                'required', 'string', 'max:100',
                Rule::unique('users', 'username')->ignore($user->id_user, 'id_user'),
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'role_id.required' => 'Role akun wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            'pegawai_id.required' => 'Akun Pegawai wajib dihubungkan dengan data pegawai.',
            'pegawai_id.exists' => 'Data pegawai tidak ditemukan.',
            'pegawai_id.unique' => 'Pegawai tersebut sudah memiliki akun.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
        ]);

        $role = Role::findOrFail($request->role_id);

        if ($role->nama_role === 'Kepala Balai' && $request->filled('pegawai_id')) {
            return response()->json([
                'message' => 'Akun Kepala Balai tidak perlu dihubungkan dengan data pegawai.',
            ], 422);
        }

        if ($role->nama_role === 'Pegawai') {
            $pegawai = Pegawai::findOrFail($request->pegawai_id);

            if ($pegawai->status_pegawai !== 'Aktif') {
                return response()->json([
                    'message' => 'Pegawai yang dipilih tidak aktif.',
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $user, $role) {
            $user->pegawai_id = $role->nama_role === 'Pegawai'
                ? $request->pegawai_id
                : null;

            $user->role_id = $request->role_id;
            $user->username = $request->username;

            if ($request->filled('password')) {
                $user->password = $request->password;
            }

            $user->save();
        });

        return response()->json([
            'message' => 'Akun pengguna berhasil diperbarui.',
            'data' => $user->fresh()->load(['role', 'pegawai']),
        ], 200);
    }

    public function destroy(int $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Data akun pengguna tidak ditemukan.',
            ], 404);
        }

        if ($user->role->nama_role === 'Admin') {
            return response()->json([
                'message' => 'Akun Admin tidak dapat dihapus.',
            ], 403);
        }

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        return response()->json([
            'message' => 'Akun pengguna berhasil dihapus.',
        ], 200);
    }

    public function updateStatus(Request $request, int $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Data akun pengguna tidak ditemukan.',
            ], 404);
        }

        if ($user->role->nama_role === 'Admin') {
            return response()->json([
                'message' => 'Status akun Admin tidak dapat diubah melalui fitur ini.',
            ], 403);
        }

        $request->validate([
            'status_akun' => [
                'required',
                Rule::in(['Aktif', 'Tidak Aktif']),
            ],
        ], [
            'status_akun.required' => 'Status akun wajib dipilih.',
            'status_akun.in' => 'Status akun hanya boleh Aktif atau Tidak Aktif.',
        ]);

        $user->update([
            'status_akun' => $request->status_akun,
        ]);

        return response()->json([
            'message' => 'Status akun pengguna berhasil diperbarui.',
            'data' => $user->fresh()->load(['role', 'pegawai']),
        ], 200);
    }
}
