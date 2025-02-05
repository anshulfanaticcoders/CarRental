<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'email_verified_at',
        'phone_verified_at',
        'remember_token',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'role' => 'string',
        'status' => 'string',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vendor_id', 'user_id');
    }
    public function vendorProfile()
    {
        return $this->hasOne(VendorProfile::class);
    }

    /**
     * Get the vendor document associated with the user.
     */
    public function vendorDocument()
    {
        return $this->hasOne(VendorDocument::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Vehicle::class, 'user_vehicle', 'user_id', 'vehicle_id')->withTimestamps();
    }

}
