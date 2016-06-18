<?php

namespace DataReaper\Http\Controllers;

use DataReaper\Repositories\MetaRepository;
use Illuminate\Http\Request;

use DataReaper\Http\Requests;

class MetaController extends Controller
{
    /**
     * The meta repository instance.
     *
     * @var MetaRepository
     */
    protected $meta;

    /**
     * Create a new controller instance.
     *
     * @param  MetaRepository  $meta
     * @return void
     */
    public function __construct(MetaRepository $meta)
    {
        $this->meta = $meta;
    }

    public function index(Request $request)
    {
        return response()->json($this->meta->get($request->all()), 200, [], JSON_PRETTY_PRINT)->header('Access-Control-Allow-Origin', '*');
    }
}
