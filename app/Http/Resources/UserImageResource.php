<?php

namespace App\Http\Resources;

use App\Enums\ImageStatus;
use App\Models\UserImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property UserImage $resource
 *
 * @mixin UserImage
 */
class UserImageResource extends JsonResource
{
    private const URL_EXPIRATION_MINUTES = 30;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageFile = $this->imageFile;

        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'status' => $this->status,
            'size' => $imageFile?->size,
            'width' => $imageFile?->width,
            'height' => $imageFile?->height,
            'created_at' => $this->created_at,
            'urls' => $this->when($this->status === ImageStatus::Ready && $imageFile !== null, function () use ($imageFile) {
                /** @var \App\Models\ImageFile $imageFile */
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('s3-public');
                $expiration = now()->addMinutes(self::URL_EXPIRATION_MINUTES);

                return [
                    'image' => $disk->temporaryUrl($imageFile->storage_path, $expiration),
                    'thumbnail' => $imageFile->thumbnail_path
                        ? $disk->temporaryUrl($imageFile->thumbnail_path, $expiration)
                        : null,
                ];
            }),
        ];
    }
}
