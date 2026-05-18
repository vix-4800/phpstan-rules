<?php

declare(strict_types=1);

namespace DisabledSslVerificationFixture;

final class HttpClient
{
    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): void
    {
    }
}

final class StaticHttpClient
{
    /**
     * @param array<string, mixed> $options
     */
    public static function request(string $method, string $url, array $options = []): void
    {
    }
}

/**
 * @param resource $curl
 */
function curlOptions($curl, HttpClient $client, bool $verify): void
{
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $verify);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt_array($curl, [
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    curl_setopt_array($curl, [
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    curl_setopt_array($curl, [
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $client->request('GET', 'https://example.com', ['verify' => false]);
    $client->request('GET', 'https://example.com', ['verify' => true]);
    $client->request('GET', 'https://example.com', ['VERIFY' => false]);
    $client->request('GET', 'https://example.com', ['timeout' => 10]);
    $client->request('GET', 'https://example.com');
    StaticHttpClient::request('GET', 'https://example.com', ['verify' => false]);
}
