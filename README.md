# Haiju API Signature

[![Latest Stable Version](https://poser.pugx.org/haiju/api-signature/v/stable)](https://packagist.org/packages/haiju/api-signature)
[![Total Downloads](https://poser.pugx.org/haiju/api-signature/downloads)](https://packagist.org/packages/haiju/api-signature)
[![License](https://poser.pugx.org/haiju/api-signature/license)](https://packagist.org/packages/haiju/api-signature)

Laravel API 签名验证中间件，为 API 通信提供安全的签名机制。

## 特性

- ✅ 支持多种签名算法（MD5, SHA1, SHA256）
- ✅ 自动参数排序和签名生成
- ✅ 时间戳验证防止重放攻击
- ✅ 内置 HTTP 客户端支持
- ✅ 门面和依赖注入两种使用方式
- ✅ 灵活的中间件配置
- ✅ Laravel 11 原生支持

## 环境要求

- PHP >= 8.2
- Laravel >= 11.0

## 安装

通过 Composer 安装：

```bash
composer require haiju/api-signature
```

## 配置

### 配置方式对比

| 配置内容 | .env 文件 | 发布配置文件 |
|---------|----------|------------|
| 签名算法、密钥、过期时间 | ✅ **推荐** | ✅ 支持 |
| HTTP 超时设置 | ✅ **推荐** | ✅ 支持 |
| SSL 证书验证 | ✅ **推荐** | ✅ 支持 |
| 自定义 Header 字段名 | ❌ 不支持 | ✅ **需要** |
| 排除验签的路由 | ❌ 不支持 | ✅ **需要** |
| HTTP 代理、认证等高级配置 | ❌ 不支持 | ✅ **需要** |

**选择建议：**
- 🎯 **90% 的场景**：使用 .env 文件即可，无需发布配置
- 🔧 **需要自定义规则**：发布配置文件

**快速决策：**
```
是否需要修改以下任一项？
├─ 自定义 Header 字段名 ────→ 发布配置文件
├─ 排除特定路由不验签 ────→ 发布配置文件
├─ HTTP 代理/认证配置 ────→ 发布配置文件
└─ 仅修改密钥/算法/超时 ──→ 使用 .env 即可 ✅
```

### 方式 1：使用 .env 文件（推荐）

**无需发布配置文件**，直接在 `.env` 中配置即可：

```env
# 基础配置
API_SIGNATURE_ALGORITHM=sha256
API_SIGNATURE_SECRET=your-secret-key
API_SIGNATURE_EXPIRE=300

# HTTP 客户端配置（可选）
API_SIGNATURE_HTTP_TIMEOUT=30
API_SIGNATURE_HTTP_CONNECT_TIMEOUT=10
API_SIGNATURE_HTTP_VERIFY=true  # ⚠️ 生产环境必须为 true
```

这种方式适合**大多数场景**，无需修改代码，环境变量覆盖默认配置。

### 方式 2：发布配置文件（高级自定义）

如果需要修改以下内容，可以发布配置文件：
- 自定义 Header 字段名（如 `X-Signature`）
- 配置排除验签的路由
- 添加复杂的 HTTP 客户端配置（代理、认证等）

**发布配置文件：**

```bash
php artisan vendor:publish --tag=api-signature-config
```

这将在 `config/api-signature.php` 创建配置文件，您可以直接修改：

```php
// config/api-signature.php
return [
    // 签名算法（支持：md5, sha1, sha256）
    'algorithm' => 'sha256',
    
    // 签名密钥
    'secret' => 'your-secret-key',
    
    // 签名有效期（秒）
    'expire' => 300,
    
    // 自定义 Header 名称
    'header_signature' => 'X-My-Signature',
    'header_timestamp' => 'X-My-Timestamp',
    'header_nonce' => 'X-My-Nonce',
    
    // 排除验签的路由
    'except' => [
        'api/public/*',
        'api/health',
    ],
    
    // HTTP 客户端高级配置
    'http' => [
        'timeout' => 60,
        'connect_timeout' => 10,
        'verify' => true,
        'proxy' => 'http://proxy.example.com:8080',
        'auth' => ['username', 'password'],
    ],
];
```

### 配置优先级

```
1. 应用的 config/api-signature.php（发布后，优先级最高）
2. 包的默认配置文件
3. .env 环境变量（通过 env() 函数读取）
```

**示例：**

```php
// 包的默认配置
'algorithm' => env('API_SIGNATURE_ALGORITHM', 'sha256'),

// .env 中设置
API_SIGNATURE_ALGORITHM=md5

// 如果发布了配置文件，可以直接修改
'algorithm' => 'sha1',  // 会覆盖 .env 的值
```

### 常见配置场景

#### 场景 1：基础使用（仅修改密钥和算法）

✅ **使用 .env**，无需发布配置文件

```env
# .env
API_SIGNATURE_SECRET=your-super-secret-key-here-32chars
API_SIGNATURE_ALGORITHM=sha256
```

#### 场景 2：排除某些路由不验签

❌ .env 无法实现  
✅ **必须发布配置文件**

```bash
php artisan vendor:publish --tag=api-signature-config
```

```php
// config/api-signature.php
'except' => [
    'api/health',      // 健康检查
    'api/public/*',    // 公开 API
    'api/webhook',     // Webhook 回调
],
```

#### 场景 3：自定义 Header 字段名

❌ .env 无法实现  
✅ **必须发布配置文件**

```php
// config/api-signature.php
'header_signature' => 'Authorization',  // 使用标准的 Authorization 头
'header_timestamp' => 'X-Request-Time',
'header_nonce' => 'X-Request-ID',
```

#### 场景 4：使用 HTTP 代理

❌ .env 无法实现完整配置  
✅ **必须发布配置文件**

```php
// config/api-signature.php
'http' => [
    'timeout' => 60,
    'verify' => true,
    'proxy' => [
        'http'  => 'tcp://proxy.example.com:8080',
        'https' => 'tcp://proxy.example.com:8080',
    ],
    'auth' => ['proxy-user', 'proxy-pass'],
],
```

#### 场景 5：不同环境不同配置

✅ **使用 .env** + 环境变量最佳实践

```env
# .env.production
API_SIGNATURE_SECRET=production-secret-key
API_SIGNATURE_HTTP_VERIFY=true
API_SIGNATURE_EXPIRE=300

# .env.staging
API_SIGNATURE_SECRET=staging-secret-key
API_SIGNATURE_HTTP_VERIFY=true
API_SIGNATURE_EXPIRE=600

# .env.local
API_SIGNATURE_SECRET=local-secret-key
API_SIGNATURE_HTTP_VERIFY=false  # 仅本地开发
API_SIGNATURE_EXPIRE=3600
```

## 使用方法

### 门面方式

使用 `ApiSignature` 门面：

```php
use ApiSignature;

// 生成签名
$params = [
    'user_id' => 123,
    'action' => 'login',
    'timestamp' => time(),
    'nonce' => \Str::random(32),
];

$signature = ApiSignature::generate($params);

// 验证签名
$isValid = ApiSignature::verify($signature, $params);

// 发送带签名的 HTTP 请求
$response = ApiSignature::post('https://api.example.com/endpoint', [
    'user_id' => 123,
    'action' => 'login'
]);
```

### 中间件验证

在路由中使用中间件：

```php
Route::middleware('verify.signature')->group(function () {
    Route::post('/api/users', [UserController::class, 'store']);
    Route::get('/api/users/{id}', [UserController::class, 'show']);
});
```

### 依赖注入方式

```php
use Haiju\ApiSignature\SignatureManager;

class UserController extends Controller
{
    public function __construct(
        protected SignatureManager $signature
    ) {}
    
    public function store(Request $request)
    {
        $params = $request->all();
        $signature = $this->signature->generate($params);
        
        // ...
    }
}
```

## API 文档

### 签名算法详解

了解签名算法对于客户端实现至关重要。

#### 签名生成步骤

1. **过滤参数**：移除值为空字符串或 null 的参数
2. **排序参数**：按参数名的**字符串字典序**排序（`SORT_STRING`）
3. **拼接参数**：格式为 `key1=value1&key2=value2`
4. **添加密钥**：追加 `&secret=YOUR_SECRET`
5. **哈希计算**：使用指定算法（默认 SHA256）

#### 示例代码

**PHP 实现：**
```php
$params = [
    'user_id' => 123,
    'action' => 'login',
    'timestamp' => 1234567890,
    'nonce' => 'abc123',
];

// 1. 过滤空值（本例无空值）
$params = array_filter($params, fn($v) => $v !== '' && $v !== null);

// 2. 按键名排序（字符串比较）
ksort($params, SORT_STRING);
// 结果：['action', 'nonce', 'timestamp', 'user_id']

// 3. 拼接参数
$signString = 'action=login&nonce=abc123&timestamp=1234567890&user_id=123';

// 4. 添加密钥
$signString .= '&secret=your-secret-key';

// 5. 计算哈希
$signature = hash('sha256', $signString);
```

**JavaScript 实现：**
```javascript
function generateSignature(params, secret, algorithm = 'sha256') {
    // 1. 过滤空值
    const filtered = Object.fromEntries(
        Object.entries(params).filter(([k, v]) => v !== '' && v !== null)
    );
    
    // 2. 按键名排序（字符串比较）
    const sorted = Object.keys(filtered).sort();
    
    // 3. 拼接参数
    const parts = sorted.map(key => {
        const value = typeof filtered[key] === 'object' 
            ? JSON.stringify(filtered[key]) 
            : filtered[key];
        return `${key}=${value}`;
    });
    const signString = parts.join('&') + `&secret=${secret}`;
    
    // 4. 计算哈希（需要 crypto-js 或 Node.js crypto）
    const signature = require('crypto')
        .createHash(algorithm)
        .update(signString)
        .digest('hex');
    
    return signature;
}
```

#### ⚠️ 重要注意事项

1. **排序规则**：使用字符串字典序（lexicographical order），不是数值排序
   ```
   正确：['action', 'nonce', 'timestamp', 'user_id']
   错误：按添加顺序或其他规则
   ```

2. **大小写敏感**：参数名区分大小写
   ```
   'User_Id' ≠ 'user_id'
   ```

3. **数组和对象值**：需要 JSON 序列化
   ```php
   ['items' => ['a', 'b']] → items=["a","b"]
   ```

4. **字符编码**：统一使用 UTF-8

5. **空值处理**：空字符串和 null 会被过滤掉，不参与签名

### 生成签名

```php
ApiSignature::generate(array $params, ?string $secret = null): string
```

### 验证签名

```php
ApiSignature::verify(string $signature, array $params, ?string $secret = null): bool
```

### HTTP 请求方法

```php
ApiSignature::get(string $url, array $params = [], array $options = [])
ApiSignature::post(string $url, array $params = [], array $options = [])
ApiSignature::put(string $url, array $params = [], array $options = [])
ApiSignature::delete(string $url, array $params = [], array $options = [])
ApiSignature::request(string $method, string $url, array $params = [], array $options = [])
```

### 验证时间戳

```php
ApiSignature::verifyTimestamp(int $timestamp): bool
```

## HTTP 客户端配置

当使用包提供的 HTTP 请求方法（`get`、`post` 等）时，可以通过配置文件自定义 Guzzle 客户端的行为。

### 配置文件中的 HTTP 选项

```php
// config/api-signature.php
'http' => [
    'timeout' => 30,              // 请求超时（秒）
    'connect_timeout' => 10,      // 连接超时（秒）
    'verify' => true,             // ⚠️ SSL 证书验证（生产环境必须为 true）
    'proxy' => 'http://proxy:8080', // 代理服务器（可选）
    'headers' => [                // 默认请求头（可选）
        'User-Agent' => 'MyApp/1.0',
    ],
],
```

#### ⚠️ SSL 证书验证的重要性

**为什么必须启用 SSL 证书验证？**

即使使用了 API 签名验证，如果不启用 SSL 证书验证（`verify => true`），仍然存在严重的安全风险：

| 安全措施 | 作用 | 防护内容 |
|---------|-----|---------|
| **API 签名** | 应用层安全 | 验证请求来源、防止参数篡改 |
| **SSL 证书验证** | 传输层安全 | 验证服务器身份、防止中间人攻击、加密传输 |

**攻击场景示例：**
```
没有 SSL 验证时，即使有签名：
客户端 ----[签名+数据]----> 黑客 ----[转发]----> 服务器
         ↑                    ↓
         └────[窃听所有数据]───┘
```

**配置建议：**

- ✅ **生产环境**：`verify => true`（强制）
- ✅ **测试环境**：`verify => true`
- ⚠️ **开发环境**：使用自签名证书时可设为 `false`，但应使用有效证书

**本地开发使用自签名证书：**

```env
# .env.local
API_SIGNATURE_HTTP_VERIFY=false  # 仅限本地开发

# 或指定自定义 CA 证书
API_SIGNATURE_HTTP_VERIFY=/path/to/custom-ca.crt
```

### 运行时传递选项

也可以在调用时传递额外的 Guzzle 选项：

```php
// 为单个请求设置超时
$response = ApiSignature::post('https://api.example.com/endpoint', 
    ['user_id' => 123],
    ['timeout' => 60]  // 覆盖默认的 30 秒
);

// 使用代理
$response = ApiSignature::get('https://api.example.com/data', [], [
    'proxy' => 'http://proxy.example.com:8080',
]);

// 添加自定义请求头
$response = ApiSignature::post('https://api.example.com/endpoint', 
    ['data' => 'value'],
    [
        'headers' => [
            'X-Custom-Header' => 'value',
        ],
    ]
);
```

### 支持的配置选项

所有 Guzzle 的请求选项都受支持，详见：
[Guzzle 请求选项文档](https://docs.guzzlephp.org/en/stable/request-options.html)

常用选项包括：
- `timeout` - 请求超时时间
- `connect_timeout` - 连接超时时间
- `verify` - SSL 证书验证
- `proxy` - 代理服务器
- `headers` - HTTP 请求头
- `auth` - HTTP 认证
- `cert` - 客户端证书
- `ssl_key` - SSL 私钥

## 安全最佳实践

### 多层安全防护

本包提供的签名验证是**应用层**的安全措施，必须配合**传输层**的安全措施：

```
┌─────────────────────────────────────────┐
│  应用层：API 签名验证                     │
│  ✓ 验证请求来源（身份认证）                │
│  ✓ 防止参数篡改（完整性）                  │
│  ✓ 防止重放攻击（时间戳 + nonce）          │
└─────────────────────────────────────────┘
             ↓ 必须同时使用 ↓
┌─────────────────────────────────────────┐
│  传输层：HTTPS + SSL 证书验证             │
│  ✓ 验证服务器身份（防冒充）                │
│  ✓ 加密传输内容（防窃听）                  │
│  ✓ 防止中间人攻击（MITM）                  │
└─────────────────────────────────────────┘
```

### 何时可以禁用 SSL 证书验证？

| 场景 | 是否可以禁用 | 说明 |
|-----|------------|-----|
| 生产环境 | ❌ **绝对不可以** | 任何情况下都必须启用 |
| 测试环境 | ❌ **不建议** | 应该模拟生产环境 |
| 本地开发 + 使用正式域名 | ❌ **不应该** | 应该使用有效证书（Let's Encrypt） |
| 本地开发 + 自签名证书 | ⚠️ **可以但不推荐** | 更好的方案是信任自签名 CA |
| HTTP（非 HTTPS） | ⚠️ **协议本身不安全** | 仅限内网或完全信任的环境 |

### 推荐的开发环境配置

**方案 1：使用本地可信证书（推荐）**

```bash
# 使用 mkcert 生成本地可信证书
mkcert -install
mkcert localhost 127.0.0.1

# .env 中保持安全配置
API_SIGNATURE_HTTP_VERIFY=true
```

**方案 2：指定自定义 CA 证书**

```env
# .env
API_SIGNATURE_HTTP_VERIFY=/path/to/custom-ca-bundle.crt
```

**方案 3：仅在本地开发临时禁用（不推荐）**

```env
# .env.local
APP_ENV=local
API_SIGNATURE_HTTP_VERIFY=false  # ⚠️ 仅用于本地开发
```

### 安全检查清单

在部署前确认：

- [ ] ✅ `API_SIGNATURE_HTTP_VERIFY=true` 或指向有效的 CA 证书路径
- [ ] ✅ `API_SIGNATURE_SECRET` 使用强密钥（至少 32 字符随机字符串）
- [ ] ✅ 时间戳验证默认启用（通过中间件验证）
- [ ] ✅ `API_SIGNATURE_EXPIRE` 设置合理的过期时间（建议 300 秒）
- [ ] ✅ 所有 API 请求使用 HTTPS
- [ ] ✅ 生产环境的 `.env` 中没有 `API_SIGNATURE_HTTP_VERIFY=false`
- [ ] ✅ 已配置 Laravel 的缓存驱动（用于 nonce 防重放）

## 许可证

MIT License