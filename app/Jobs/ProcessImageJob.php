<?php

namespace App\Jobs;

use App\DTO\CreateImageFileDTO;
use App\Enums\ImageStatus;
use App\Models\ImageFile;
use App\Models\UserImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProcessImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $userImageId
    ) {
        $this->onConnection('redis');
        $this->onQueue('image-processing');
    }

    public function handle(): void
    {
        $userImage = UserImage::find($this->userImageId);

        if (! $userImage || ! $userImage->temp_path) {
            Log::warning('ProcessImageJob: UserImage not found or no temp_path', [
                'user_image_id' => $this->userImageId,
            ]);

            return;
        }

        $tempPath = $userImage->temp_path;

        try {
            $tempContent = $this->downloadTempFile($tempPath);
            $contentHash = hash('sha256', $tempContent);

            $existingFile = $this->findExistingImageFile($contentHash);

            if ($existingFile) {
                $this->linkUserImage($userImage, $existingFile);
                $this->deleteTempFile($tempPath);

                Log::info('ProcessImageJob: Deduplicated image', [
                    'user_image_id' => $this->userImageId,
                    'image_file_id' => $existingFile->id,
                ]);

                return;
            }

            $imageFile = $this->processAndStoreImage($tempContent, $contentHash);
            $this->linkUserImage($userImage, $imageFile);
            $this->deleteTempFile($tempPath);

            Log::info('ProcessImageJob: Processed new image', [
                'user_image_id' => $this->userImageId,
                'image_file_id' => $imageFile->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessImageJob: Attempt failed', [
                'user_image_id' => $this->userImageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Обработка ошибки после исчерпания всех попыток.
     */
    public function failed(?\Throwable $exception): void
    {
        $userImage = UserImage::find($this->userImageId);

        if ($userImage) {
            $userImage->update(['status' => ImageStatus::Failed]);

            if ($userImage->temp_path) {
                Storage::disk('s3')->delete($userImage->temp_path);
            }
        }

        Log::error('ProcessImageJob: All retries exhausted', [
            'user_image_id' => $this->userImageId,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Скачать временный файл из S3.
     */
    private function downloadTempFile(string $tempPath): string
    {
        $content = Storage::disk('s3')->get($tempPath);

        if (! $content) {
            throw new \RuntimeException("Failed to download temp file: {$tempPath}");
        }

        return $content;
    }

    /**
     * Найти существующий ImageFile по хешу контента для дедупликации.
     */
    private function findExistingImageFile(string $contentHash): ?ImageFile
    {
        return ImageFile::where('content_hash', $contentHash)->first();
    }

    /**
     * Конвертировать изображение в WebP, создать миниатюру, загрузить в S3 и создать запись ImageFile.
     */
    private function processAndStoreImage(string $tempContent, string $contentHash): ImageFile
    {
        $image = Image::read($tempContent);

        $webpContent = $image->toWebp(85)->toString();
        $thumbnailContent = $image->coverDown(200, 200)->toWebp(85)->toString();

        $data = new CreateImageFileDTO(
            contentHash: $contentHash,
            storagePath: 'images/'.substr($contentHash, 0, 2).'/'.$contentHash.'.webp',
            thumbnailPath: 'thumbnails/'.substr($contentHash, 0, 2).'/'.$contentHash.'.webp',
            mimeType: 'image/webp',
            size: strlen($webpContent),
            width: $image->width(),
            height: $image->height(),
        );

        Storage::disk('s3')->put($data->storagePath, $webpContent);
        Storage::disk('s3')->put($data->thumbnailPath, $thumbnailContent);

        return $this->findOrCreateImageFile($data);
    }

    /**
     * Создать запись ImageFile с обработкой race condition при дублировании content_hash.
     */
    private function findOrCreateImageFile(CreateImageFileDTO $data): ImageFile
    {
        try {
            return ImageFile::create([
                'content_hash' => $data->contentHash,
                'storage_path' => $data->storagePath,
                'thumbnail_path' => $data->thumbnailPath,
                'mime_type' => $data->mimeType,
                'size' => $data->size,
                'width' => $data->width,
                'height' => $data->height,
            ]);
        } catch (UniqueConstraintViolationException) {
            /** @var ImageFile $existing */
            $existing = ImageFile::where('content_hash', $data->contentHash)->firstOrFail();

            // Удалить дублирующие файлы из S3, загруженные этой задачей
            Storage::disk('s3')->delete($data->storagePath);
            Storage::disk('s3')->delete($data->thumbnailPath);

            return $existing;
        }
    }

    /**
     * Привязать UserImage к ImageFile и отметить как готовое.
     */
    private function linkUserImage(UserImage $userImage, ImageFile $imageFile): void
    {
        $userImage->update([
            'image_file_id' => $imageFile->id,
            'status' => ImageStatus::Ready,
            'temp_path' => null,
        ]);
    }

    private function deleteTempFile(string $tempPath): void
    {
        Storage::disk('s3')->delete($tempPath);
    }
}
