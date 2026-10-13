<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1 #7: kalkulator winrate MLBB — halaman organik gratis;
 * perhitungan dari query string sehingga hasil bisa dibagikan
 * sebagai tautan dan tidak menyimpan apa pun.
 */
class WinrateCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_halaman_tampil_dengan_formulir()
    {
        $this->get(route('page.winrate'))
            ->assertOk()
            ->assertSee('Kalkulator Winrate MLBB', false)
            ->assertSee('name="menang"', false)
            ->assertSee('name="kalah"', false)
            ->assertSee('Cara Menghitung Winrate', false);
    }

    public function test_menghitung_winrate_dari_isian()
    {
        $this->get(route('page.winrate') . '?menang=7&kalah=3')
            ->assertOk()
            ->assertSee('70%', false)
            ->assertSee('total 10 pertandingan', false);
    }

    public function test_membulatkan_satu_desimal()
    {
        $this->get(route('page.winrate') . '?menang=2&kalah=1')
            ->assertOk()
            ->assertSee('66.7%', false);
    }

    public function test_menolak_isian_bukan_angka()
    {
        $this->get(route('page.winrate') . '?menang=abc&kalah=3')
            ->assertOk()
            ->assertSee('bilangan bulat', false)
            ->assertDontSee('winrate-result', false);
    }

    public function test_menolak_nol_pertandingan()
    {
        $this->get(route('page.winrate') . '?menang=0&kalah=0')
            ->assertOk()
            ->assertSee('Minimal satu pertandingan', false)
            ->assertDontSee('winrate-result', false);
    }

    public function test_menolak_isian_negatif()
    {
        // min="0" di HTML hanya penanda browser; query negatif harus ditolak server.
        $this->get(route('page.winrate') . '?menang=-5&kalah=10')
            ->assertOk()
            ->assertSee('bilangan bulat', false)
            ->assertDontSee('winrate-result', false);
    }

    public function test_tautan_footer_dan_sitemap_menyertakan_kalkulator()
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('page.winrate'), false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('page.winrate'), false);
    }
}
