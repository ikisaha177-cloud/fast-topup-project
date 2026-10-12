<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotAdmin
{
    /**
     * Blokir admin dari route pembelian (top up, cart, checkout).
     * Admin diarahkan kembali ke dashboard admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Akun admin tidak dapat melakukan pembelian atau top up.');
        }

        return $next($request);
    }
}
