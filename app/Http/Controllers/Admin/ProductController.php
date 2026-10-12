<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\GameTitle;
use App\Models\Product;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $gameId = $request->query('game');

        $products = Product::with('gameTitle')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($gameId, function ($query) use ($gameId) {
                $query->where('game_title_id', $gameId);
            })
            ->orderBy('game_title_id')
            ->orderBy('price')
            ->paginate(15)
            ->withQueryString();

        $games = GameTitle::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'games', 'search', 'gameId'));
    }

    public function create()
    {
        $games = GameTitle::orderBy('name')->get();

        return view('admin.products.create', compact('games'));
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->payload());

        ActivityLogger::log('product.create', 'Menambahkan produk top up "' . $product->name . '".', $product);

        return redirect()->route('admin.products.index')->with('success', 'Produk top up berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $games   = GameTitle::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'games'));
    }

    public function update(ProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update($request->payload());

        ActivityLogger::log('product.update', 'Memperbarui produk top up "' . $product->name . '".', $product);

        return redirect()->route('admin.products.index')->with('success', 'Produk top up berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        ActivityLogger::log('product.delete', 'Menghapus produk top up "' . $product->name . '".', $product);

        return redirect()->route('admin.products.index')->with('success', 'Produk top up berhasil dihapus.');
    }

    /**
     * Aktif / nonaktifkan produk.
     */
    public function toggle($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        ActivityLogger::log('product.toggle', 'Mengubah status produk "' . $product->name . '" menjadi ' . ($product->is_active ? 'aktif' : 'nonaktif') . '.', $product);

        return back()->with('success', 'Status produk diperbarui.');
    }
}
