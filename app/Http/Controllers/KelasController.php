<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $editId = $request->integer('edit');

        return view('admin.kelas', [
            'kelas' => Kelas::withCount('users')->orderBy('nama')->get(),
            'editKelas' => $editId > 0 ? Kelas::find($editId) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $nama = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('kelas', 'nama')],
        ])['nama'];

        Kelas::create(['nama' => $nama]);

        return redirect()->route('admin.kelas.index')
            ->with('success', "Kelas \"{$nama}\" ditambahkan.");
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $nama = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('kelas', 'nama')->ignore($kelas->id)],
        ])['nama'];

        $kelas->update(['nama' => $nama]);

        return redirect()->route('admin.kelas.index')
            ->with('success', "Kelas \"{$nama}\" diperbarui.");
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $name = $kelas->nama;

        if ($kelas->users()->exists()) {
            return back()->with('error', "Kelas \"{$name}\" tidak dapat dihapus karena masih dipakai siswa.");
        }

        $kelas->delete();

        return redirect()->route('admin.kelas.index')
            ->with('success', "Kelas \"{$name}\" dihapus.");
    }
}
