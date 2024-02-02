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
        // Write the data to a file
        $this->writeData("oldPullRequests.txt", $oldPullRequests);

        // Fetch pull requests that require review
        $pullRequestsWithReviewRequired = $this->fetchPullRequests("+review:required", $ownerName, $repoName);
        // Write the data to a file
        $this->writeData("pullRequestsWithReviewRequired.txt", $pullRequestsWithReviewRequired);

        // Fetch pull requests where review status is none
        $pullRequestsWithReviewNone = $this->fetchPullRequests("+review:none", $ownerName, $repoName);
        // Write the data to a file
        $this->writeData("pullRequestsWithReviewNone.txt", $pullRequestsWithReviewNone);

        // Fetch pull requests where review status is success
        $pullRequestsWithReviewSuccess = $this->fetchPullRequests("+review:success", $ownerName, $repoName);
        // Write the data to a file
        $this->writeData("pullRequestsWithReviewSuccess.txt", $pullRequestsWithReviewSuccess);


        return response()->json([
            'oldPullRequests' => $oldPullRequests,
            'pullRequestsWithReviewRequired' => $pullRequestsWithReviewRequired,
            'pullRequestsWithReviewNone' => $pullRequestsWithReviewNone,
            'pullRequestsWithReviewSuccess' => $pullRequestsWithReviewSuccess
        ]);
    }



    private function writeToTxtFile($fileName, $data)
    {
        try {
            // check if file exists
            if (file_exists($fileName)) {
                // if file exists, delete it
                unlink($fileName);
            }
            // create a new file
            $file = fopen($fileName, "w");
            fwrite($file, $data);
            fclose($file);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function writeData($fileName, $pullRequests)
    {
        $data = "";
        foreach ($pullRequests as $pullRequest) {
            $data .= "ID: " . $pullRequest["id"] . "\n";
            $data .= "Title: " . $pullRequest["title"] . "\n";
            $data .= "URL: " . $pullRequest["html_url"] . "\n";
            $data .= "Created at: " . $pullRequest["created_at"] . "\n";
            $data .= "Updated at: " . $pullRequest["updated_at"] . "\n";
            $data .= "User: " . $pullRequest["user"]["login"] . "\n";
            $data .= "User URL: " . $pullRequest["user"]["html_url"] . "\n";
            $data .= "----------------------------------------------------------------------\n";
        }
        $this->writeToTxtFile($fileName, $data);
    }
}
