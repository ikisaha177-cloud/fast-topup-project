<?php

if (! function_exists('aria_current')) {
    /**
     * Kembalikan atribut aria-current bila kondisi aktif.
     *
     * Dipakai di navigasi/tab/chip supaya pembaca layar tahu item mana yang
     * sedang aktif, tanpa harus mengulang ekspresi kondisi di template.
     *
     * @param  mixed   $condition
     * @param  string  $value  'page' | 'step' | 'true'
     * @return string
     */
    function aria_current($condition, $value = 'page')
    {
        return $condition ? 'aria-current="' . $value . '"' : '';
    }
}
