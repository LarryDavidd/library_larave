<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use Exception;
use App\Models\Book;
use App\Models\Author;
use App\Models\Genre;

class Parser 
{
    private Client $httpClient;
    private int $lastInsertCount = 0;
    private string $baseUrl = 'https://litlife.club';
    
    public function __construct() 
    {
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ]
        ]);
    }
    
    public function clearDatabase(): void 
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            
            DB::table('book_author')->truncate();
            DB::table('book_genre')->truncate();
            Book::truncate();
            Author::truncate();
            Genre::truncate();
            
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Exception $e) {
            throw new Exception("Database clearing failed: " . $e->getMessage());
        }
    }
    
    public function parseAndInsertData(int $pages = 2): void 
    {
        for ($page = 1; $page <= $pages; $page++) {
            $this->parsePage($page);
        }
        $this->lastInsertCount = Book::count();
    }
    
    private function parsePage(int $page): void 
    {
        $url = "/popular_books/year?page={$page}";
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);
        
        $crawler->filter('div.card-body')->each(function (Crawler $book) {
            try {
                $imageUrl = $this->getImageUrl($book);
                $bookData = $this->parseBookData($book);
                
                if ($bookData && $imageUrl) {
                    $this->saveBookWithRelations($bookData, $imageUrl);
                }
            } catch (Exception $e) {
                logger()->error("Error parsing book: " . $e->getMessage());
            }
        });
    }
    
    private function fetchHtml(string $url): string 
    {
        $response = $this->httpClient->get($url);
        return (string)$response->getBody();
    }
    
    private function getImageUrl(Crawler $book): ?string 
    {
        $img = $book->filter('img')->first();
        if ($img->count() === 0) return null;
        
        $src = $img->attr('src') ?? $img->attr('data-src') ?? $img->attr('data-srcset');
        if (!$src) return null;
        
        return strpos($src, '//') === 0 ? 'https:' . $src : $src;
    }
    
    private function parseBookData(Crawler $book): array 
    {
        return array_filter([
            'title' => $this->parseBookTitle($book),
            'genres' => $this->parseGenres($book),
            'authors' => $this->parseAuthors($book),
            'description' => $this->parseDescription($book)
        ], fn($value) => $value !== null);
    }
    
    private function parseBookTitle(Crawler $book): ?string 
    {
        $title = $book->filter('h3.break-words.h5 a');
        return $title->count() ? trim($title->text()) : null;
    }
    
    private function parseGenres(Crawler $book): array 
    {
        $text = $book->filterXPath('//*[contains(text(), "Жанры:")]')->text();
        
        if (preg_match('/Жанры:\s*(.+)/u', $text, $matches)) {
            return array_filter(
                preg_split('/\s*[,\/]\s*/u', trim($matches[1])),
                fn($genre) => !empty($genre)
            );
        }
        
        return [];
    }
    
    private function parseAuthors(Crawler $book): array 
    {
        $authors = [];
        $book->filter('a.author.name')->each(function (Crawler $author) use (&$authors) {
            $authors[] = [
                'name' => trim($author->text()),
                'link' => $author->attr('href')
            ];
        });
        return $authors;
    }
    
    private function parseDescription(Crawler $book): ?string 
    {
        $desc = $book->filter('div.mt-3');
        return $desc->count() ? trim(str_replace('далее', '', $desc->text())) : null;
    }
    
    private function getOrCreateAuthor(array $authorInfo): int 
    {
        $nameParts = preg_split('/\s+/', trim($authorInfo['name']));
        $nameParts = array_filter($nameParts);
        
        $firstName = $lastName = $middleName = null;
        
        switch (count($nameParts)) {
            case 1: $firstName = $nameParts[0]; break;
            case 2: 
                $lastName = $nameParts[0]; 
                $firstName = $nameParts[1]; 
                break;
            case 3: 
                $lastName = $nameParts[0]; 
                $firstName = $nameParts[1]; 
                $middleName = $nameParts[2]; 
                break;
        }
        
        $author = Author::firstOrCreate(
            [
                'first_name' => $firstName,
                'last_name' => $lastName
            ],
            [
                'middle_name' => $middleName
            ]
        );
        
        return $author->id;
    }
    
    private function getOrCreateGenre(string $genreName): int 
    {
        $genre = Genre::firstOrCreate(
            ['name' => $genreName]
        );
        
        return $genre->id;
    }
    
    private function saveBookWithRelations(array $bookData, string $imageUrl): void 
    {
        DB::transaction(function() use ($bookData, $imageUrl) {
            
            $book = Book::create([
                'title' => $bookData['title'],
                'description' => $bookData['description'] ?? null,
                'image_path' => $imageUrl
            ]);
            
            $authorIds = [];
            foreach ($bookData['authors'] as $author) {
                $authorIds[] = $this->getOrCreateAuthor($author);
            }
            $book->authors()->syncWithoutDetaching($authorIds);
            
            $genreIds = [];
            foreach ($bookData['genres'] as $genre) {
                $genreIds[] = $this->getOrCreateGenre($genre);
            }
            $book->genres()->syncWithoutDetaching($genreIds);
        });
    }
    
    private function saveImage(string $url): string
    {
        try {
            $contents = file_get_contents($url);
            $name = 'books/' . md5($url) . '.jpg';
            Storage::disk('public')->put($name, $contents);
            return $name;
        } catch (Exception $e) {
            logger()->error("Failed to save image: " . $e->getMessage());
            return '';
        }
    }
    
    public function getLastInsertCount(): int 
    {
        return $this->lastInsertCount;
    }
}