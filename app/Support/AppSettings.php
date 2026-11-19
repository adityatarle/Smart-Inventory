<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $value = DB::table('app_settings')->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function receiveTolerancePercent(): float
    {
        return max(0, (float) self::get('purchase_receive_tolerance_pct', 0));
    }
}
