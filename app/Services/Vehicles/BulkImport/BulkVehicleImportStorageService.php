<?php

namespace App\Services\Vehicles\BulkImport;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkVehicleImportStorageService
{
    private const DISK = 'local';

    public function store(UploadedFile $file, User $user): string
    {
        $directory = sprintf('bulk-vehicle-imports/%d', $user->id);
        $filename = sprintf('%s-%s.csv', now()->format('YmdHis'), Str::uuid()->toString());

        return $file->storeAs($directory, $filename, self::DISK);
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::disk(self::DISK)->exists($path);
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk(self::DISK)->path($path);
    }

    public function delete(?string $path): void
    {
        if ($this->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
