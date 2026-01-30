<?php

namespace App\Jobs;

use App\Models\ImageFile;
use App\Models\UserImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProcessImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly int $userImageId
    ) {}

    public function handle(): void
    {
        $userImage = UserImage::find($this->userImageId);

        if (! $userImage || ! $userImage->temp_path) {
            return;
        }

        try {
            $tempPath = $userImage->temp_path;
            $tempContent = Storage::disk('s3')->get($tempPath);

            if (! $tempContent) {
                $this->markFailed($userImage);

                return;
            }

            $contentHash = hash('sha256', $tempContent);

            $existingFile = ImageFile::where('content_hash', $contentHash)->first();

            if ($existingFile) {
                $userImage->update([
                    'image_file_id' => $existingFile->id,
                    'status' => 'ready',
                    'temp_path' => null,
                ]);

                Storage::disk('s3')->delete($tempPath);

                return;
            }

            $image = Image::read($tempContent);
            $width = $image->width();
            $height = $image->height();

            $webpContent = $image->toWebp(85)->toString();

            $thumbnail = $image->coverDown(200, 200);
            $thumbnailContent = $thumbnail->toWebp(85)->toString();

            $storagePath = 'images/' . substr($contentHash, 0, 2) . '/' . $contentHash . '.webp';
            $thumbnailPath = 'thumbnails/' . substr($contentHash, 0, 2) . '/' . $contentHash . '.webp';

            Storage::disk('s3')->put($storagePath, $webpContent);
            Storage::disk('s3')->put($thumbnailPath, $thumbnailContent);

            $imageFile = ImageFile::create([
                'content_hash' => $contentHash,
                'storage_path' => $storagePath,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => 'image/webp',
                'size' => strlen($webpContent),
                'width' => $width,
                'height' => $height,
            ]);

            $userImage->update([
                'image_file_id' => $imageFile->id,
                'status' => 'ready',
                'temp_path' => null,
            ]);

            Storage::disk('s3')->delete($tempPath);
        } catch (\Throwable $e) {
            $this->markFailed($userImage);

            throw $e;
        }
    }

    private function markFailed(UserImage $userImage): void
    {
        $userImage->update(['status' => 'failed']);
    }
}
