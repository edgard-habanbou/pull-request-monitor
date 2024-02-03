<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\ResponseTrait;
use Revolution\Google\Sheets\Facades\Sheets;

class GoogleServiceController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    // retrieve the data from google sheet
    public function getGoogleSheetValues()
    {
        $getrange = 'A:I';
        $values = Sheets::spreadsheet(config('google.post_spreadsheet_id'))
            ->sheet(config('google.post_sheet_id'))
            ->range($getrange)->all();
        return $values;
    }
    // append new row to google sheet
    public function appendValuesToGoggleSheet()
    {
        $append = [
            'title' => 'Test Title',
            'description' => 'This is dummy title'
        ];
        $appendSheet = Sheets::spreadsheet(config('google.post_spreadsheet_id'))
            ->sheet(config('google.post_sheet_id'))
            ->append([$append]);
    }
}
