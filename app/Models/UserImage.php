<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $image_file_id
 * @property string $original_name
 * @property string $status
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

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
