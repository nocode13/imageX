<?php

namespace App\Models;

use App\Enums\ImageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $image_file_id
 * @property string $original_name
 * @property ImageStatus $status
 * @property string|null $temp_path
 * @property \Carbon\Carbon $created_at
 * @property-read User $user
 * @property-read ImageFile|null $imageFile
 */
class UserImage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'image_file_id',
        'original_name',
        'status',
        'temp_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'status' => ImageStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ImageFile, $this>
     */
    public function imageFile(): BelongsTo
    {
        return $this->belongsTo(ImageFile::class);
    }
}
