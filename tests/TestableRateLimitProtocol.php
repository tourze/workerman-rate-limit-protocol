<?php

declare(strict_types=1);

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use Tourze\Workerman\RateLimitProtocol\AbstractRateLimitProtocol;
use Tourze\Workerman\RateLimitProtocol\ConnectionStats;
use Workerman\Connection\ConnectionInterface;

/**
 * 用于测试的抽象类实现
 *
 * @internal
 */
class TestableRateLimitProtocol extends AbstractRateLimitProtocol
{
    protected static int $defaultLimit = 1000;

    protected static string $statsType = 'test';

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_numeric($data) || is_bool($data)) {
            return (string) $data;
        }

        if (is_object($data) && method_exists($data, '__toString')) {
            return (string) $data;
        }

        if (is_array($data)) {
            return json_encode($data, JSON_THROW_ON_ERROR);
        }

        if (null === $data) {
            return '';
        }

        // 对于其他类型，使用 serialize 作为后备方案
        return serialize($data);
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        return strlen($buffer);
    }

    protected static function createStats(int $limit): ConnectionStats
    {
        return ConnectionStats::createTrafficStats($limit);
    }

    /**
     * 公开受保护的方法以供测试使用
     */
    protected static function getConnectionStats(ConnectionInterface $connection): ConnectionStats
    {
        return parent::getConnectionStats($connection);
    }

    /**
     * 供测试使用的公开方法
     */
    public static function getConnectionStatsForTest(ConnectionInterface $connection): ConnectionStats
    {
        return static::getConnectionStats($connection);
    }
}
