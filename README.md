# Workerman Rate Limit Protocol

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rate-limit-protocol)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rate-limit-protocol)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rate-limit-protocol)
[![License](https://img.shields.io/packagist/l/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://github.com/tourze/workerman-rate-limit-protocol/blob/master/LICENSE)
<!-- Add other relevant badges like build status, quality score if applicable -->
<!-- [![Build Status](https://img.shields.io/travis/tourze/workerman-rate-limit-protocol/master.svg?style=flat-square)](https://travis-ci.org/tourze/workerman-rate-limit-protocol) -->
<!-- [![Quality Score](https://img.shields.io/scrutinizer/g/tourze/workerman-rate-limit-protocol.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/workerman-rate-limit-protocol) -->

A rate limiting protocol implementation integrated with Workerman.

## Features

- Provides traffic-based rate limiting protocol (bytes/second).
- Provides packet-based rate limiting protocol (packets/second).
- Supports setting default rate limits globally and overriding limits for specific connections.
- Non-intrusive usage, acts as a wrapper around existing Workerman protocols (defaults to raw TCP/UDP handling if no inner protocol specified).
- Uses `WeakMap` to store connection context for automatic memory management (no manual cleanup needed on connection close).
- **TCP Connections**: Pauses receiving data (`pauseRecv`) instead of closing when the rate limit is exceeded. Resumes automatically after 1 second.
- **UDP Connections**: Directly discards packets when the rate limit is exceeded.
- **Important Note**: Rate limiting is currently implemented **per-process**. In a multi-process Workerman setup, the limit applies independently to each worker process, not globally across all processes.

## Installation

```bash
composer require tourze/workerman-rate-limit-protocol
```

## Quick Start

### Traffic-based Rate Limiting (Bytes/Second)

```php
<?php

use Tourze\Workerman\RateLimitProtocol\TrafficRateLimitProtocol;
use Workerman\Worker;
use Workerman\Connection\TcpConnection; // Ensure TcpConnection is imported if used

require_once __DIR__ . '/vendor/autoload.php'; // Adjust path if needed

// Create a Worker listening on port 8080
// Apply traffic-based rate limiting: Limit each connection to 1MB/s by default
$worker = new Worker('tcp://0.0.0.0:8080');
$worker->protocol = TrafficRateLimitProtocol::class;
TrafficRateLimitProtocol::setDefaultLimit(1024 * 1024); // 1MB/s

$worker->onConnect = function(TcpConnection $connection) { // Type hint connection for clarity
    echo "New connection from {$connection->getRemoteAddress()}\n";

    // Optional: Set a different rate limit for this specific connection
    TrafficRateLimitProtocol::setConnectionLimit($connection, 2 * 1024 * 1024); // 2MB/s for this one
    echo "Set connection-specific limit to 2MB/s for {$connection->id}\n";
};

$worker->onMessage = function(TcpConnection $connection, $data) {
    // The protocol handles the rate limiting check in its input/encode methods.
    // Your application logic receives data only if it's within the limit.
    echo "Received message from {$connection->id}: {$data}\n";
    $connection->send('Server received: ' . $data);
};

$worker->onClose = function(TcpConnection $connection) {
    echo "Connection closed from {$connection->getRemoteAddress()}\n";
};

Worker::runAll();

```

### Packet-based Rate Limiting (Packets/Second)

```php
<?php

use Tourze\Workerman\RateLimitProtocol\PacketRateLimitProtocol;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/vendor/autoload.php';

// Create a Worker listening on port 8081
// Apply packet-based rate limiting: Limit each connection to 100 packets/s by default
$worker = new Worker('tcp://0.0.0.0:8081');
$worker->protocol = PacketRateLimitProtocol::class;
PacketRateLimitProtocol::setDefaultLimit(100); // 100 packets/s

$worker->onConnect = function(TcpConnection $connection) {
    echo "New connection on 8081 from {$connection->getRemoteAddress()}\n";

    // Optional: Set a different packet rate limit for this connection
    PacketRateLimitProtocol::setConnectionLimit($connection, 200); // 200 packets/s for this one
    echo "Set connection-specific packet limit to 200/s for {$connection->id}\n";
};

$worker->onMessage = function(TcpConnection $connection, $data) {
    echo "Received message on 8081 from {$connection->id}: {$data}\n";
    $connection->send('Server received packet: ' . $data);
};

$worker->onClose = function(TcpConnection $connection) {
    echo "Connection closed on 8081 from {$connection->getRemoteAddress()}\n";
};

Worker::runAll();

```

## Notes

- Statistics are calculated per second, and counters reset every second for each connection independently.
- The core limiting logic happens within the `input` and `encode` methods of the protocol classes.

## Contributing

Please refer to the main project contribution guidelines if available. Issues and Pull Requests are welcome.

## License

The MIT License (MIT). Please see the [LICENSE](LICENSE) file for more information.
