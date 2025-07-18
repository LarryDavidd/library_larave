<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        return Book::with(['authors', 'genres'])->get();
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|array',
            'authors.*' => 'integer|exists:authors,id',
            'genres' => 'nullable|array',
            'genres.*' => 'integer|exists:genres,id'
        ]);

        $books = Book::when($validated['title'] ?? null, function($q, $title) {
                return $q->where('title', 'like', "%$title%");
            })
            ->when($validated['authors'] ?? null, function($q, $authors) {
                return $q->whereHas('authors', fn($q) => $q->whereIn('id', $authors));
            })
            ->when($validated['genres'] ?? null, function($q, $genres) {
                return $q->whereHas('genres', fn($q) => $q->whereIn('id', $genres));
            })
            ->with(['authors', 'genres'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }
}