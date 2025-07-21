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
            'genres.*' => 'integer|exists:genres,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $page = $validated['page'] ?? 1;

        $result = Book::query()
            ->search(
                $validated['title'] ?? null,
                $validated['authors'] ?? [],
                $validated['genres'] ?? [],
                $perPage
            );


        $result['pagination']['current_page'] = $page;

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['pagination']
        ]);
    }
}