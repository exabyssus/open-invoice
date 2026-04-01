<?php

namespace App\Models;

use App\NumberStyles;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property ?int owner_id
 * @property ?User owner

 */
class Company extends Model implements HasName
{
    use HasFactory;
    protected $fillable = [
        'owner_id',
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

    protected function casts(): array
    {
        return [
            'show_accounts' => 'boolean',
            'number_style' => NumberStyles::class,
        ];
    }

    public function getFilamentName(): string
    {
        return $this->title;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class)->whereNull('accepted_at');
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
