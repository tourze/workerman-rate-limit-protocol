<?php

declare(strict_types=1);

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RateLimitProtocol\AbstractRateLimitProtocol;
use Workerman\Connection\ConnectionInterface;

/**
 * 抽象限流协议测试
 *
 * @internal
 */
#[CoversClass(AbstractRateLimitProtocol::class)]
final class AbstractRateLimitProtocolTest extends TestCase
{
    /**
     * 测试设置默认限流阈值
     */
    public function testSetDefaultLimit(): void
    {
        TestableRateLimitProtocol::setDefaultLimit(1024);

        // 验证默认限制是否生效 - 通过创建新连接的统计信息来验证
        $connection = $this->createMock(ConnectionInterface::class);
        $stats = TestableRateLimitProtocol::getConnectionStatsForTest($connection);

        $this->assertEquals(1024, $stats->limit, '新连接应该使用设置的默认限制');
    }

    /**
     * 测试为特定连接设置限流阈值
     */
    public function testSetConnectionLimit(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        TestableRateLimitProtocol::setConnectionLimit($connection, 2048);

        // 验证连接的特定限制是否生效
        $stats = TestableRateLimitProtocol::getConnectionStatsForTest($connection);
        $this->assertEquals(2048, $stats->limit, '连接应该使用设置的特定限制');
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
