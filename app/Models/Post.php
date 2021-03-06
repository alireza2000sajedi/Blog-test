<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'created_by',
    ];

    public function createdByUser()
    {
        return $this->belongsTo(User::class,'created_by');
    }
}
