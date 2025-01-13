<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopularPlace extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural of the model name
    protected $table = 'popular_places';

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'place_name',
        'city',
        'country',
        'latitude',
        'longitude',
        'image',
    ];
}
