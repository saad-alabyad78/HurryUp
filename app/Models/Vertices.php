<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class Vertices extends Model
{
    use HasFactory, SpatialTrait;

    protected $fillable = ['bus_line_id','name' ,  'point', 'is_busy'];

    protected $spatialFields = [
        'point',
    ];

    public function busLine()
    {
        return $this->belongsTo(BusLine::class);
    }
    public function sourceEdges()
    {
        return $this->hasMany(Edges::class, 'source_vertex_id');
    }

    public function targetEdges()
    {
        return $this->hasMany(Edges::class, 'target_vertex_id');
    }
}
