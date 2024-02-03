<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Revolution\Google\Sheets\Facades\Sheets;
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

    private function redoSheet($sheet_name)
    {
        try {
            Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))
                ->sheet($sheet_name)
                ->clear();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkIfSheetExists($sheet_name)
    {
        try {
            // Check if the sheet exists
            $sheets = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheetList();
            foreach ($sheets as $sheet) {
                if ($sheet == $sheet_name) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function addToGoogleSheet($data, $sheet_name)
    {
        try {
            // Check if the sheet exists, if not, create it
            if (!$this->checkIfSheetExists($sheet_name)) {
                Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))
                    ->addSheet($sheet_name);
                Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))
                    ->sheet($sheet_name)
                    ->append([["ID", "Title", "URL", "Created at", "Updated at", "User", "User URL"]]);
            } else {
                $this->redoSheet($sheet_name);
            }

            // Add data to the sheet
            Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))
                ->sheet($sheet_name)
                ->append($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function writeData($fileName, $pullRequests, $ownerName, $repoName)
    {
        $data = [];
        foreach ($pullRequests as $pullRequest) {
            // $data .= "ID: " . $pullRequest["id"] . "\n";
            // $data .= "Title: " . $pullRequest["title"] . "\n";
            // $data .= "URL: " . $pullRequest["html_url"] . "\n";
            // $data .= "Created at: " . $pullRequest["created_at"] . "\n";
            // $data .= "Updated at: " . $pullRequest["updated_at"] . "\n";
            // $data .= "User: " . $pullRequest["user"]["login"] . "\n";
            // $data .= "User URL: " . $pullRequest["user"]["html_url"] . "\n";
            // $data .= "----------------------------------------------------------------------\n";
            $data[] = [
                $pullRequest["id"],
                $pullRequest["title"],
                $pullRequest["html_url"],
                $pullRequest["created_at"],
                $pullRequest["updated_at"],
                $pullRequest["user"]["login"],
                $pullRequest["user"]["html_url"]
            ];
        }
        // $this->writeToTxtFile($fileName, $data,  $ownerName, $repoName);
        $this->addToGoogleSheet($data, $ownerName . "-" . $repoName . "-" . $fileName);
    }

    private function processRepository($repository)
    {
        // Get the owner and repository name
        $ownerName = $repository->owner;
        $repoName = $repository->name;

        // Fetch and write different types of pull requests
        $this->fetchAndWritePullRequests("oldPullRequests", "+created:<" . $this->getTwoWeeksAgo(), $ownerName, $repoName);
        $this->fetchAndWritePullRequests("pullRequestsWithReviewRequired", "+review:required", $ownerName, $repoName);
        $this->fetchAndWritePullRequests("pullRequestsWithReviewNone", "+review:none", $ownerName, $repoName);
        $this->fetchAndWritePullRequests("pullRequestsWithReviewSuccess", "+review:success", $ownerName, $repoName);
    }

    private function fetchAndWritePullRequests($fileName, $parameter, $ownerName, $repoName)
    {
        $pullRequests = $this->fetchPullRequests($parameter, $ownerName, $repoName);
        $this->writeData($fileName, $pullRequests, $ownerName, $repoName);
    }

    private function getTwoWeeksAgo()
    {
        return date('Y-m-d', strtotime('-2 weeks'));
    }

    private function fetchRepositories()
    {
        return Repository::all();
    }

    public function Main()
    {
        // Fetch all repositories
        $repositories = $this->fetchRepositories();

        // Process each repository
        foreach ($repositories as $repository) {
            $this->processRepository($repository);
        }

        return response()->json([
            'message' => 'Data has been written to files'
        ]);
    }
}
