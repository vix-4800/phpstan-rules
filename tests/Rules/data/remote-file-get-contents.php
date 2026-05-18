<?php

declare(strict_types=1);

namespace RemoteFileGetContentsFixture;

function remoteReads(string $url, string $path): void
{
    file_get_contents(__FILE__);
    file_get_contents('/tmp/local-file.txt');
    file_get_contents('https://example.com/data.json');
    file_get_contents('http://example.com/data.json');
    file_get_contents('HTTPS://example.com/data.json');
    file_get_contents('ftp://example.com/data.json');
    file_get_contents('https://example.com/' . $path);
    file_get_contents('/tmp/' . $path);
    file_get_contents($url);
    file_get_contents($path);
}
