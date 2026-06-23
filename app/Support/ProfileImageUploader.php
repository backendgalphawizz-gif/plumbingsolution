<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileImageUploader
{
    public static function resolveUpload(Request $request, string ...$keys): ?UploadedFile
    {
        foreach ($keys as $key) {
            if ($request->hasFile($key)) {
                return $request->file($key);
            }
        }

        foreach ($keys as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                return self::fromBase64($value, $key);
            }
        }

        return null;
    }

    public static function fromBase64(string $value, string $name = 'profile_image'): ?UploadedFile
    {
        $value = trim($value);
        $extension = 'jpg';

        if (preg_match('/^data:image\/(\w+);base64,(.+)$/i', $value, $matches)) {
            $extension = strtolower($matches[1] === 'jpeg' ? 'jpg' : $matches[1]);
            $value = $matches[2];
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'profile_');
        if ($tmpPath === false) {
            return null;
        }

        file_put_contents($tmpPath, $decoded);
        $mime = mime_content_type($tmpPath) ?: 'image/jpeg';

        if (! str_starts_with($mime, 'image/')) {
            @unlink($tmpPath);

            return null;
        }

        return new UploadedFile(
            $tmpPath,
            Str::slug($name).'.'.$extension,
            $mime,
            null,
            true
        );
    }

    public static function storeAvatar($user, Request $request, string $directory = 'avatars', int $maxKilobytes = 5120): ?string
    {
        $file = self::resolveUpload($request, 'avatar', 'profile_image');
        if (! $file) {
            return null;
        }

        if ($file->getSize() > $maxKilobytes * 1024) {
            throw new \InvalidArgumentException('Image must not be greater than '.$maxKilobytes.' kilobytes.');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        return $file->store($directory, 'public');
    }
}
