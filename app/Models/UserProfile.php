<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'postal_code',
        'date_of_birth',
        'gender',
        'about',
        'tax_identification',
        'title',
        'languages',
        'currency',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
