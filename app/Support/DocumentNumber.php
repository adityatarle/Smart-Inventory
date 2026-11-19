<?php

namespace App\Support;

use Illuminate\Support\Str;

class DocumentNumber
{
    public static function make(string $prefix): string
    {
        return strtoupper($prefix).'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
    }
}
