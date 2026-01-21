<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\ChatStatus; // Added import
use Illuminate\Database\Eloquent\Relations\HasOne; // Added import
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProviderFavorite;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_code',
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

    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class);
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

    public function documents()
{
    return $this->hasMany(UserDocument::class);
}

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Vehicle::class, 'user_vehicle', 'user_id', 'vehicle_id')->withTimestamps();
    }

    public function activityLogs()
{
    return $this->hasMany(ActivityLog::class);
}
public function bookings()
{
    return $this->hasMany(Booking::class);
}

 /**
     * Get the chat status associated with the user.
     */
    public function chatStatus(): HasOne
    {
        return $this->hasOne(ChatStatus::class);
    }

    public function providerFavorites(): HasMany
    {
        return $this->hasMany(ProviderFavorite::class);
    }
}
