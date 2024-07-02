<?php

namespace App\Models;

use Filament\Facades\Filament;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string title
 * @property string registration_number
 * @property string address
 * @property ?string agreement_number
 * @property ?string vat_number
 * @property ?string bank_name
 * @property ?string bank_swift
 * @property ?string bank_iban

 */
class Client extends Model implements HasName
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'registration_number',
        'address',
        'agreement_number',
        'vat_number',
        'bank_name',
        'bank_swift',
        'bank_iban'
    ];

    protected static function booted(): void
    {
        $tenant = Filament::getTenant();

        static::addGlobalScope('team', static function (Builder $query) use ($tenant) {
            $query->whereBelongsTo($tenant);
        });
    }

    public function getFilamentName(): string
    {
        return $this->title;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
