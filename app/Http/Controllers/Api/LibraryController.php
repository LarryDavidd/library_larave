<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Author;
use App\Models\Genre;
use App\Services\Parser;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function getAuthors()
    {
        $authors = Author::all();
        
        return response()->json([
            'success' => true,
            'data' => $authors
        ]);
    }

    public function getGenres()
    {
        $genres = Genre::all();
        
        return response()->json([
            'success' => true,
            'data' => $genres
        ]);
    }

    public function search(Request $request)
    {
        $title = $request->input('title');
        $authorIds = $request->input('authors', []);
        $genreIds = $request->input('genres', []);
        
        $books = Book::when($title, function($query, $title) {
                return $query->where('title', 'like', "%$title%");
            })
            ->when($authorIds, function($query, $authorIds) {
                return $query->whereHas('authors', function($q) use ($authorIds) {
                    $q->whereIn('id', $authorIds);
                });
            })
            ->when($genreIds, function($query, $genreIds) {
                return $query->whereHas('genres', function($q) use ($genreIds) {
                    $q->whereIn('id', $genreIds);
                });
            })
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    public function parseBooks(Request $request, Parser $parser)
    {
        $pages_num = $request->input('pages_num', 2);
        
        try {
            $parser->clearDatabase();
            $parser->parseAndInsertData($pages_num);
            
            return response()->json([
                'success' => true,
                'message' => 'База успешно обновлена',
                'count' => $parser->getLastInsertCount()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при парсинге: ' . $e->getMessage()
            ], 500);
        }
    }
}
