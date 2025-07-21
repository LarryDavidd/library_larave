<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Author extends Model
{
    use HasFactory;

    protected $table = 'authors';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'first_name',
        'last_name', 
        'middle_name'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_author', 'author_id', 'id');
    }
}