<?php

use App\Casts\EncryptedValue;
use App\Contracts\Encryption\DataEncryptionProvider;
use App\Exceptions\DataEncryptionException;
use App\Services\Encryption\EncryptionManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestEncryptionProvider implements DataEncryptionProvider
{
    public function encrypt(mixed $value, string $keyVersion): string
    {
        return base64_encode(serialize(['key' => $keyVersion, 'value' => $value]));
    }

    public function decrypt(string $payload, string $keyVersion): mixed
    {
        $decoded = unserialize(base64_decode($payload, true));

        if (! is_array($decoded) || ($decoded['key'] ?? null) !== $keyVersion) {
            throw new DataEncryptionException('Test provider could not decrypt value.');
        }

        return $decoded['value'];
    }
}

class EncryptedCastTestRecord extends Model
{
    protected $table = 'encrypted_cast_test_records';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'secret' => EncryptedValue::class,
        ];
    }
}

beforeEach(function () {
    config()->set('data-encryption.driver', 'laravel');
    config()->set('data-encryption.envelope_version', '1');
    config()->set('data-encryption.allow_plaintext_fallback', false);
    config()->set('data-encryption.drivers.laravel.key_version', '1');
});

it('encrypts and decrypts values with the laravel provider', function () {
    $manager = app(EncryptionManager::class);

    $encrypted = $manager->encrypt('sensitive value');

    expect($encrypted)
        ->toBeString()
        ->toStartWith('irad:v1:laravel:k1:')
        ->not->toContain('sensitive value')
        ->and($manager->decrypt($encrypted))->toBe('sensitive value');
});

it('preserves non-string value types through encryption', function () {
    $manager = app(EncryptionManager::class);
    $value = ['enabled' => true, 'count' => 3, 'items' => ['alpha', 'beta']];

    expect($manager->decrypt($manager->encrypt($value)))->toBe($value);
});

it('uses randomized authenticated encryption for the same plaintext', function () {
    $manager = app(EncryptionManager::class);

    $first = $manager->encrypt('same value');
    $second = $manager->encrypt('same value');

    expect($first)->not->toBe($second)
        ->and($manager->decrypt($first))->toBe('same value')
        ->and($manager->decrypt($second))->toBe('same value');
});

it('rejects tampered ciphertext', function () {
    $manager = app(EncryptionManager::class);
    $encrypted = $manager->encrypt('do not alter');
    $tampered = substr($encrypted, 0, -1).(str_ends_with($encrypted, 'A') ? 'B' : 'A');

    expect(fn () => $manager->decrypt($tampered))
        ->toThrow(DataEncryptionException::class);
});

it('keeps null values null', function () {
    $manager = app(EncryptionManager::class);

    expect($manager->encrypt(null))->toBeNull()
        ->and($manager->decrypt(null))->toBeNull();
});

it('can decrypt data written by a different configured provider after the default driver changes', function () {
    config()->set('data-encryption.drivers.test', [
        'provider' => TestEncryptionProvider::class,
        'key_version' => '42',
    ]);
    config()->set('data-encryption.driver', 'test');

    $manager = app(EncryptionManager::class);
    $encrypted = $manager->encrypt('provider-portable');

    expect($encrypted)->toStartWith('irad:v1:test:k42:');

    config()->set('data-encryption.driver', 'laravel');

    expect($manager->decrypt($encrypted))->toBe('provider-portable');
});

it('rejects an unsupported encryption driver', function () {
    config()->set('data-encryption.driver', 'missing-driver');

    expect(fn () => app(EncryptionManager::class)->encrypt('value'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported IRAD encryption driver [missing-driver].');
});

it('does not silently accept plaintext unless migration fallback is explicitly enabled', function () {
    $manager = app(EncryptionManager::class);

    expect(fn () => $manager->decrypt('legacy plaintext'))
        ->toThrow(DataEncryptionException::class);

    config()->set('data-encryption.allow_plaintext_fallback', true);

    expect($manager->decrypt('legacy plaintext'))->toBe('legacy plaintext');
});

it('encrypts database values through the reusable eloquent cast', function () {
    Schema::create('encrypted_cast_test_records', function (Blueprint $table) {
        $table->id();
        $table->text('secret')->nullable();
    });

    try {
        $record = EncryptedCastTestRecord::query()->create([
            'secret' => 'classified-at-rest',
        ]);

        $raw = DB::table('encrypted_cast_test_records')
            ->where('id', $record->id)
            ->value('secret');

        expect($raw)
            ->toBeString()
            ->toStartWith('irad:v1:laravel:k1:')
            ->not->toContain('classified-at-rest')
            ->and($record->fresh()->secret)->toBe('classified-at-rest');
    } finally {
        Schema::dropIfExists('encrypted_cast_test_records');
    }
});
