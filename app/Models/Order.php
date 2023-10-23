<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_ids',
        'top_passenger_count',
        'current_passenger_count',
        'destination_vertices_id',
        'estimated_price',
        'is_hurry',
        'status',
        'genders'
    ];
}
