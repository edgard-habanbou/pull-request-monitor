<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class PullRequestController extends Controller
{

    private function checkRateLimit($response)
    {
        // Check if we're about to hit the rate limit
        $remainingRequests = $response->getHeaderLine('X-RateLimit-Remaining');
        if ($remainingRequests == 0) {
            $resetTime = $response->getHeaderLine('X-RateLimit-Reset');
            $resetTimeInSeconds = $resetTime - time();
            sleep($resetTimeInSeconds); // Sleep until rate limit resets
        }
    }

    private function fetchPullRequests($parameter, $ownerName, $repoName)
    {
        try {
            // Fetch pull requests from GitHub
            $client = new Client();
            $response = $client->request('GET', 'https://api.github.com/search/issues?q=is:pr+is:open+repo:' . $ownerName . "/" . $repoName . $parameter, [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'Authorization' => 'Bearer ' . env('GITHUB_ACCESS_TOKEN'),
                    'X-GitHub-Api-Version' => '2022-11-28'
                ]
            ]);

            // Check if we're about to hit the rate limit
            $this->checkRateLimit($response);

            // If the request is successful, return the pull requests
            if ($response->getStatusCode() == 200) {
                $pullRequests = json_decode($response->getBody()->getContents(), true);
                return $pullRequests["items"];

                // If the request fails, return an empty array
            } else {
                return [];
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [];
        }
    }

    public function Main($ownerName, $repoName)
    {
        // Fetch pull requests that are older than two weeks
        $twoWeeksAgo = date('Y-m-d', strtotime('-2 weeks'));
        $oldPullRequests = $this->fetchPullRequests("+created:<" . $twoWeeksAgo, $ownerName, $repoName);

        // Fetch pull requests that require review
        $pullRequestsWithReviewRequired = $this->fetchPullRequests("+review:required", $ownerName, $repoName);

        // Fetch pull requests where review status is none
        $pullRequestsWithReviewNone = $this->fetchPullRequests("+review:none", $ownerName, $repoName);

        // Fetch pull requests where review status is success
        $pullRequestsWithReviewSuccess = $this->fetchPullRequests("+review:success", $ownerName, $repoName);


        return response()->json([
            'oldPullRequests' => $oldPullRequests,
            'pullRequestsWithReviewRequired' => $pullRequestsWithReviewRequired,
            'pullRequestsWithReviewNone' => $pullRequestsWithReviewNone,
            'pullRequestsWithReviewSuccess' => $pullRequestsWithReviewSuccess
        ]);
    }

    public function writeToTxtFile($fileName, $data)
    {
        $file = fopen($fileName, "w");
        fwrite($file, $data);
        fclose($file);
    }
}
