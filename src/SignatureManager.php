<?php

namespace Haiju\ApiSignature;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SignatureManager
{
    protected array $config;
    protected Client $client;

    public function __construct(array $config = [])
    {
        $this->config = array_merge(config('api-signature', []), $config);
        $this->client = new Client($this->config['http'] ?? []);
    }

    /**
     * 生成签名
     *
     * @param array $params 参数数组
     * @param string|null $secret 密钥（可选，默认使用配置）
     * @return string
     */
    public function generate(array $params, ?string $secret = null): string
    {
        $secret = $secret ?? $this->config['secret'];
        
        // 移除空值
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });

        // 按键名排序（字符串比较，保证跨语言一致性）
        ksort($params, SORT_STRING);

        // 拼接参数
        $signString = $this->buildSignature($params);

        // 加入密钥
        $signString .= '&secret=' . $secret;

        // 生成签名
        return $this->hash($signString);
    }

    /**
     * 验证签名
     *
     * @param string $signature 待验证的签名
     * @param array $params 参数数组
     * @param string|null $secret 密钥（可选，默认使用配置）
     * @return bool
     */
    public function verify(string $signature, array $params, ?string $secret = null): bool
    {
        $expectedSignature = $this->generate($params, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * 发送带签名的 HTTP 请求
     *
     * @param string $method HTTP 方法 (GET, POST, PUT, DELETE 等)
     * @param string $url 请求 URL
     * @param array $params 请求参数
     * @param array $options 额外的 Guzzle 选项
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $url, array $params = [], array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $method = strtoupper($method);

        // 生成时间戳和随机字符串
        $timestamp = time();
        $nonce = Str::random(32);

        // 准备签名参数（包含业务参数、时间戳、随机字符串）
        $signParams = array_merge($params, [
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ]);

        // 生成签名
        $signature = $this->generate($signParams);

        // 构建请求头
        $headers = array_merge($options['headers'] ?? [], [
            $this->config['header_signature'] => $signature,
            $this->config['header_timestamp'] => $timestamp,
            $this->config['header_nonce'] => $nonce,
        ]);

        // 构建请求选项
        $requestOptions = array_merge($options, [
            'headers' => $headers,
        ]);

        if (in_array($method, ['GET', 'DELETE'])) {
            $requestOptions['query'] = $params;
        } else {
            $requestOptions['json'] = $params;
        }

        return $this->client->request($method, $url, $requestOptions);
    }

    public function get(string $url, array $params = [], array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->request('GET', $url, $params, $options);
    }

    public function post(string $url, array $params = [], array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->request('POST', $url, $params, $options);
    }

    public function put(string $url, array $params = [], array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->request('PUT', $url, $params, $options);
    }

    public function delete(string $url, array $params = [], array $options = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->request('DELETE', $url, $params, $options);
    }

    protected function buildSignature(array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $parts[] = $key . '=' . $value;
        }
        return implode('&', $parts);
    }

    protected function hash(string $data): string
    {
        $algorithm = $this->config['algorithm'] ?? 'sha256';

        return match ($algorithm) {
            'md5' => md5($data),
            'sha1' => sha1($data),
            default => hash('sha256', $data),
        };
    }

    public function verifyTimestamp(int $timestamp): bool
    {
        $expire = $this->config['expire'] ?? 300;

        return abs(time() - $timestamp) <= $expire;
    }

    /**
     * 验证随机数（防重放攻击）
     */
    public function verifyNonce(string $nonce): bool
    {
        $cacheKey = 'api_signature_nonce:' . $nonce;

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::put($cacheKey, true, $this->config['expire'] ?? 300);

        return true;
    }
}