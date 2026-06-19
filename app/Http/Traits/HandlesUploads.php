<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HandlesUploads
{
    protected function storeUploadedImage(Request $request, string $field, string $folder, ?string $oldPath = null): ?string
    {
        if (! $request->hasFile($field)) {
            return $oldPath;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $request->file($field)->store($folder, 'public');
    }
}
