<?php

declare(strict_types=1);

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use Workerman\Connection\UdpConnection;

/**
 * UDP 模拟连接类用于测试
 *
 * @internal
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
        // 使用stream资源满足Workerman的要求
        $socket = stream_socket_server('udp://127.0.0.1:0', $errno, $errstr, STREAM_SERVER_BIND);
        if (false === $socket) {
            $errorMessage = \is_string($errstr) ? $errstr : 'Unknown error';
            throw new \RuntimeException('Cannot create socket for testing: ' . $errorMessage);
        }
        parent::__construct($socket, '127.0.0.1:8080');
    }

    /**
     * 发送数据
     * @param mixed $buffer
     * @param mixed $raw
     */
    public function send($buffer, $raw = false): bool
    {
        if (\is_scalar($buffer) || null === $buffer) {
            $this->sentData[] = (string) $buffer;
        } else {
            $this->sentData[] = \is_object($buffer) && \method_exists($buffer, '__toString') ? (string) $buffer : \serialize($buffer);
        }

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
        if (null !== $data) {
            if (\is_scalar($data)) {
                $this->sentData[] = (string) $data;
            } else {
                $this->sentData[] = \is_object($data) && \method_exists($data, '__toString') ? (string) $data : \serialize($data);
            }
        }
    }
}
