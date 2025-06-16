<?php

namespace Tourze\Workerman\RateLimitProtocol\Tests;

use Tourze\Workerman\RateLimitProtocol\PacketRateLimitProtocol;
use Tourze\WorkermanPHPUnit\Core\AsyncTestCase;

/**
 * 包数量限制协议测试类
 */
class PacketRateLimitProtocolTest extends AsyncTestCase
{
    /**
     * 测试 decode 方法（直接返回原始数据）
     */
    public function testDecode(): void
    {
        $connection = new MockTcpConnection();
        $data = 'test data';

        $result = PacketRateLimitProtocol::decode($data, $connection);
        $this->assertEquals($data, $result, '解码方法应原样返回数据');
    }

    /**
     * 测试正常包数下的 input 方法
     */
    public function testInputNormalPackets(): void
    {
        $connection = new MockTcpConnection();
        $data = 'test packet';

        // 设置限流阈值为 100 包/秒
        PacketRateLimitProtocol::setDefaultLimit(100);

        $result = PacketRateLimitProtocol::input($data, $connection);
        $this->assertEquals(strlen($data), $result, '正常包数应返回数据长度');
        $this->assertFalse($connection->paused, '正常包数不应暂停连接');
    }

    /**
     * 测试超出限流阈值时的 input 方法（TCP 连接）
     */
    public function testInputExceedLimitTcp(): void
    {
        $connection = new MockTcpConnection();

        // 设置限流阈值为 1 包/秒
        PacketRateLimitProtocol::setDefaultLimit(1);

        // 第一个包应正常处理
        $result1 = PacketRateLimitProtocol::input('packet1', $connection);
        $this->assertEquals(strlen('packet1'), $result1);
        $this->assertFalse($connection->paused);

        // 第二个包应被限流
        PacketRateLimitProtocol::input('packet2', $connection);
        $this->assertTrue($connection->paused, 'TCP 连接应被暂停');
        
        // 验证 1 秒后连接恢复
        $this->advanceTime(1.0);
        $this->assertFalse($connection->paused, '1秒后连接应恢复');
    }

    /**
     * 测试超出限流阈值时的 input 方法（UDP 连接）
     */
    public function testInputExceedLimitUdp(): void
    {
        $connection = new MockUdpConnection();

        // 设置限流阈值为 1 包/秒
        PacketRateLimitProtocol::setDefaultLimit(1);

        // 第一个包应正常处理
        $result1 = PacketRateLimitProtocol::input('packet1', $connection);
        $this->assertEquals(strlen('packet1'), $result1);

        // 第二个包应被限流
        PacketRateLimitProtocol::input('packet2', $connection);
        $this->assertFalse($connection->paused, 'UDP 连接不应被暂停');
    }

    /**
     * 测试连接特定的限流
     */
    public function testConnectionSpecificLimit(): void
    {
        $connection1 = new MockTcpConnection();
        $connection2 = new MockTcpConnection();

        // 全局限制：10 包/秒
        PacketRateLimitProtocol::setDefaultLimit(10);

        // 为连接1设置：2 包/秒
        PacketRateLimitProtocol::setConnectionLimit($connection1, 2);

        // 连接1的第一个包应该正常
        $result1 = PacketRateLimitProtocol::input('packet1', $connection1);
        $this->assertEquals(strlen('packet1'), $result1);
        $this->assertFalse($connection1->paused);

        // 连接1的第二个包应该正常
        $result2 = PacketRateLimitProtocol::input('packet2', $connection1);
        $this->assertEquals(strlen('packet2'), $result2);
        $this->assertFalse($connection1->paused);

        // 连接1的第三个包应该被限流
        PacketRateLimitProtocol::input('packet3', $connection1);
        $this->assertTrue($connection1->paused);
        
        // 验证 1 秒后连接恢复
        $this->advanceTime(1.0);
        $this->assertFalse($connection1->paused, '1秒后连接1应恢复');

        // 连接2的第一个包应该正常（使用全局限制）
        $result4 = PacketRateLimitProtocol::input('packet1', $connection2);
        $this->assertEquals(strlen('packet1'), $result4);
        $this->assertFalse($connection2->paused);
    }

    /**
     * 测试 encode 方法
     */
    public function testEncode(): void
    {
        $connection = new MockTcpConnection();

        // 设置限流阈值为 1 包/秒
        PacketRateLimitProtocol::setDefaultLimit(1);

        // 第一个包应该正常发送
        $result1 = PacketRateLimitProtocol::encode('packet1', $connection);
        $this->assertEquals('packet1', $result1, '正常包数应原样返回数据');
        $this->assertFalse($connection->paused, '正常包数不应暂停连接');

        // 第二个包应该触发限流但仍然发送（TCP连接）
        $result2 = PacketRateLimitProtocol::encode('packet2', $connection);
        $this->assertEquals('packet2', $result2, 'TCP 连接即使超出包数限制也应发送数据');
        $this->assertTrue($connection->paused, 'TCP 连接应被暂停');
        
        // 验证 1 秒后连接恢复
        $this->advanceTime(1.0);
        $this->assertFalse($connection->paused, '1秒后连接应恢复');

        // 测试非 TCP 连接超过限制
        $connection2 = new MockUdpConnection();
        PacketRateLimitProtocol::setDefaultLimit(1); // 重置限制

        // 第一个包应该正常发送
        $result3 = PacketRateLimitProtocol::encode('packet1', $connection2);
        $this->assertEquals('packet1', $result3);

        // 第二个包应该被限流
        $result4 = PacketRateLimitProtocol::encode('packet2', $connection2);
        $this->assertEquals('', $result4, '非 TCP 连接超出包数限制应返回空字符串');
    }

    /**
     * 测试非字符串数据的 encode 方法
     */
    public function testEncodeNonStringData(): void
    {
        $connection = new MockTcpConnection();

        // 设置限流阈值为 10 包/秒
        PacketRateLimitProtocol::setDefaultLimit(10);

        $data = 123456; // 整数数据
        $result = PacketRateLimitProtocol::encode($data, $connection);
        $this->assertEquals((string)$data, $result, '非字符串数据应转换为字符串');
    }
}
