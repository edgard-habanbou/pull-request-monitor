<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class PullRequestController extends Controller
{
    public function fetchOpenPullRequests()
    {
        try {
            $client = new Client();
            $response = $client->request('GET', 'https://api.github.com/repos/OWNER/REPO/pulls', [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'Authorization' => 'Bearer ' + env('GITHUB_ACCESS_TOKEN'),
                    'X-GitHub-Api-Version' => '2022-11-28'
                ]
            ]);
            return $response->getBody();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
