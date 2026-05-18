<?php

declare(strict_types=1);

namespace HttpClientWithoutTimeoutFixture;

final class Client
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): void
    {
    }
}

final class ApiClient
{
    /**
     * @param array<string, mixed> $options
     */
    public static function request(string $method, string $url, array $options = []): void
    {
    }
}

final class Service
{
    /**
     * @param resource $curl
     * @param resource $otherCurl
     */
    public function run($curl, $otherCurl, Client $client): void
    {
        new Client();
        new Client([]);
        new Client(['timeout' => 10]);
        new Client(['connect_timeout' => 5]);

        $client->request('GET', 'https://example.com');
        $client->request('GET', 'https://example.com', ['timeout' => 10]);
        $client->request('GET', 'https://example.com', ['connect_timeout' => 5]);
        $client->request('GET', 'https://example.com', ['headers' => ['Accept' => 'application/json']]);
        ApiClient::request('GET', 'https://example.com', ['timeout' => 10]);
        ApiClient::request('GET', 'https://example.com');

        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_exec($curl);

        curl_exec($otherCurl);
    }

    /**
     * @param resource $curl
     */
    public function runWithTimeoutArray($curl): void
    {
        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        curl_exec($curl);
    }

    /**
     * @param resource $curl
     */
    public function runWithExecBeforeTimeout($curl): void
    {
        curl_exec($curl);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1000);
        curl_exec($curl);
    }
}

/**
 * @param resource $curl
 */
function runFunction($curl): void
{
    curl_exec($curl);
}
