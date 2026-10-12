<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarketplaceProductRequest;
use App\Models\MarketplaceProduct;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class MarketplaceProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $products = MarketplaceProduct::with('seller')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.marketplace.index', compact('products', 'search'));
    }

    public function edit($id)
    {
        $product = MarketplaceProduct::findOrFail($id);

        return view('admin.marketplace.edit', compact('product'));
    }

    public function update(MarketplaceProductRequest $request, $id)
    {
        $product = MarketplaceProduct::findOrFail($id);

        $product->update($request->payload($product));

        ActivityLogger::log('marketplace.update', 'Memperbarui produk marketplace "' . $product->name . '".', $product);

        return redirect()->route('admin.marketplace.index')->with('success', 'Produk marketplace berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = MarketplaceProduct::findOrFail($id);
        $product->delete();

        ActivityLogger::log('marketplace.delete', 'Menghapus produk marketplace "' . $product->name . '".', $product);

        return redirect()->route('admin.marketplace.index')->with('success', 'Produk marketplace berhasil dihapus.');
    }

    /**
     * Aktif / nonaktifkan produk marketplace.
     */
    public function toggle($id)
    {
        $product = MarketplaceProduct::findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        ActivityLogger::log('marketplace.toggle', 'Mengubah status produk marketplace "' . $product->name . '" menjadi ' . ($product->is_active ? 'aktif' : 'nonaktif') . '.', $product);

        return back()->with('success', 'Status produk marketplace diperbarui.');
    }
}
