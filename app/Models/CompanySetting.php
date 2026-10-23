<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("company_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Taux de TVA applique aux factures : 0 tant que la TVA est desactivee dans les parametres.
     * Le taux saisi (tax_rate) est conserve pour une reactivation ulterieure.
     */
    public static function taxRate(): float
    {
        return static::get('tax_enabled', '0') === '1' ? (float) static::get('tax_rate', 20) : 0.0;
    }

    /** Temps attendu d'un chariot dans le tunnel, en heures : [minimum, maximum] (defaut 1 a 3 jours). */
    public static function tunnelWindow(): array
    {
        $min = max(1, (int) static::get('tunnel_min_hours', 24));
        $max = max($min, (int) static::get('tunnel_max_hours', 72));

        return [$min, $max];
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("company_setting_{$key}");
    }
}
