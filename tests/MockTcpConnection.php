<?php

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * TCP 模拟连接类用于测试
 */
class MockTcpConnection extends TcpConnection
{
    /**
     * 连接是否处于暂停状态
     */
    public bool $paused = false;

    /**
     * 发送的数据
     *
     * @var array<string>
     */
    public array $sentData = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 不调用父类构造函数，避免实际网络操作
    }

    /**
     * 暂停接收数据
     */
    public function pauseRecv(): void
    {
        $this->paused = true;
    }

    /**
     * 恢复接收数据
     */
    public function resumeRecv(): void
    {
        $this->paused = false;
    }

    /**
     * 发送数据
     */
    public function send($buffer, $raw = false): bool
    {
        $this->sentData[] = $buffer;
        return true;
    }

    /**
     * 获取远程IP
     */
    public function getRemoteIp(): string
    {
        return '127.0.0.1';
    }

    /**
     * 获取远程端口
     */
    public function getRemotePort(): int
    {
        return 8080;
    }

    /**
     * 获取本地IP
     */
    public function getLocalIp(): string
    {
        return '127.0.0.1';
    }

    /**
     * 获取本地端口
     */
    public function getLocalPort(): int
    {
        return 9000;
    }

    /**
     * 获取远程地址
     */
    public function getRemoteAddress(): string
    {
        return '127.0.0.1:8080';
    }

    /**
     * 获取本地地址
     */
    public function getLocalAddress(): string
    {
        return '127.0.0.1:9000';
    }

    /**
     * 是否为 IPv4
     */
    public function isIpV4(): bool
    {
        return true;
    }

    /**
     * 是否为 IPv6
     */
    public function isIpV6(): bool
    {
        return false;
    }

    /**
     * 关闭连接
     */
    public function close(mixed $data = null, bool $raw = false): void
    {
        if ($data !== null) {
            $this->sentData[] = $data;
        }
    }
}

/**
 * UDP 模拟连接类用于测试
 */
class MockUdpConnection extends UdpConnection
{
    /**
     * 连接是否处于暂停状态 (UDP 不会暂停，但为测试保留该属性)
     */
    public bool $paused = false;

    /**
     * 发送的数据
     *
     * @var array<string>
     */
    public array $sentData = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 不调用父类构造函数，避免实际网络操作
    }

    /**
     * 发送数据
     */
    public function send($buffer, $raw = false): bool
    {
        $this->sentData[] = $buffer;
        return true;
    }

    /**
     * 获取远程IP
     */
    public function getRemoteIp(): string
    {
        return '127.0.0.1';
    }

    /**
     * 获取远程端口
     */
    public function getRemotePort(): int
    {
        return 8080;
    }

    /**
     * 获取本地IP
     */
    public function getLocalIp(): string
    {
        return '127.0.0.1';
    }

    /**
     * 获取本地端口
     */
    public function getLocalPort(): int
    {
        return 9000;
    }

    /**
     * 获取远程地址
     */
    public function getRemoteAddress(): string
    {
        return '127.0.0.1:8080';
    }

    /**
     * 获取本地地址
     */
    public function getLocalAddress(): string
    {
        return '127.0.0.1:9000';
    }

    /**
     * 是否为 IPv4
     */
    public function isIpV4(): bool
    {
        return true;
    }

    /**
     * 是否为 IPv6
     */
    public function isIpV6(): bool
    {
        return false;
    }

    /**
     * 关闭连接
     */
    public function close(mixed $data = null, bool $raw = false): void
    {
        if ($data !== null) {
            $this->sentData[] = $data;
        }
    }
}
