<?php

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RateLimitProtocol\ConnectionStats;

/**
 * 连接统计信息类测试
 */
class ConnectionStatsTest extends TestCase
{
    /**
     * 测试构造函数和属性
     */
    public function testConstructor(): void
    {
        $stats = new ConnectionStats(
            trafficBytes: 1024,
            packets: 10,
            lastResetTime: 1234567890,
            limit: 2048
        );

        $this->assertEquals(1024, $stats->trafficBytes, '流量字节数应为 1024');
        $this->assertEquals(10, $stats->packets, '数据包数量应为 10');
        $this->assertEquals(1234567890, $stats->lastResetTime, '上次重置时间应为 1234567890');
        $this->assertEquals(2048, $stats->limit, '限流阈值应为 2048');
    }

    /**
     * 测试创建流量统计实例方法
     */
    public function testCreateTrafficStats(): void
    {
        $limit = 1024 * 1024;
        $stats = ConnectionStats::createTrafficStats($limit);

        $this->assertEquals(0, $stats->trafficBytes, '新创建的实例流量字节数应为 0');
        $this->assertEquals(0, $stats->packets, '新创建的实例数据包数量应为 0');
        $this->assertGreaterThan(0, $stats->lastResetTime, '上次重置时间应为正数');
        $this->assertEquals($limit, $stats->limit, '限流阈值应为指定值');
    }

    /**
     * 测试创建包数统计实例方法
     */
    public function testCreatePacketStats(): void
    {
        $limit = 100;
        $stats = ConnectionStats::createPacketStats($limit);

        $this->assertEquals(0, $stats->trafficBytes, '新创建的实例流量字节数应为 0');
        $this->assertEquals(0, $stats->packets, '新创建的实例数据包数量应为 0');
        $this->assertGreaterThan(0, $stats->lastResetTime, '上次重置时间应为正数');
        $this->assertEquals($limit, $stats->limit, '限流阈值应为指定值');
    }

    /**
     * 测试增加流量字节数方法
     */
    public function testAddTraffic(): void
    {
        $stats = new ConnectionStats(
            trafficBytes: 1000,
            packets: 5,
            lastResetTime: time(),
            limit: 2000
        );

        $newStats = $stats->addTraffic(500);

        // 原实例不应被修改
        $this->assertEquals(1000, $stats->trafficBytes, '原实例流量字节数不应改变');

        // 新实例应反映变化
        $this->assertEquals(1500, $newStats->trafficBytes, '新实例流量字节数应增加');
        $this->assertEquals(5, $newStats->packets, '新实例数据包数量应保持不变');
        $this->assertEquals($stats->lastResetTime, $newStats->lastResetTime, '新实例重置时间应保持不变');
        $this->assertEquals(2000, $newStats->limit, '新实例限流阈值应保持不变');
    }

    /**
     * 测试增加数据包数量方法
     */
    public function testAddPacket(): void
    {
        $stats = new ConnectionStats(
            trafficBytes: 1000,
            packets: 5,
            lastResetTime: time(),
            limit: 10
        );

        $newStats = $stats->addPacket();

        // 原实例不应被修改
        $this->assertEquals(5, $stats->packets, '原实例数据包数量不应改变');

        // 新实例应反映变化
        $this->assertEquals(6, $newStats->packets, '新实例数据包数量应增加');
        $this->assertEquals(1000, $newStats->trafficBytes, '新实例流量字节数应保持不变');
        $this->assertEquals($stats->lastResetTime, $newStats->lastResetTime, '新实例重置时间应保持不变');
        $this->assertEquals(10, $newStats->limit, '新实例限流阈值应保持不变');
    }

    /**
     * 测试重置统计数据方法
     */
    public function testReset(): void
    {
        $stats = new ConnectionStats(
            trafficBytes: 1000,
            packets: 5,
            lastResetTime: time() - 10, // 10秒前
            limit: 2000
        );

        $newStats = $stats->reset();

        // 原实例不应被修改
        $this->assertEquals(1000, $stats->trafficBytes, '原实例流量字节数不应改变');
        $this->assertEquals(5, $stats->packets, '原实例数据包数量不应改变');

        // 新实例应被重置
        $this->assertEquals(0, $newStats->trafficBytes, '新实例流量字节数应重置为 0');
        $this->assertEquals(0, $newStats->packets, '新实例数据包数量应重置为 0');
        $this->assertGreaterThan($stats->lastResetTime, $newStats->lastResetTime, '新实例重置时间应更新');
        $this->assertEquals(2000, $newStats->limit, '新实例限流阈值应保持不变');
    }

    /**
     * 测试检查是否超出流量限制方法
     */
    public function testIsTrafficLimitExceeded(): void
    {
        $stats = new ConnectionStats(
            trafficBytes: 800,
            packets: 5,
            lastResetTime: time(),
            limit: 1000
        );

        // 添加 100 字节不会超出限制
        $this->assertFalse($stats->isTrafficLimitExceeded(100), '添加 100 字节不应超出限制');

        // 添加 201 字节会超出限制
        $this->assertTrue($stats->isTrafficLimitExceeded(201), '添加 201 字节应超出限制');

        // 原实例不应被修改
        $this->assertEquals(800, $stats->trafficBytes, '原实例流量字节数不应改变');
    }

    /**
     * 测试检查是否超出包数限制方法
     */
    public function testIsPacketLimitExceeded(): void
    {
        // 当前 5 个包，限制 6 个包，再增加 1 个包不会超出限制
        $stats1 = new ConnectionStats(
            trafficBytes: 1000,
            packets: 5,
            lastResetTime: time(),
            limit: 6
        );
        $this->assertFalse($stats1->isPacketLimitExceeded(), '再增加 1 个包不应超出限制');

        // 当前 5 个包，限制 5 个包，再增加 1 个包会超出限制
        $stats2 = new ConnectionStats(
            trafficBytes: 1000,
            packets: 5,
            lastResetTime: time(),
            limit: 5
        );
        $this->assertTrue($stats2->isPacketLimitExceeded(), '再增加 1 个包应超出限制');
    }

    /**
     * 测试更新限流阈值方法
     */
    public function testWithLimit(): void
    {
        $stats = new ConnectionStats(
            trafficBytes: 1000,
            packets: 5,
            lastResetTime: time(),
            limit: 2000
        );

        $newStats = $stats->withLimit(3000);

        // 原实例不应被修改
        $this->assertEquals(2000, $stats->limit, '原实例限流阈值不应改变');

        // 新实例应反映变化
        $this->assertEquals(3000, $newStats->limit, '新实例限流阈值应更新');
        $this->assertEquals(1000, $newStats->trafficBytes, '新实例流量字节数应保持不变');
        $this->assertEquals(5, $newStats->packets, '新实例数据包数量应保持不变');
        $this->assertEquals($stats->lastResetTime, $newStats->lastResetTime, '新实例重置时间应保持不变');
    }
} 