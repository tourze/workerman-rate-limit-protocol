<?php

declare(strict_types=1);

namespace Tourze\Workerman\RateLimitProtocol;

use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;

/**
 * 基于流量总数的限流协议
 */
class TrafficRateLimitProtocol extends AbstractRateLimitProtocol
{
    /**
     * 默认限流阈值（字节/秒）
     */
    protected static int $defaultLimit = 1024 * 1024; // 默认 1MB/s

    /**
     * 创建流量统计对象
     */
    protected static function createStats(int $limit): ConnectionStats
    {
        return ConnectionStats::createTrafficStats($limit);
    }

    /**
     * 更新统计信息
     */
    protected static function updateStats(ConnectionInterface $connection, ConnectionStats $stats, int $value): void
    {
        static::$statsMap[$connection] = $stats->addTraffic($value);
    }

    /**
     * 检查数据包的完整性，并返回数据包的长度
     */
    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        $stats = static::getConnectionStats($connection);

        // 计算缓冲区长度
        $bufferLength = strlen($buffer);

        // 检查是否超出限流阈值
        if ($stats->isTrafficLimitExceeded($bufferLength)) {
            // 根据连接类型采取不同的限流措施
            if ($connection instanceof TcpConnection) {
                // TCP连接，暂停接收
                static::pauseConnection($connection);

                return 0; // 等待更多数据，不处理当前数据
            }

            // 其他类型连接（包括UDP），直接丢弃数据包
            return 0;
        }

        // 更新统计信息
        static::updateStats($connection, $stats, $bufferLength);

        // 返回buffer长度，表示整个buffer都是一个完整的数据包
        return $bufferLength;
    }

    /**
     * 编码要发送的数据
     */
    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        $stats = static::getConnectionStats($connection);
        $stringData = match (true) {
            is_string($data) => $data,
            is_scalar($data) => (string) $data,
            is_null($data) => '',
            default => throw new \InvalidArgumentException('Data must be string, scalar, or null, got: ' . get_debug_type($data)),
        };
        $dataLength = strlen($stringData);

        // 检查是否会超出限流阈值
        if ($stats->isTrafficLimitExceeded($dataLength)) {
            // 根据连接类型采取不同的限流措施
            if ($connection instanceof TcpConnection) {
                // TCP连接，暂停接收但仍发送数据
                static::pauseConnection($connection);
                static::updateStats($connection, $stats, $dataLength);

                return $stringData;
            }

            // 其他类型连接，直接丢弃数据
            return '';
        }

        // 更新统计信息
        static::updateStats($connection, $stats, $dataLength);

        // 直接返回数据
        return $stringData;
    }
}
