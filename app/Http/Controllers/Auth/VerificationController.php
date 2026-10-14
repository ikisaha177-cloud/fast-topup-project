<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Verifikasi alamat email akun.
 *
 * Tautan bertanda tangan (signed URL) dikirim saat registrasi. Membuka tautan
 * menandai email sebagai terverifikasi, lalu mengarahkan pembeli ke beranda.
 *
 * Tautan kedaluwarsa/berubah ditolak dengan halaman 403 — pembeli bisa minta
 * tautan baru lewat tombol "kirim ulang" di halaman ini.
 */
class VerificationController extends Controller
{
    /**
     * Tampilkan pemberitahuan "email belum diverifikasi".
     */
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('home')
            : view('auth.verify-email');
    }

    /**
     * Proses tautan verifikasi bertanda tangan.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = $request->user();

        abort_unless((int) $id === $user->getKey(), 403);
        abort_unless(hash_equals((string) $hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return redirect()->route('home')->with('success', 'Email berhasil diverifikasi. Terima kasih!');
    }

    /**
     * Kirim ulang tautan verifikasi.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Tautan verifikasi baru sudah dikirim ke email kamu.');
    }
}
