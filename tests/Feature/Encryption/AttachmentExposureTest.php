<?php

use App\Models\Attachment;
use App\Models\Person;
use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores new person attachments on the private local disk by default', function () {
    Storage::fake('local');

    $person = Person::factory()->create();
    $file = UploadedFile::fake()->create('resume.pdf', 25, 'application/pdf');

    $created = app(AttachmentService::class)->uploadForModel($person, [$file]);

    expect($created)->toHaveCount(1)
        ->and($created[0]->disk)->toBe('local');

    Storage::disk('local')->assertExists($created[0]->path);
});

it('does not expose storage paths or disk details in attachment browser payloads', function () {
    $attachment = new Attachment([
        'original_name' => 'resume.pdf',
        'stored_name' => 'private-random-name.pdf',
        'disk' => 'local',
        'path' => 'person/12/attachments/private-random-name.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 100,
        'category' => 'resume',
        'description' => 'Resume',
        'uploaded_by_user_id' => 99,
        'is_primary' => true,
        'sort_order' => 1,
    ]);

    $payload = app(AttachmentService::class)->normalizeForUi([$attachment])[0];

    expect($payload)->not->toHaveKeys([
        'stored_name',
        'disk',
        'path',
        'uploaded_by_user_id',
    ]);
});


it('hides private storage coordinates when an attachment model is serialized', function () {
    $attachment = new Attachment([
        'original_name' => 'resume.pdf',
        'stored_name' => 'private-random-name.pdf',
        'disk' => 'local',
        'path' => 'person/12/attachments/private-random-name.pdf',
    ]);

    $serialized = $attachment->toArray();

    expect($serialized)->not->toHaveKeys(['stored_name', 'disk', 'path']);
});
