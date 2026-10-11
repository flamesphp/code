<?php

declare (strict_types=1);
namespace PHPStan\Internal;

use Flames\Code\Upgrade\ThirdParty\GuzzleHttp\Client;
use Flames\Code\Upgrade\ThirdParty\GuzzleHttp\RequestOptions;
use PHPStan\DependencyInjection\AutowiredService;
use function extension_loaded;
#[AutowiredService]
final class HttpClientFactory
{
    private int $timeout;
    private int $connectTimeout;
    public function __construct(int $timeout = 30, int $connectTimeout = 10)
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }
    /**
     * @param array<mixed> $config
     *
     * @see \GuzzleHttp\RequestOptions
     */
    public function createClient(array $config): Client
    {
        if (!isset($config['headers']['Accept-Encoding']) && extension_loaded('zlib')) {
            $config['headers']['Accept-Encoding'] = 'gzip,deflate';
        }
        $defaults = [RequestOptions::TIMEOUT => $this->timeout, RequestOptions::CONNECT_TIMEOUT => $this->connectTimeout];
        return new Client($config + $defaults);
    }
}
