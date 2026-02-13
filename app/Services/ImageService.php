<?php

namespace App\Services;

use App\DTO\UploadImageDTO;
use App\Enums\ImageStatus;
use App\Exceptions\ImageNotFoundException;
use App\Jobs\ProcessImageJob;
use App\Models\User;
use App\Models\UserImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public function upload(User $user, UploadImageDTO $dto): UserImage
    {
        $file = $dto->image;
        $tempPath = 'temp/' . Str::uuid() . '.' . $file->guessExtension();

        Storage::disk('s3')->put($tempPath, $file->getContent());

        $userImage = UserImage::create([
            'user_id' => $user->id,
            'original_name' => $this->sanitizeFilename($file->getClientOriginalName()),
            'status' => ImageStatus::Pending,
            'temp_path' => $tempPath,
        ]);

        ProcessImageJob::dispatch($userImage->id);

        return $userImage;
    }

    public function delete(User $user, int $id): UserImage
    {
        $userImage = UserImage::with('imageFile')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $userImage) {
            throw new ImageNotFoundException();
        }

        DB::transaction(function () use ($userImage): void {
            if ($userImage->temp_path) {
                Storage::disk('s3')->delete($userImage->temp_path);
            }

            $imageFile = $userImage->imageFile;

            $userImage->delete();

            if (! $imageFile) {
                return;
            }

            $hasOtherReferences = UserImage::where('image_file_id', $imageFile->id)->exists();

            if ($hasOtherReferences) {
                return;
            }

            Storage::disk('s3')->delete(array_filter([
                $imageFile->storage_path,
                $imageFile->thumbnail_path,
            ]));

            $imageFile->delete();
        });

        return $userImage;
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^\w\s\-.]/', '', $filename) ?? $filename;
        $filename = trim($filename);

        return mb_substr($filename, 0, 255) ?: 'unnamed';
    }
}
