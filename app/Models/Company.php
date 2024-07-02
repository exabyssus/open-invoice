<?php

namespace App\Models;

use App\NumberStyles;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string title
 * @property string registration_number
 * @property string address
 * @property ?string agreement_number
 * @property ?string vat_number
 * @property ?string bank_name
 * @property ?string bank_swift
 * @property ?string bank_iban
 * @property ?string logo
 * @property ?string number_prefix
 * @property ?string number_suffix
 * @property ?NumberStyles number_style
 * @property boolean show_accounts

 */
class Company extends Model implements HasName
{
    use HasFactory;
    protected $fillable = [
        'title',
        'registration_number',
        'address',
        'agreement_number',
        'vat_number',
        'bank_name',
        'bank_swift',
        'bank_iban',
        'logo',
        'show_accounts',
        'number_prefix',
        'number_style',
        'number_suffix',
    ];

    public $casts = [
        'show_accounts' => 'boolean',
        'number_style' => NumberStyles::class
    ];

    public function getFilamentName(): string
    {
        return $this->title;
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_companies');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
