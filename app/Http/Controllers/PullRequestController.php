<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class PullRequestController extends Controller
{
    public function fetchOpenPullRequests()
    {
        try {
            $client = new Client();
            $response = $client->request('GET', 'https://api.github.com/repos/woocommerce/woocommerce/pulls', [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'Authorization' => 'Bearer ' . env('GITHUB_ACCESS_TOKEN'),
                    'X-GitHub-Api-Version' => '2022-11-28'
                ]
            ]);
            $pullRequests = json_decode($response->getBody()->getContents(), true);
            return $pullRequests;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchOldOpenPullRequests($pullRequests)
    {
        $oldPullRequests = [];
        foreach ($pullRequests as $pullRequest) {
            if (strtotime($pullRequest['created_at']) < strtotime('-14 days')) {
                $oldPullRequests[] = $pullRequest;
            }
        }
        return $oldPullRequests;
    }

    public function Main()
    {
        $pullRequests = $this->fetchOpenPullRequests();
        $oldPullRequests = $this->fetchOldOpenPullRequests($pullRequests);
        return response()->json($oldPullRequests);
    }
}
