<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Revolution\Google\Sheets\Facades\Sheets;


class GoogleSheetsController extends Controller
{

    public function addToGoogleSheet(Request $request)
    {

        $data = $request->data;
        Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))
            ->sheet($request->sheet_name)
            ->append($data);
        return response()->json(['message' => 'Data added to Google Sheet']);
    }

    public function redoSheet(Request $request)
    {
        Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))
            ->sheet($request->sheet_name)
            ->clear();
        return response()->json(['message' => 'Sheet cleared']);
    }
}
