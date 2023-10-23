<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

use Illuminate\Database\Eloquent\Model;

class BusLine extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'city_name', 'bus_line'];
  protected $spatialFields = [
        'bus_line',
    ];
}
