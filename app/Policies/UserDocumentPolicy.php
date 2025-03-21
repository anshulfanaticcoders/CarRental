<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UserDocument  $userDocument
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, UserDocument $userDocument)
    {
        return $user->id === $userDocument->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UserDocument  $userDocument
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, UserDocument $userDocument)
    {
        return $user->id === $userDocument->user_id;
    }
}