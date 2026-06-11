<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PhotoPolicy
{
    use HandlesAuthorization;

    /**
     * Determina se l'utente può aggiornare la foto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Photo  $photo
     * @return bool
     */
    public function update(User $user, Photo $photo): bool
    {
        // L'utente può aggiornare la foto solo se è il suo proprietario (user_id corrisponde).
        return $user->id === $photo->user_id;
    }

    /**
     * Determina se l'utente può cancellare la foto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Photo  $photo
     * @return bool
     */
    public function delete(User $user, Photo $photo): bool
    {
        // L'utente può cancellare la foto solo se è il suo proprietario.
        return $user->id === $photo->user_id;
    }
}
