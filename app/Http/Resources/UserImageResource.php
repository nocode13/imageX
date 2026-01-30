<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @property int $id
 * @property string $original_name
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property-read \App\Models\ImageFile|null $imageFile
 */
class UserImageResource extends JsonResource
{
    private const URL_EXPIRATION_MINUTES = 60;

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
            'urls' => $this->when($this->status === 'ready' && $imageFile !== null, fn () => [
                'image' => URL::temporarySignedRoute(
                    'images.show',
                    now()->addMinutes(self::URL_EXPIRATION_MINUTES),
                    ['id' => $this->id]
                ),
                'thumbnail' => URL::temporarySignedRoute(
                    'images.thumbnail',
                    now()->addMinutes(self::URL_EXPIRATION_MINUTES),
                    ['id' => $this->id]
                ),
            ]),
        ];
    }
}
