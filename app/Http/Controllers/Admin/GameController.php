<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GameRequest;
use App\Models\GameTitle;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $games = GameTitle::withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.games.index', compact('games', 'search'));
    }

    public function create()
    {
        return view('admin.games.create');
    }

    public function store(GameRequest $request)
    {
        $data = $request->payload();
        $data['slug'] = $this->uniqueSlug($data['name']);

        $game = GameTitle::create($data);

        ActivityLogger::log('game.create', 'Menambahkan game "' . $game->name . '".', $game);

        return redirect()->route('admin.games.index')->with('success', 'Game berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $game = GameTitle::findOrFail($id);

        return view('admin.games.edit', compact('game'));
    }

    public function update(GameRequest $request, $id)
    {
        $game = GameTitle::findOrFail($id);

        $data = $request->payload($game);

        // Slug hanya berubah kalau nama berubah, dan tetap unik.
        if ($data['name'] !== $game->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $game->id);
        }

        $game->update($data);

        ActivityLogger::log('game.update', 'Memperbarui game "' . $game->name . '".', $game);

        return redirect()->route('admin.games.index')->with('success', 'Game berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $game = GameTitle::findOrFail($id);
        $game->delete();

        ActivityLogger::log('game.delete', 'Menghapus game "' . $game->name . '".', $game);

        return redirect()->route('admin.games.index')->with('success', 'Game berhasil dihapus.');
    }

    /**
     * Aktif / nonaktifkan game.
     */
    public function toggle($id)
    {
        $game = GameTitle::findOrFail($id);
        $game->is_active = ! $game->is_active;
        $game->save();

        ActivityLogger::log('game.toggle', 'Mengubah status game "' . $game->name . '" menjadi ' . ($game->is_active ? 'aktif' : 'nonaktif') . '.', $game);

        return back()->with('success', 'Status game diperbarui.');
    }

    /**
     * Buat slug unik dari nama.
     */
    private function uniqueSlug($name, $ignoreId = null)
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (GameTitle::where('slug', $slug)
            ->when($ignoreId, function ($q) use ($ignoreId) {
                return $q->where('id', '!=', $ignoreId);
            })
            ->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
