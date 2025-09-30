<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\EpisodesExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class EpisodesController extends Controller
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
     * Show the episodes dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        return view('episodes');
    }

    /**
     * Export episodes data.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        return Excel::download(new EpisodesExport(), 'episodes.xlsx');
    }

    /**
     * Export large episodes data.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportLarge(Request $request)
    {
        return Excel::download(new EpisodesExport(), 'episodes_large.xlsx');
    }

    /**
     * Export episodes to XLSX.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportXlsx(Request $request)
    {
        return Excel::download(new EpisodesExport(), 'episodes.xlsx');
    }
}
