<?php
namespace chanfuku\ApiAsyncExecutor;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Executor
{
    private $requests = [];
    private $responses = [];

    public function addRequest(Request $request): void
    {
        $key = $request->getUri()->getPath();
        $this->requests += [$key => $request];
    }

    public function doAsyncExecute(): void
    {
        $client = new Client();
        $pool = new Pool($client, $this->requests, [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) {
                // this is delivered each successful response
                $contents = json_decode($response->getBody()->getContents(), true);
                $this->responses[$index] = $contents;
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
    }

    public function getResponse(string $key)
    {
        return $this->responses[$key];
    }
}
