<?php

namespace App\Http\Controllers;

use App\Models\Repository;
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
        // Set the maximum execution time to 1 hour
        ini_set("max_execution_time", 3600);

        try {
            $pullRequests = [];
            $page = 1;
            $perPage = 50;

            $client = new Client([
                'timeout' => 500,
            ]);
            do {
                $response = $client->request('GET', 'https://api.github.com/search/issues?q=is:pr+is:open+repo:' . $ownerName . "/" . $repoName . $parameter . '&per_page=' . $perPage . '&page=' . $page, [
                    'headers' => [
                        'Accept' => 'application/vnd.github+json',
                        'Authorization' => 'Bearer ' . env('GITHUB_ACCESS_TOKEN'),
                        'X-GitHub-Api-Version' => '2022-11-28'
                    ]
                ]);

                // Check if we're about to hit the rate limit
                $this->checkRateLimit($response);

                // If the request is successful, append pull requests to the array
                if ($response->getStatusCode() == 200) {
                    $responseData = json_decode($response->getBody()->getContents(), true);
                    $pullRequests = array_merge($pullRequests, $responseData["items"]);
                }

                $page++;

                // Continue looping until there are no more pages
            } while ($response->hasHeader('Link') && strpos($response->getHeader('Link')[0], 'rel="next"') !== false);

            return $pullRequests;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [];
        }
    }


    public function Main()
    {
        // Fetch all repositories
        $repositories = Repository::all();


        foreach ($repositories as $repository) {
            // Get the owner and repository name
            $ownerName = $repository->owner;
            $repoName = $repository->name;

            // Fetch pull requests that are older than two weeks
            $twoWeeksAgo = date('Y-m-d', strtotime('-2 weeks'));
            $oldPullRequests = $this->fetchPullRequests("+created:<" . $twoWeeksAgo, $ownerName, $repoName);
            // Write the data to a file
            $this->writeData("oldPullRequests.txt", $oldPullRequests, $ownerName, $repoName);

            // Fetch pull requests that require review
            $pullRequestsWithReviewRequired = $this->fetchPullRequests("+review:required", $ownerName, $repoName);
            // Write the data to a file
            $this->writeData("pullRequestsWithReviewRequired.txt", $pullRequestsWithReviewRequired, $ownerName, $repoName);

            // Fetch pull requests where review status is none
            $pullRequestsWithReviewNone = $this->fetchPullRequests("+review:none", $ownerName, $repoName);
            // Write the data to a file
            $this->writeData("pullRequestsWithReviewNone.txt", $pullRequestsWithReviewNone, $ownerName, $repoName);

            // Fetch pull requests where review status is success
            $pullRequestsWithReviewSuccess = $this->fetchPullRequests("+review:success", $ownerName, $repoName);
            // Write the data to a file
            $this->writeData("pullRequestsWithReviewSuccess.txt", $pullRequestsWithReviewSuccess, $ownerName, $repoName);
        }


        return response()->json([
            'message' => 'Data has been written to files'
        ]);
    }



    private function writeToTxtFile($fileName, $data, $ownerName, $repoName)
    {
        try {
            $directory = public_path("/pull-requests/{$ownerName}-{$repoName}");

            // Check if directory exists, if not, create it
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            // Check if file exists within the directory
            $filePath = $directory . '/' . $fileName;
            if (file_exists($filePath)) {
                // if file exists, delete it
                unlink($filePath);
            }

            // create a new file
            $file = fopen($filePath, "w");
            fwrite($file, $data);
            fclose($file);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function writeData($fileName, $pullRequests, $ownerName, $repoName)
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
        $this->writeToTxtFile($fileName, $data,  $ownerName, $repoName);
    }
}
