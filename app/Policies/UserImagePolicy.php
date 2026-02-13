<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserImage;

class UserImagePolicy
{
    public function delete(User $user, UserImage $userImage): bool
    {
        return $user->id === $userImage->user_id;
    }
}
