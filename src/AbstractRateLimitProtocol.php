<?php

declare(strict_types=1);

namespace Tourze\Workerman\RateLimitProtocol;

use WeakMap;
use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\ProtocolInterface;
use Workerman\Timer;

/**
 * 限流协议抽象基类
 */
abstract class AbstractRateLimitProtocol implements ProtocolInterface
{
    /**
     * 默认限流阈值
     */
    protected static int $defaultLimit;

    /**
     * 统计数据 - 使用 WeakMap 自动管理内存
     *
     * @var \WeakMap<ConnectionInterface, ConnectionStats>
     */
    protected static \WeakMap $statsMap;

    /**
     * 统计类型（'traffic' 或 'packets'）
     */
    protected static string $statsType;

    /**
     * 暂停接收的连接列表
     *
     * @var \WeakMap<ConnectionInterface, bool>
     */
    protected static \WeakMap $pausedConnections;

    /**
     * 初始化 WeakMap
     */
    protected static function initMaps(): void
    {
        if (!isset(static::$statsMap)) {
            static::$statsMap = new \WeakMap();
        }

        if (!isset(static::$pausedConnections)) {
            static::$pausedConnections = new \WeakMap();
        }
    }

    /**
     * 设置默认限流阈值
     */
    public static function setDefaultLimit(int $limit): void
    {
        static::$defaultLimit = $limit;
    }

    /**
     * 为特定连接设置限流阈值
     */
    public static function setConnectionLimit(ConnectionInterface $connection, int $limit): void
    {
        static::initMaps();

        if (!static::$statsMap->offsetExists($connection)) {
            static::$statsMap[$connection] = static::createStats($limit);
        } else {
            $stats = static::$statsMap[$connection];
            static::$statsMap[$connection] = $stats->withLimit($limit);
        }
    }

    /**
     * 创建统计对象
     */
    abstract protected static function createStats(int $limit): ConnectionStats;

    /**
     * 检查连接是否已暂停接收
     */
    protected static function isConnectionPaused(ConnectionInterface $connection): bool
    {
        static::initMaps();

        return static::$pausedConnections->offsetExists($connection)
            && true === static::$pausedConnections[$connection];
    }

    /**
     * 暂停连接接收数据
     */
    protected static function pauseConnection(ConnectionInterface $connection): void
    {
        static::initMaps();

        // 只处理 TCP 连接
        if ($connection instanceof TcpConnection && !static::isConnectionPaused($connection)) {
            $connection->pauseRecv();
            static::$pausedConnections[$connection] = true;

            // 设置定时器，1秒后恢复接收
            Timer::add(1, function () use ($connection): void {
                static::resumeConnection($connection);
            }, null, false);
        }
    }

    /**
     * 恢复连接接收数据
     */
    protected static function resumeConnection(ConnectionInterface $connection): void
    {
        static::initMaps();

        // 只处理 TCP 连接
        if ($connection instanceof TcpConnection && static::isConnectionPaused($connection)) {
            $connection->resumeRecv();
            static::$pausedConnections[$connection] = false;
        }
    }

    /**
     * 检查并重置统计信息（如果需要）
     */
    protected static function checkAndResetStats(ConnectionInterface $connection): void
    {
        $currentTime = time();
        $stats = static::$statsMap[$connection];

        // 如果已经过了一秒，重置计数器
        if ($currentTime > $stats->lastResetTime) {
            static::$statsMap[$connection] = $stats->reset();

            // 如果连接被暂停，恢复接收
            if ($connection instanceof TcpConnection && static::isConnectionPaused($connection)) {
                static::resumeConnection($connection);
            }
        }
    }

    /**
     * 获取连接的统计信息，如果不存在则初始化
     */
    protected static function getConnectionStats(ConnectionInterface $connection): ConnectionStats
    {
        static::initMaps();

        // 初始化连接统计信息（如果不存在）
        if (!static::$statsMap->offsetExists($connection)) {
            static::$statsMap[$connection] = static::createStats(static::$defaultLimit);
        }

        static::checkAndResetStats($connection);

        return static::$statsMap[$connection];
    }

    public static function decode(string $buffer, ConnectionInterface $connection): mixed
    {
        // 直接返回原始数据
        return $buffer;
    }
}
