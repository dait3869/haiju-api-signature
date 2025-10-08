<?php

namespace Haiju\ApiSignature\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generate(array $params, ?string $secret = null)
 * @method static bool verify(string $signature, array $params, ?string $secret = null)
 * @method static \Psr\Http\Message\ResponseInterface request(string $method, string $url, array $params = [], array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface get(string $url, array $params = [], array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface post(string $url, array $params = [], array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface put(string $url, array $params = [], array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface delete(string $url, array $params = [], array $options = [])
 * @method static bool verifyTimestamp(int $timestamp)
 *
 * @see \Haiju\ApiSignature\SignatureManager
 */
class ApiSignature extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'api-signature';
    }
}

