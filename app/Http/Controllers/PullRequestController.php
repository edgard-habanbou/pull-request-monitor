<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class PullRequestController extends Controller
{
    public function fetchPullRequests($parameter)
    {
        $client = new Client();
        $response = $client->request('GET', 'https://api.github.com/search/issues?q=is:pr+is:open+repo:woocommerce/woocommerce' . $parameter, [
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'Authorization' => 'Bearer ' . env('GITHUB_ACCESS_TOKEN'),
                'X-GitHub-Api-Version' => '2022-11-28'
            ]
        ]);
        $pullRequests = json_decode($response->getBody()->getContents(), true);
        return $pullRequests["items"];
    }



    public function Main()
    {
        $twoWeeksAgo = date('Y-m-d', strtotime('-2 weeks'));
        $oldPullRequests = $this->fetchPullRequests("+created:<" . $twoWeeksAgo);
        return response()->json($oldPullRequests);
    }
}
