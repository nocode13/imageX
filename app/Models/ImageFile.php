<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $content_hash
 * @property string $storage_path
 * @property string|null $thumbnail_path
 * @property string $mime_type
 * @property int $size
 * @property int $width
 * @property int $height
 * @property \Carbon\Carbon $created_at
 */
class ImageFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'content_hash',
        'storage_path',
        'thumbnail_path',
        'mime_type',
        'size',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<UserImage, $this>
     */
    public function userImages(): HasMany
    {
        return $this->hasMany(UserImage::class);
    }
}
