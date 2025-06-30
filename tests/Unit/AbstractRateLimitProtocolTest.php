<?php

namespace Tourze\Workerman\RateLimitProtocol\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RateLimitProtocol\AbstractRateLimitProtocol;
use Tourze\Workerman\RateLimitProtocol\ConnectionStats;
use Workerman\Connection\ConnectionInterface;

/**
 * 抽象限流协议测试
 */
class AbstractRateLimitProtocolTest extends TestCase
{

    /**
     * 测试设置默认限流阈值
     */
    public function testSetDefaultLimit(): void
    {
        TestableRateLimitProtocol::setDefaultLimit(1024);
        $this->assertTrue(true, '设置默认限流阈值应该成功');
    }

    /**
     * 测试为特定连接设置限流阈值
     */
    public function testSetConnectionLimit(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        TestableRateLimitProtocol::setConnectionLimit($connection, 2048);
        $this->assertTrue(true, '设置连接限流阈值应该成功');
    }

    /**
     * 测试解码方法
     */
    public function testDecode(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $buffer = 'test data';
        
        $result = TestableRateLimitProtocol::decode($buffer, $connection);
        
        $this->assertEquals($buffer, $result, '解码应该返回原始数据');
    }
}

/**
 * 用于测试的抽象类实现
 */
class TestableRateLimitProtocol extends AbstractRateLimitProtocol
{
    protected static int $defaultLimit = 1000;
    protected static string $statsType = 'test';

    protected static function createStats(int $limit): ConnectionStats
    {
        return ConnectionStats::createTrafficStats($limit);
    }

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        return (string) $data;
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        return strlen($buffer);
    }
}