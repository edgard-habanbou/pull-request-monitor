<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    //

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect('/');
        }

        $repository = new Repository();
        //validate data
        $fields = $request->validate([
            'ownerName' => 'required',
            'repoName' => 'required'
        ]);

        $fields['ownerName'] = strip_tags($fields['ownerName']);
        $fields['repoName'] = strip_tags($fields['repoName']);

        $repository->owner = $request->ownerName;
        $repository->name = $request->repoName;

        $repository->save();

        return redirect('/');
    }
}
