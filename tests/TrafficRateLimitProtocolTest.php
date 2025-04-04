<?php

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RateLimitProtocol\TrafficRateLimitProtocol;

/**
 * 流量限制协议测试类
 */
class TrafficRateLimitProtocolTest extends TestCase
{
    /**
     * 测试 decode 方法（直接返回原始数据）
     */
    public function testDecode(): void
    {
        $connection = new MockTcpConnection();
        $data = 'test data';

        $result = TrafficRateLimitProtocol::decode($data, $connection);
        $this->assertEquals($data, $result, '解码方法应原样返回数据');
    }

    /**
     * 测试正常流量下的 input 方法
     */
    public function testInputNormalTraffic(): void
    {
        $connection = new MockTcpConnection();
        $data = str_repeat('a', 1000); // 1KB 数据

        // 设置限流阈值为 10KB
        TrafficRateLimitProtocol::setDefaultLimit(10 * 1024);

        $result = TrafficRateLimitProtocol::input($data, $connection);
        $this->assertEquals(strlen($data), $result, '正常流量应返回数据长度');
        $this->assertFalse($connection->paused, '正常流量不应暂停连接');
    }

    /**
     * 测试超出限流阈值时的 input 方法（TCP 连接）
     */
    public function testInputExceedLimitTcp(): void
    {
        $connection = new MockTcpConnection();
        $limit = 1024; // 1KB 限制
        $data = str_repeat('a', $limit * 2); // 2KB 数据

        // 设置限流阈值
        TrafficRateLimitProtocol::setDefaultLimit($limit);

        TrafficRateLimitProtocol::input($data, $connection);
        $this->assertTrue($connection->paused, 'TCP 连接应被暂停');
    }

    /**
     * 测试超出限流阈值时的 input 方法（UDP 连接）
     */
    public function testInputExceedLimitUdp(): void
    {
        $connection = new MockUdpConnection();
        $limit = 1024; // 1KB 限制
        $data = str_repeat('a', $limit * 2); // 2KB 数据

        // 设置限流阈值
        TrafficRateLimitProtocol::setDefaultLimit($limit);

        TrafficRateLimitProtocol::input($data, $connection);
        $this->assertFalse($connection->paused, 'UDP 连接不应被暂停');
    }

    /**
     * 测试连接特定的限流
     */
    public function testConnectionSpecificLimit(): void
    {
        $connection1 = new MockTcpConnection();
        $connection2 = new MockTcpConnection();

        // 全局限制：10KB
        TrafficRateLimitProtocol::setDefaultLimit(10 * 1024);

        // 为连接1设置：2KB
        TrafficRateLimitProtocol::setConnectionLimit($connection1, 2 * 1024);

        $data1 = str_repeat('a', 1500); // 1.5KB
        $data2 = str_repeat('b', 5000); // 5KB

        // 连接1应该没问题（低于2KB限制）
        $result1 = TrafficRateLimitProtocol::input($data1, $connection1);
        $this->assertEquals(strlen($data1), $result1);
        $this->assertFalse($connection1->paused);

        // 连接2应该没问题（低于10KB全局限制）
        $result2 = TrafficRateLimitProtocol::input($data2, $connection2);
        $this->assertEquals(strlen($data2), $result2);
        $this->assertFalse($connection2->paused);

        // 现在连接1发送超过限制的数据
        $data3 = str_repeat('c', 1000); // 再增加1KB，累计超过2KB限制
        TrafficRateLimitProtocol::input($data3, $connection1);
        $this->assertTrue($connection1->paused);
    }

    /**
     * 测试 encode 方法
     */
    public function testEncode(): void
    {
        $connection = new MockTcpConnection();
        $limit = 1024;
        $data = str_repeat('a', 500); // 500 字节数据

        // 设置限流阈值
        TrafficRateLimitProtocol::setDefaultLimit($limit);

        $result = TrafficRateLimitProtocol::encode($data, $connection);
        $this->assertEquals($data, $result, '正常流量应原样返回数据');
        $this->assertFalse($connection->paused, '正常流量不应暂停连接');

        // 测试超过限制
        $bigData = str_repeat('b', 2000); // 2KB 数据
        $result2 = TrafficRateLimitProtocol::encode($bigData, $connection);
        $this->assertEquals($bigData, $result2, 'TCP 连接即使超出流量限制也应发送数据');
        $this->assertTrue($connection->paused, 'TCP 连接应被暂停');

        // 测试非 TCP 连接超过限制
        $connection2 = new MockUdpConnection();

        $result3 = TrafficRateLimitProtocol::encode($bigData, $connection2);
        $this->assertEquals('', $result3, '非 TCP 连接超出流量限制应返回空字符串');
    }

    /**
     * 测试非字符串数据的 encode 方法
     */
    public function testEncodeNonStringData(): void
    {
        $connection = MockConnection::getTcpConnection();
        $limit = 1024;
        $data = 123456; // 整数数据

        // 设置限流阈值
        TrafficRateLimitProtocol::setDefaultLimit($limit);

        $result = TrafficRateLimitProtocol::encode($data, $connection);
        $this->assertEquals((string)$data, $result, '非字符串数据应转换为字符串');
    }
} 