<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurusanController extends Controller
{
    public function index(Request $request): View
    {
        $editId = $request->integer('edit');

        return view('admin.jurusan', [
            'jurusan' => Jurusan::withCount('users')->orderBy('kode')->get(),
            'editJurusan' => $editId > 0 ? Jurusan::find($editId) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:255', Rule::unique('jurusan', 'kode')],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        Jurusan::create($data);

        return redirect()->route('admin.jurusan.index')
            ->with('success', "Jurusan \"{$data['kode']}\" ditambahkan.");
    }

    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:255', Rule::unique('jurusan', 'kode')->ignore($jurusan->id)],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $jurusan->update($data);

        return redirect()->route('admin.jurusan.index')
            ->with('success', "Jurusan \"{$data['kode']}\" diperbarui.");
    }

    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        $name = $jurusan->kode;

        if ($jurusan->users()->exists()) {
            return back()->with('error', "Jurusan \"{$name}\" tidak dapat dihapus karena masih dipakai siswa.");
        }

        $jurusan->delete();

        return redirect()->route('admin.jurusan.index')
            ->with('success', "Jurusan \"{$name}\" dihapus.");
    }
}
