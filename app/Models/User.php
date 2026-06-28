<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\TranslateAttributes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TranslateAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'role',
        'password',
        'phone',
        'country',
        'city',
        'google_id',
        'google_token',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
    ];

    public function getAvatarAttribute($value)
    {
        if (!$value) {
            return asset('images/user-icon.png');
        } else {
            if (file_exists(public_path('images/' . $value))) {
                return asset('images/' . $value);
            } else {
                return asset('storage/avatars/' . $value);
            }
        }
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites');
    }
}
