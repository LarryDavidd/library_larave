<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parser;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    public function parse(Request $request, Parser $parser)
    {
        try {
            $parser->clearDatabase();
            $parser->parseAndInsertData($request->pages_num ?? 2);
            
            return response()->json([
                'message' => 'Данные успешно обновлены',
                'count' => $parser->getLastInsertCount()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
