<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edges extends Model
{
    use HasFactory;
    protected $fillable = ['source_vertex_id', 'target_vertex_id', 'weight', 'distance', 'status', 'time'];

    public function sourceVertex()
    {
        return $this->belongsTo(Vertices::class, 'source_vertex_id');
    }

    public function targetVertex()
    {
        return $this->belongsTo(Vertices::class, 'target_vertex_id');
    }
}
