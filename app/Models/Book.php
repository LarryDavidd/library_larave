<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    protected $table = 'Books';
    protected $primaryKey = 'id';
    protected $fillable = ['title', 'description', 'image_path'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_author', 'book_id', 'author_id');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'book_genre', 'book_id', 'genre_id');
    }

    public function scopeSearch(Builder $query, ?string $title = null, array $authorIds = [], array $genreIds = [])
    {
        return $query->select([
                'Books.id',
                'Books.title',
                'Books.description',
                'Books.image_path'
            ])
            ->when($title, function(Builder $query, string $title) {
                return $query->where(function($q) use ($title) {
                    $q->where('Books.title', 'LIKE', "%{$title}%")
                      ->orWhere('Books.description', 'LIKE', "%{$title}%");
                });
            })
            ->when($authorIds, function(Builder $query, array $authorIds) {
                return $query->whereHas('authors', function($q) use ($authorIds) {
                    $q->whereIn('Authors.id', $authorIds);
                });
            })
            ->when($genreIds, function(Builder $query, array $genreIds) {
                return $query->whereHas('genres', function($q) use ($genreIds) {
                    $q->whereIn('Genres.id', $genreIds);
                });
            })
            ->with(['authors' => function($query) {
                $query->selectRaw("CONCAT(authors.last_name, ' ', authors.first_name, 
                                 IFNULL(CONCAT(' ', authors.middle_name), '') as full_name")
                     ->orderBy('last_name')
                     ->orderBy('first_name');
            }])
            ->with(['genres' => function($query) {
                $query->orderBy('name');
            }])
            ->orderBy('title');
    }
}
