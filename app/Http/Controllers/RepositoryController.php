<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    //

    public function index()
    {
        return view('repositories.index');
    }

    public function create()
    {
        return view('repositories.create');
    }

    public function store(Request $request)
    {
        $repository = new Repository();
        //validate data
        $request->validate([
            'owner' => 'required',
            'name' => 'required'
        ]);
        $repository->owner = $request->owner;
        $repository->name = $request->name;

        $repository->save();

        return redirect()->route('repositories.index');
    }
}
