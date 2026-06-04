<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 *pengguna yang terdaftar di aplikasi.
 * Authenticatable biar bisa dipake untuk login/logout.
 * HasApiTokens → token-based auth untuk mobile app (Flutter).
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast otomatis untuk tipe data
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function postingan()
{
    return $this->hasMany(\App\Models\Postingan::class);
}
}
