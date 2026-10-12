<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Tampilkan profil user yang login.
     */
    public function show()
    {
        $user = auth()->user();

        $stats = [
            'transactions' => $user->transactions()->count(),
            'orders'       => $user->marketplaceOrders()->count(),
            'topup_spent'  => (int) $user->transactions()->where('status', OrderStatus::SUCCESS)->sum('amount'),
            'market_spent' => (int) $user->marketplaceOrders()->where('status', OrderStatus::SUCCESS)->sum('total_amount'),
        ];

        $stats['total_spent'] = $stats['topup_spent'] + $stats['market_spent'];

        return view('profile.show', [
            'user'  => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Update nama / password.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak benar.',
            ]);
        }

        $user->name = $data['name'];

        if (! empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
