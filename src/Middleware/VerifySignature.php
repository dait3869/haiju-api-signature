<?php

namespace Haiju\ApiSignature\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Haiju\ApiSignature\Exceptions\SignatureException;
use Haiju\ApiSignature\SignatureManager;

class VerifySignature
{
    private array $config;

    public function __construct(protected SignatureManager $signature)
    {
        $this->config = config('api-signature');
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     * @throws SignatureException
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->inExcept($request)) {
            return $next($request);
        }

        if (! $signature = $request->header($this->config['header_signature'])) {
            throw new SignatureException('签名信息缺失');
        }

        if (! $timestamp = $request->header($this->config['header_timestamp'])) {
            throw new SignatureException('时间戳缺失');
        }

        if (! $nonce = $request->header($this->config['header_nonce'])) {
            throw new SignatureException('随机数缺失');
        }

        // 验证时间戳
        if (! $this->signature->verifyTimestamp((int)$timestamp)) {
            throw new SignatureException('请求已过期');
        }

        // 验证随机数（防重放攻击）
        if (! $this->signature->verifyNonce($nonce)) {
            throw new SignatureException('请勿重复请求');
        }

        $params = $this->getRequestParams($request);
        $params['timestamp'] = $timestamp;
        $params['nonce'] = $nonce;

        if (! $this->signature->verify($signature, $params)) {
            throw new SignatureException('签名验证失败');
        }

        return $next($request);
    }

    protected function getRequestParams(Request $request): array
    {
        $params = [];

        // Query
        $params = array_merge($params, $request->query());

        // Body
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $contentType = $request->header('Content-Type', '');
            
            if (str_contains($contentType, 'application/json')) {
                $params = array_merge($params, $request->json()->all());
            } else {
                $params = array_merge($params, $request->post());
            }
        }

        return $params;
    }

    protected function inExcept(Request $request): bool
    {
        $except = config('api-signature.except', []);
        
        foreach ($except as $pattern) {
            if ($pattern !== '/') {
                $pattern = trim($pattern, '/');
            }

            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}