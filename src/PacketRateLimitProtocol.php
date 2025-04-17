<?php

namespace Tourze\Workerman\RateLimitProtocol;

use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * 基于包数计数的限流协议
 */
class PacketRateLimitProtocol extends AbstractRateLimitProtocol
{
    /**
     * 默认限流阈值（包/秒）
     */
    protected static int $defaultLimit = 100; // 默认 100包/秒

    /**
     * 创建包数统计对象
     */
    protected static function createStats(int $limit): ConnectionStats
    {
        return ConnectionStats::createPacketStats($limit);
    }

    /**
     * 更新统计信息
     */
    protected static function updateStats(ConnectionInterface $connection, ConnectionStats $stats): void
    {
        // 对于包限流，不使用value参数
        static::$statsMap[$connection] = $stats->addPacket();
    }

    /**
     * 检查数据包的完整性，并返回数据包的长度
     */
    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        $stats = static::getConnectionStats($connection);

        // 检查是否超出限流阈值
        if ($stats->isPacketLimitExceeded()) {
            // 根据连接类型采取不同的限流措施
            if ($connection instanceof TcpConnection) {
                // TCP连接，暂停接收
                static::pauseConnection($connection);
                return 0; // 等待更多数据，不处理当前数据
            } elseif ($connection instanceof UdpConnection) {
                // UDP连接，直接丢弃数据包
                return 0;
            } else {
                // 其他类型连接，返回-1关闭连接
                return -1;
            }
        }

        // 更新统计信息
        static::updateStats($connection, $stats);

        // 返回buffer长度，表示整个buffer都是一个完整的数据包
        return strlen($buffer);
    }

    /**
     * 编码要发送的数据
     */
    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        $stats = static::getConnectionStats($connection);
        $stringData = is_string($data) ? $data : (string)$data;

        // 检查是否会超出限流阈值
        if ($stats->isPacketLimitExceeded()) {
            // 根据连接类型采取不同的限流措施
            if ($connection instanceof TcpConnection) {
                // TCP连接，暂停接收但仍发送数据
                static::pauseConnection($connection);
                static::updateStats($connection, $stats);
                return $stringData;
            } else {
                // 其他类型连接，直接丢弃数据
                return '';
            }
        }

        // 更新统计信息
        static::updateStats($connection, $stats);

        // 直接返回数据
        return $stringData;
    }
}
