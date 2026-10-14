<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Menambahkan header keamanan pada setiap respons.
 *
 * Toko ini punya tombol pembayaran, jadi wajib tahan clickjacking (iframe),
 * sniffing konten, dan kebocoran referrer. Header dikirim global sehingga
 * berlaku untuk halaman toko, invoice, portal pembayaran, maupun error page.
 *
 * HSTS hanya dikirim saat permintaan sudah lewat HTTPS agar tidak memaksa
 * HTTPS pada lingkungan pengembangan lokal.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Jangan menimpa header yang sudah diatur khusus oleh route (mis. unduhan).
        if ($response->headers->has('Content-Security-Policy')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');
        $response->headers->set('Content-Security-Policy', $this->policy());

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Kebijakan CSP untuk toko.
     *
     * Skrip inline dipakai untuk navigasi & efek 3D, jadi 'unsafe-inline'
     * diperlukan. Sumber daya eksternal dibatasi ke Google Fonts (satu-satunya
     * pihak ketiga yang dipakai). Objek/iframe diblokir total.
     */
    protected function policy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data:",
            "font-src 'self' https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "script-src 'self' 'unsafe-inline'",
            "connect-src 'self'",
        ]);
    }
}
