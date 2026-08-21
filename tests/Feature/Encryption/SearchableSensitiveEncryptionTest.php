<?php

use App\Models\CustomField;
use App\Models\Person;
use App\Services\CustomFieldService;
use App\Services\Encryption\LookupHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('data-encryption.driver', 'laravel');
    config()->set('data-encryption.allow_plaintext_fallback', false);
    config()->set('data-encryption.lookup_key', 'test-lookup-key-for-irad');
});

it('encrypts person code at rest and resolves it through a keyed lookup hash', function () {
    $person = Person::factory()->create([
        'person_code' => 'ADFS-PER-100',
    ]);

    $raw = DB::table('people')->where('id', $person->id)->first();

    expect($raw->person_code)
        ->toStartWith('irad:v1:laravel:k1:')
        ->not->toContain('ADFS-PER-100')
        ->and($raw->person_code_lookup)
        ->toBe(app(LookupHashService::class)->hash('ADFS-PER-100'))
        ->not->toBe(hash('sha256', 'ADFS-PER-100'));

    expect($person->fresh()->person_code)->toBe('ADFS-PER-100')
        ->and(Person::findByPersonCode('adfs-per-100')?->id)->toBe($person->id);
});

it('keeps encrypted person codes unique through the lookup hash', function () {
    Person::factory()->create(['person_code' => 'PER-UNIQUE-1']);

    expect(Person::personCodeExists('per-unique-1'))->toBeTrue()
        ->and(Person::personCodeExists('PER-UNIQUE-2'))->toBeFalse();
});

it('encrypts sensitive custom text values while returning plaintext through the custom field service', function () {
    $field = CustomField::query()->create([
        'entity_type' => CustomField::ENTITY_PERSON,
        'name' => 'Sensitive Identifier',
        'key' => 'sensitive_identifier',
        'field_type' => CustomField::TYPE_TEXT,
        'is_required' => false,
        'is_active' => true,
        'is_sensitive' => true,
        'is_list_column' => false,
        'is_searchable' => false,
        'is_filterable' => false,
        'sort_order' => 10,
    ]);

    $person = Person::factory()->create();
    $service = app(CustomFieldService::class);

    $service->syncValues($person, CustomField::ENTITY_PERSON, [
        (string) $field->id => 'SECRET-CUSTOM-VALUE',
    ]);

    $stored = DB::table('custom_field_values')
        ->where('custom_field_id', $field->id)
        ->where('fieldable_id', $person->id)
        ->value('value_text');

    expect($stored)
        ->toStartWith('irad:v1:laravel:k1:')
        ->not->toContain('SECRET-CUSTOM-VALUE')
        ->and($service->valuesForForm($person, CustomField::ENTITY_PERSON)[(string) $field->id])
        ->toBe('SECRET-CUSTOM-VALUE')
        ->and(collect($service->displayValues($person, CustomField::ENTITY_PERSON))->firstWhere('id', $field->id)['value'])
        ->toBe('SECRET-CUSTOM-VALUE');
});

it('can rebuild person code lookup hashes after the lookup secret changes', function () {
    $person = Person::factory()->create(['person_code' => 'ROTATE-PER-100']);

    config()->set('data-encryption.lookup_key', 'replacement-lookup-key');

    expect(Person::findByPersonCode('ROTATE-PER-100'))->toBeNull();

    Artisan::call('irad:rebuild-encryption-lookups');

    expect(Person::findByPersonCode('ROTATE-PER-100')?->id)->toBe($person->id);
});

