<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageStorageHelper
{
    /**
     * Store a new image file under user account path.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param ?string $disk
     * @return string Path to the stored file
     *
     * @throws \Exception
     */
    public static function store(UploadedFile $file, string $folder, ?string $disk = null): string
    {
        $account = currentAccount();

        if (!$account) {
            throw new \Exception('No account context found.');
        }

        $role = $account->role ?? 'unknown';
        $path = "user_uploads/{$role}/{$account->id}/{$folder}";

        return $file->store($path, $disk ?? config('filesystems.default'));
    }

    /**
     * Update an existing image file: deletes the old file and stores the new one.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string|null $oldPath
     * @param ?string $disk
     * @return string Path to the new stored file
     *
     * @throws \Exception
     */
    public static function update(UploadedFile $file, string $folder, ?string $oldPath, ?string $disk = null): string
    {
        $diskToUse = $disk ?? config('filesystems.default');

        if ($oldPath && Storage::disk($diskToUse)->exists($oldPath)) {
            Storage::disk($diskToUse)->delete($oldPath);
        }

        return self::store($file, $folder, $diskToUse);
    }
}
