<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Genre extends Model
{
    use HasFactory;

    protected $table = 'genres';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['name'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_genre', 'genre_id', 'id');
    }

    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:genres'
        ];
    }
}