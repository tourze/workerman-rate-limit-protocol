# Workerman Rate Limit Protocol

[English](README.md) | 中文

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rate-limit-protocol)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rate-limit-protocol)
<!-- 其他相关徽章，例如构建状态、质量得分等 (如果适用) -->
<!-- [![Build Status](https://img.shields.io/travis/tourze/workerman-rate-limit-protocol/master.svg?style=flat-square)](https://travis-ci.org/tourze/workerman-rate-limit-protocol) -->
<!-- [![Quality Score](https://img.shields.io/scrutinizer/g/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/workerman-rate-limit-protocol) -->

一个与 Workerman 集成的限流协议实现。

## 功能特点

- 提供基于流量的限流协议 (字节/秒)。
- 提供基于数据包数量的限流协议 (包/秒)。
- 支持全局设置默认限流值，并可为特定连接覆盖此限制。
- 无侵入式使用，作为现有 Workerman 协议的包装器 (如果未指定内部协议，则默认为原始 TCP/UDP 处理)。
- 使用 `WeakMap` 存储连接上下文，实现自动内存管理 (连接关闭时无需手动清理)。
- **TCP 连接**: 超出速率限制时暂停接收数据 (`pauseRecv`) 而不是关闭连接。1 秒后自动恢复。
- **UDP 连接**: 超出速率限制时直接丢弃数据包。
- **重要提示**: 当前限流实现是**基于单个进程**的。在多进程 Workerman 设置中，限制独立应用于每个 Worker 进程，而非所有进程的全局限制。

## 安装

```bash
composer require tourze/workerman-rate-limit-protocol
```

## 快速开始

### 基于流量总数的限流 (字节/秒)

```php
<?php

use Tourze\Workerman\RateLimitProtocol\TrafficRateLimitProtocol;
use Workerman\Worker;
use Workerman\Connection\TcpConnection; // 如果使用，请确保导入 TcpConnection

require_once __DIR__ . '/vendor/autoload.php'; // 根据需要调整路径

// 创建一个监听 8080 端口的 Worker
// 应用基于流量的限流: 默认限制每个连接为 1MB/s
$worker = new Worker('tcp://0.0.0.0:8080');
$worker->protocol = TrafficRateLimitProtocol::class;
TrafficRateLimitProtocol::setDefaultLimit(1024 * 1024); // 1MB/s

$worker->onConnect = function(TcpConnection $connection) { // 类型提示 connection 以提高清晰度
    echo "新的连接来自 {$connection->getRemoteAddress()}\n";

    // 可选: 为此特定连接设置不同的速率限制
    TrafficRateLimitProtocol::setConnectionLimit($connection, 2 * 1024 * 1024); // 此连接为 2MB/s
    echo "已为连接 {$connection->id} 设置特定限制为 2MB/s\n";
};

$worker->onMessage = function(TcpConnection $connection, $data) {
    // 协议在其 input/encode 方法中处理限流检查。
    // 只有在限制范围内的数据才会到达您的应用程序逻辑。
    echo "收到来自连接 {$connection->id} 的消息: {$data}\n";
    $connection->send('服务器收到: ' . $data);
};

$worker->onClose = function(TcpConnection $connection) {
    echo "来自 {$connection->getRemoteAddress()} 的连接已关闭\n";
};

Worker::runAll();

```

### 基于数据包数量的限流 (包/秒)

```php
<?php

use Tourze\Workerman\RateLimitProtocol\PacketRateLimitProtocol;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/vendor/autoload.php';

// 创建一个监听 8081 端口的 Worker
// 应用基于数据包的限流: 默认限制每个连接为 100 包/秒
$worker = new Worker('tcp://0.0.0.0:8081');
$worker->protocol = PacketRateLimitProtocol::class;
PacketRateLimitProtocol::setDefaultLimit(100); // 100 包/秒

$worker->onConnect = function(TcpConnection $connection) {
    echo "8081 端口有新的连接来自 {$connection->getRemoteAddress()}\n";

    // 可选: 为此连接设置不同的包速率限制
    PacketRateLimitProtocol::setConnectionLimit($connection, 200); // 此连接为 200 包/秒
    echo "已为连接 {$connection->id} 设置特定包限制为 200/s\n";
};

$worker->onMessage = function(TcpConnection $connection, $data) {
    echo "在 8081 端口收到来自连接 {$connection->id} 的消息: {$data}\n";
    $connection->send('服务器收到包: ' . $data);
};

$worker->onClose = function(TcpConnection $connection) {
    echo "来自 {$connection->getRemoteAddress()} 的连接 (8081 端口) 已关闭\n";
};

Worker::runAll();

```

## 注意事项

- 统计数据按秒计算，并且每个连接的计数器每秒独立重置。
- 核心限制逻辑发生在协议类的 `input` 和 `encode` 方法内部。

## 贡献

如果主项目有贡献指南，请参考。欢迎提交 Issue 和 Pull Request。

## 协议

MIT 许可证 (MIT)。详情请参阅 [LICENSE](LICENSE) 文件。
