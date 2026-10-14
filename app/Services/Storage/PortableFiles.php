<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** New files use configurable disks. Old unprefixed paths stay on their original disks. */
class PortableFiles
{
    public static function documentsDisk(): string { return (string) config('insite_storage.documents_disk', 'local'); }
    public static function importsDisk(): string { return (string) config('insite_storage.imports_disk', 'local'); }
    public static function reference(string $disk, string $path, string $legacyDisk): string
    {
        return $disk === $legacyDisk ? $path : $disk.':'.$path;
    }
    public static function resolve(string $reference, string $legacyDisk): array
    {
        foreach (['azure_documents', 'azure_imports'] as $disk) {
            if (str_starts_with($reference, $disk.':')) return [$disk, substr($reference, strlen($disk) + 1)];
        }
        return [$legacyDisk, $reference];
    }
    public static function localPath(string $reference, string $legacyDisk, callable $callback): mixed
    {
        [$disk, $path] = self::resolve($reference, $legacyDisk);
        if ($disk === 'local' || $disk === 'public') return $callback(Storage::disk($disk)->path($path));
        $temp = tempnam(sys_get_temp_dir(), 'insite-');
        if ($temp === false) throw new RuntimeException('Could not create temporary import file.');
        $stream = null;
        try {
            $stream = Storage::disk($disk)->readStream($path);
            if (!is_resource($stream)) throw new RuntimeException('Cannot download workbook.');
            $target = fopen($temp, 'wb');
            if (!$target) throw new RuntimeException('Cannot write temporary workbook.');
            try { stream_copy_to_stream($stream, $target); } finally { fclose($target); }
            return $callback($temp);
        } finally {
            if (is_resource($stream)) fclose($stream);
            @unlink($temp);
        }
    }
}
