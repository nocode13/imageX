<?php

namespace App\Services;

use App\Jobs\ProcessImageJob;
use App\Models\User;
use App\Models\UserImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public function store(User $user, UploadedFile $file): UserImage
    {
        $tempPath = 'temp/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        Storage::disk('s3')->put($tempPath, $file->getContent());

        $userImage = UserImage::create([
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
            'temp_path' => $tempPath,
        ]);

        ProcessImageJob::dispatch($userImage->id);

        return $userImage;
    }

    public function index(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->images()
            ->with('imageFile')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findReadyImage(int $id): ?UserImage
    {
        $userImage = UserImage::with('imageFile')->find($id);

        if (! $userImage || ! $userImage->isReady() || ! $userImage->imageFile) {
            return null;
        }

        return $userImage;
    }

    public function findReadyImageWithThumbnail(int $id): ?UserImage
    {
        $userImage = UserImage::with('imageFile')->find($id);

        if (! $userImage || ! $userImage->isReady() || ! $userImage->imageFile?->thumbnail_path) {
            return null;
        }

        return $userImage;
    }

    public function destroy(User $user, int $id): bool
    {
        $userImage = UserImage::with('imageFile')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $userImage) {
            return false;
        }

        if ($userImage->temp_path) {
            Storage::disk('s3')->delete($userImage->temp_path);
        }

        $imageFile = $userImage->imageFile;

        $userImage->delete();

        if ($imageFile) {
            $otherReferences = UserImage::where('image_file_id', $imageFile->id)->exists();

            if (! $otherReferences) {
                Storage::disk('s3')->delete($imageFile->storage_path);

                if ($imageFile->thumbnail_path) {
                    Storage::disk('s3')->delete($imageFile->thumbnail_path);
                }

                $imageFile->delete();
            }
        }

        return true;
    }
}
