<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use App\Casts\EncryptedSearchableValue;
use App\Contracts\Workforce\PersonPositionRelationships;
use RuntimeException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\PersonPhoneNumber;
use App\Models\Address;
use App\Models\Attachment;
use App\Models\Group;
use App\Models\Team;
use App\Models\PersonNote;
use App\Models\CustomFieldValue;
use App\Services\Encryption\LookupHashService;
use Illuminate\Database\Eloquent\Builder;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'person_code',
        'first_name',
        'alternate_first_name',
        'preferred_name',
        'last_name',
        'alternate_last_name',
        'company_name',
        'email',
        'employment_status',
        'notes',
        'resume_path',
    ];


    protected $casts = [
        'person_code' => EncryptedSearchableValue::class.':person_code_lookup',
        'notes' => EncryptedValue::class,
    ];

    public function scopeWherePersonCode(Builder $query, string|int $personCode): Builder
    {
        return $query->where(
            'person_code_lookup',
            app(LookupHashService::class)->hash($personCode),
        );
    }

    public static function findByPersonCode(string|int $personCode): ?self
    {
        return static::query()->wherePersonCode($personCode)->first();
    }

    public static function personCodeExists(string|int $personCode, ?int $ignoreId = null): bool
    {
        return static::query()
            ->wherePersonCode($personCode)
            ->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '<>', $ignoreId))
            ->exists();
    }

    public static function updateOrCreateByPersonCode(string|int $personCode, array $values = []): self
    {
        $person = static::query()->wherePersonCode($personCode)->first() ?? new static();
        $person->fill(['person_code' => (string) $personCode, ...$values]);
        $person->save();

        return $person;
    }

    /**
     * Legacy public API retained until Positions moves into its own module.
     * Core does not define or reference a Position model.
     */
    public function assignments(): HasMany
    {
        return $this->positionRelationships()->assignments($this);
    }

    public function positions(): BelongsToMany
    {
        return $this->positionRelationships()->positions($this);
    }


    /**
     * Eloquent relationship methods cannot safely return an empty stand-in when
     * their owner module is absent. Other Person operations remain available.
     */
    private function positionRelationships(): PersonPositionRelationships
    {
        if (! app()->bound(PersonPositionRelationships::class)) {
            throw new RuntimeException('Positions integration is unavailable for this installation.');
        }

        return app(PersonPositionRelationships::class);
    }


    public function getPrimaryAddressDisplayAttribute(): string
    {
        $address = $this->relationLoaded('primaryAddress')
            ? $this->getRelation('primaryAddress')
            : $this->primaryAddress()->first();

        if (! $address) {
            return '';
        }

        $cityStatePostal = trim(implode(' ', array_filter([
            trim((string) $address->state),
            trim((string) $address->postal_code),
        ], fn ($value) => $value !== '')));

        return implode(', ', array_filter([
            trim((string) $address->line_1),
            trim((string) $address->city),
            $cityStatePostal,
        ], fn ($value) => $value !== ''));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function phoneNumbers()
    {
        return $this->hasMany(PersonPhoneNumber::class);
    }

    public function primaryPhoneNumber()
    {
        return $this->hasOne(PersonPhoneNumber::class)->where('is_primary', true);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(Address::class)->where('is_primary', true);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'person_group')
            ->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'person_team')
            ->withTimestamps();
    }

    public function personNotes()
    {
        return $this->hasMany(PersonNote::class)
            ->orderByRaw("CASE category WHEN 'kudos' THEN 1 WHEN 'reprimand' THEN 2 ELSE 3 END")
            ->latest();
    }
    
    public function customFieldValues()
    {
        return $this->morphMany(CustomFieldValue::class, 'fieldable');
    }

}