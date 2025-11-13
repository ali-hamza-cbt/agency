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

    /**
     * Delete multiple files from storage.
     *
     * @param array|string|null $paths
     * @param string|null $disk
     * @return void
     */
    public static function deleteMultiple($paths, ?string $disk = null): void
    {
        if (!$paths) return;

        $diskToUse = $disk ?? config('filesystems.default');
        $pathsArray = is_array($paths) ? $paths : [$paths];

        foreach ($pathsArray as $path) {
            if ($path && Storage::disk($diskToUse)->exists($path)) {
                Storage::disk($diskToUse)->delete($path);
            }
        }
    }

    /**
     * Delete all files except the ones to keep.
     *
     * @param  array              $keepPaths     Paths to keep
     * @param  array|string|null  $allPaths      All existing paths (array or single)
     * @param  string|null        $disk          Storage disk (default from config if null)
     * @return void
     */
    public static function deleteAllExcept(array $keepPaths, array|string|null $allPaths, ?string $disk = null)
    {
        if (empty($allPaths)) {
            return;
        }

        $diskToUse = $disk ?? config('filesystems.default');
        $pathsArray = is_array($allPaths) ? $allPaths : [$allPaths];

        foreach ($pathsArray as $path) {
            if ($path && !in_array($path, $keepPaths, true) && Storage::disk($diskToUse)->exists($path)) {
                Storage::disk($diskToUse)->delete($path);
            }
        }
    }
}
