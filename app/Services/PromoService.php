<?php

namespace App\Services;

use App\Models\PromoCode;

/**
 * Satu-satunya tempat yang memutuskan apakah sebuah kode promo boleh dipakai.
 *
 * Dipakai halaman checkout (untuk pratinjau potongan) dan saat pesanan benar-benar
 * dibuat (dengan baris promo dikunci) supaya kuota tidak bisa dilewati dua pembeli
 * yang menekan "Bayar" bersamaan.
 */
class PromoService
{
    /**
     * Validasi kode promo terhadap subtotal saat ini.
     *
     * @param  string|null  $code
     * @param  int          $amount  Subtotal sebelum diskon.
     * @param  string       $scope   "topup" atau "marketplace".
     * @param  bool         $lock    Kunci baris promo (pakai di dalam transaksi DB).
     * @return array{ok: bool, promo: \App\Models\PromoCode|null, discount: int, message: string|null}
     */
    public function quote($code, $amount, $scope, $lock = false)
    {
        $amount = (int) $amount;
        $code   = strtoupper(trim((string) $code));

        if ($code === '') {
            return $this->fail('Kode promo belum diisi.');
        }

        if (! preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) {
            return $this->fail('Format kode promo tidak valid.');
        }

        $query = PromoCode::where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        $promo = $query->first();

        if (! $promo) {
            return $this->fail('Kode promo "' . $code . '" tidak ditemukan.');
        }

        if (! $promo->is_active) {
            return $this->fail('Kode promo "' . $code . '" sudah tidak aktif.');
        }

        if (! $promo->hasStarted()) {
            return $this->fail('Kode promo "' . $code . '" belum berlaku. Mulai ' . $promo->starts_at->format('d-m-Y H:i') . '.');
        }

        if ($promo->hasExpired()) {
            return $this->fail('Kode promo "' . $code . '" sudah kedaluwarsa.');
        }

        if (! $promo->coversScope($scope)) {
            return $this->fail('Kode promo "' . $code . '" hanya berlaku untuk ' . $promo->scopeLabel() . '.');
        }

        if ($promo->isUsedUp()) {
            return $this->fail('Kode promo "' . $code . '" sudah mencapai batas pemakaian.');
        }

        if ($amount < (int) $promo->min_spend) {
            return $this->fail('Minimal belanja Rp ' . number_format((int) $promo->min_spend, 0, ',', '.') . ' untuk memakai kode ini.');
        }

        $discount = $promo->discountFor($amount);

        if ($discount < 1) {
            return $this->fail('Kode promo ini tidak menghasilkan potongan untuk belanjaanmu.');
        }

        return [
            'ok'       => true,
            'promo'    => $promo,
            'discount' => $discount,
            'message'  => null,
        ];
    }

    /**
     * Hasil gagal dengan format yang sama seperti quote() yang berhasil.
     *
     * @return array{ok: bool, promo: null, discount: int, message: string}
     */
    private function fail($message)
    {
        return [
            'ok'       => false,
            'promo'    => null,
            'discount' => 0,
            'message'  => $message,
        ];
    }
}
