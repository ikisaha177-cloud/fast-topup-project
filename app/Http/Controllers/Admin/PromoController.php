<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoRequest;
use App\Models\PromoCode;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $promos = PromoCode::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.promos.index', compact('promos', 'search'));
    }

    public function create()
    {
        return view('admin.promos.create');
    }

    public function store(PromoRequest $request)
    {
        $promo = PromoCode::create($request->payload());

        ActivityLogger::log('promo.create', 'Menambahkan kode promo "' . $promo->code . '".', $promo);

        return redirect()->route('admin.promos.index')->with('success', 'Kode promo berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $promo = PromoCode::findOrFail($id);

        return view('admin.promos.edit', compact('promo'));
    }

    public function update(PromoRequest $request, $id)
    {
        $promo = PromoCode::findOrFail($id);
        $promo->update($request->payload());

        ActivityLogger::log('promo.update', 'Memperbarui kode promo "' . $promo->code . '".', $promo);

        return redirect()->route('admin.promos.index')->with('success', 'Kode promo berhasil diperbarui.');
    }

    /**
     * Aktif / nonaktifkan kode promo.
     */
    public function toggle($id)
    {
        $promo = PromoCode::findOrFail($id);
        $promo->is_active = ! $promo->is_active;
        $promo->save();

        ActivityLogger::log(
            'promo.toggle',
            'Mengubah status kode promo "' . $promo->code . '" menjadi ' . ($promo->is_active ? 'aktif' : 'nonaktif') . '.',
            $promo
        );

        return back()->with('success', 'Status kode promo diperbarui.');
    }

    public function destroy($id)
    {
        $promo = PromoCode::findOrFail($id);

        // Kode yang pernah dipakai jadi jejak pembelian: jangan dihapus, nonaktifkan saja.
        if ($promo->used_count > 0) {
            return back()->with('error', 'Kode promo "' . $promo->code . '" sudah terpakai dan tidak boleh dihapus. Nonaktifkan saja agar riwayat tetap utuh.');
        }

        $promo->delete();

        ActivityLogger::log('promo.delete', 'Menghapus kode promo "' . $promo->code . '".', $promo);

        return redirect()->route('admin.promos.index')->with('success', 'Kode promo berhasil dihapus.');
    }
}
