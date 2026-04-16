<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public function uploadForModel(
        Model $model,
        array $files = [],
        array $metadata = [],
        ?int $uploadedByUserId = null,
        string $disk = 'public'
    ): array {
        $created = [];

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $category = $metadata[$index]['category'] ?? null;
            $description = $metadata[$index]['description'] ?? null;
            $isPrimary = (bool) ($metadata[$index]['is_primary'] ?? false);
            $sortOrder = (int) ($metadata[$index]['sort_order'] ?? $index);

            if ($isPrimary) {
                $this->clearPrimaryForCategory($model, $category);
            }

            $folder = $this->buildFolder($model);
            $path = $file->store($folder, $disk);

            $created[] = Attachment::create([
                'attachable_type' => $model->getMorphClass(),
                'attachable_id' => $model->getKey(),
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => basename($path),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize() ?: 0,
                'category' => $category,
                'description' => $description,
                'uploaded_by_user_id' => $uploadedByUserId,
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
            ]);
        }

        return $created;
    }

    public function deleteAttachment(Attachment $attachment): void
    {
        if ($attachment->path && Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();
    }

    public function deleteForModel(Model $model, array $attachmentIds = []): void
    {
        if (empty($attachmentIds)) {
            return;
        }

        $attachments = $model->attachments()
            ->whereIn('id', $attachmentIds)
            ->get();

        foreach ($attachments as $attachment) {
            $this->deleteAttachment($attachment);
        }
    }

    public function syncForModel(
        Model $model,
        array $newFiles = [],
        array $metadata = [],
        array $removeIds = [],
        ?int $uploadedByUserId = null,
        string $disk = 'public'
    ): void {
        $this->deleteForModel($model, $removeIds);
        $this->uploadForModel($model, $newFiles, $metadata, $uploadedByUserId, $disk);
    }

    public function normalizeForUi(Collection|array $attachments): array
    {
        return collect($attachments)
            ->map(function ($attachment) {
                return [
                    'id' => $attachment['id'] ?? null,
                    'original_name' => $attachment['original_name'] ?? '',
                    'stored_name' => $attachment['stored_name'] ?? '',
                    'disk' => $attachment['disk'] ?? 'public',
                    'path' => $attachment['path'] ?? '',
                    'mime_type' => $attachment['mime_type'] ?? '',
                    'extension' => $attachment['extension'] ?? '',
                    'size' => $attachment['size'] ?? 0,
                    'category' => $attachment['category'] ?? '',
                    'description' => $attachment['description'] ?? '',
                    'uploaded_by_user_id' => $attachment['uploaded_by_user_id'] ?? null,
                    'is_primary' => (bool) ($attachment['is_primary'] ?? false),
                    'sort_order' => (int) ($attachment['sort_order'] ?? 0),
                    'url' => $attachment['url'] ?? null,
                ];
            })
            ->sortBy([
                ['is_primary', 'desc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->toArray();
    }

    public function validatePrimaryPerCategory(array $metadata = []): void
    {
        $grouped = collect($metadata)
            ->groupBy(fn ($item) => $item['category'] ?? '__uncategorized__');

        foreach ($grouped as $category => $items) {
            $primaryCount = $items->where('is_primary', true)->count();

            if ($primaryCount > 1) {
                throw ValidationException::withMessages([
                    'attachments' => 'Only one uploaded file per category can be marked as primary.',
                ]);
            }
        }
    }

    protected function clearPrimaryForCategory(Model $model, ?string $category = null): void
    {
        $query = $model->attachments()->where('is_primary', true);

        if ($category === null) {
            $query->whereNull('category');
        } else {
            $query->where('category', $category);
        }

        $query->update(['is_primary' => false]);
    }

    protected function buildFolder(Model $model): string
    {
        $type = strtolower(class_basename($model));

        return "{$type}/{$model->getKey()}/attachments";
    }
}