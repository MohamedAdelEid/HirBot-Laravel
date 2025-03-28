<?php

namespace App\Shared\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    private const ALLOWED_TYPES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'video' => ['mp4', 'mov', 'avi'],
        'document' => ['pdf', 'doc', 'docx']
    ];

    public function upload(
        UploadedFile|array $files,
        string $type,
        string|array $fileName,
        ?string $customPath = null,
        string $disk = 'public'
    ): string|array {
        return is_array($files)
            ? $this->uploadMultiple($files, $type, $fileName, $customPath, $disk)
            : $this->uploadSingle($files, $type, $fileName, $customPath, $disk);
    }

    public function update(
        string $oldPath,
        UploadedFile $newFile,
        string $type,
        string $fileName,
        ?string $customPath = null,
        string $disk = 'public'
    ): string {
        $this->delete($oldPath, $disk);
        return $this->uploadSingle($newFile, $type, $fileName, $customPath, $disk);
    }

    public function delete(string|array $paths, string $disk = 'public'): bool
    {
        if (is_array($paths)) {
            return collect($paths)->every(fn($path) => Storage::disk($disk)->delete($path));
        }

        return Storage::disk($disk)->delete($paths);
    }

    private function uploadSingle(
        UploadedFile $file,
        string $type,
        string $fileName,
        ?string $customPath,
        string $disk
    ): string {
        if (!$this->validateFileType($file, $type)) {
            throw new \InvalidArgumentException("Invalid file type for {$type}");
        }

        $extension = $file->getClientOriginalExtension();
        $fullFileName = $fileName . '.' . $extension;
        $path = $this->buildPath($customPath, $type, $fullFileName);

        Storage::disk($disk)->put($path, file_get_contents($file));

        // For Azure, return the full URL
        // if ($disk === 'azure') {
        //     return Storage::disk($disk)->url($path);
        // }

        return $path;
    }

    private function uploadMultiple(
        array $files,
        string $type,
        array $fileNames,
        ?string $customPath,
        string $disk
    ): array {
        if (count($files) !== count($fileNames)) {
            throw new \InvalidArgumentException('Number of files and filenames must match');
        }

        $paths = [];
        foreach ($files as $index => $file) {
            $paths[] = $this->uploadSingle($file, $type, $fileNames[$index], $customPath, $disk);
        }

        return $paths;
    }

    private function validateFileType(UploadedFile $file, string $type): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, self::ALLOWED_TYPES[$type] ?? []);
    }

    private function buildPath(?string $customPath, string $type, string $fileName): string
    {
        if ($customPath) {
            return rtrim($customPath, '/') . '/' . $fileName;
        }

        return "uploads/{$type}s/" . $fileName;
    }
}
