<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReaderFeeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the readers fee dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('readersfee');
    }

    /**
     * Test date method.
     *
     * @return \Illuminate\Http\Response
     */
    public function testDate()
    {
        return response()->json([
            'current_date' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone')
        ]);
    }
}
