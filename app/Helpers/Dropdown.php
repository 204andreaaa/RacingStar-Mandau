<?php

namespace App\Helpers;

class Dropdown
{
    public static function activeStatusOpt($selected = null)
    {
        $options = [1 => 'Aktif', 0 => 'Tidak Aktif'];
        if ($selected !== null) {
            if (array_key_exists($selected, $options)) {
                return $options[$selected];
            }
        }
        return $options;
    }
    
    public static function requiredStatusOpt($selected = null)
    {
        $options = [1 => 'Wajib', 0 => 'Tidak Wajib'];
        if ($selected !== null) {
            if (array_key_exists($selected, $options)) {
                return $options[$selected];
            }
        }
        return $options;
    }
}