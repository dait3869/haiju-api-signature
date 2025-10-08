<?php

namespace Haiju\ApiSignature;

use Illuminate\Support\ServiceProvider;
use Haiju\ApiSignature\Middleware\VerifySignature;

class SignatureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 合并配置
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-signature.php',
            'api-signature'
        );

        // 注册单例
        $this->app->singleton('api-signature', function ($app) {
            return new SignatureManager(config('api-signature'));
        });

        // 注册别名
        $this->app->alias('api-signature', SignatureManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/api-signature.php' => config_path('api-signature.php'),
        ], 'api-signature-config');

        // 注册中间件
        $router = $this->app['router'];
        $router->aliasMiddleware('verify.signature', VerifySignature::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['api-signature', SignatureManager::class];
    }
}