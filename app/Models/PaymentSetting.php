<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = ['enabled', 'bank_name', 'bank_bin', 'account_number', 'account_holder', 'qr_path'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public static function active(): ?self
    {
        return self::whereKey(1)->where('enabled', true)->first();
    }
}
