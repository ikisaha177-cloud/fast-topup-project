<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'icon',
        'faqs',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'faqs' => 'array',
    ];

    /**
     * Daftar FAQ siap tayang: hanya entri {question, answer} berupa teks
     * yang tidak kosong. Data korup (bentuk salah / tag HTML) dibuang,
     * bukan dirender mentah ke halaman.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function faqItems(): array
    {
        $items = [];

        foreach ((array) $this->faqs as $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = isset($faq['question']) && is_string($faq['question'])
                ? trim(strip_tags($faq['question']))
                : '';
            $answer = isset($faq['answer']) && is_string($faq['answer'])
                ? trim(strip_tags($faq['answer']))
                : '';

            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = ['question' => $question, 'answer' => $answer];
        }

        return $items;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Game hanya boleh dihapus bila belum punya transaksi, supaya riwayat
     * pembayaran (audit) tidak ikut terhapus oleh cascade.
     */
    public function hasTransactions()
    {
        return $this->transactions()->exists();
    }
}
