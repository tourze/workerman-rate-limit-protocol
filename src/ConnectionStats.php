<?php

namespace Tourze\Workerman\RateLimitProtocol;

/**
 * 连接统计信息类
 */
class ConnectionStats
{
    /**
     * @param int $trafficBytes 流量字节数
     * @param int $packets 数据包数量
     * @param int $lastResetTime 上次重置时间
     * @param int $limit 限流阈值
     */
    public function __construct(
        public int $trafficBytes = 0,
        public int $packets = 0,
        public int $lastResetTime = 0,
        public int $limit = 0
    )
    {
    }

    /**
     * 创建一个新的流量统计实例
     */
    public static function createTrafficStats(int $limit): self
    {
        return new self(
            trafficBytes: 0,
            packets: 0,
            lastResetTime: time(),
            limit: $limit
        );
    }

    /**
     * 创建一个新的包数统计实例
     */
    public static function createPacketStats(int $limit): self
    {
        return new self(
            trafficBytes: 0,
            packets: 0,
            lastResetTime: time(),
            limit: $limit
        );
    }

    /**
     * 增加流量字节数
     */
    public function addTraffic(int $bytes): self
    {
        return new self(
            trafficBytes: $this->trafficBytes + $bytes,
            packets: $this->packets,
            lastResetTime: $this->lastResetTime,
            limit: $this->limit
        );
    }

    /**
     * 增加数据包数量
     */
    public function addPacket(): self
    {
        return new self(
            trafficBytes: $this->trafficBytes,
            packets: $this->packets + 1,
            lastResetTime: $this->lastResetTime,
            limit: $this->limit
        );
    }

    /**
     * 重置统计数据
     */
    public function reset(): self
    {
        return new self(
            trafficBytes: 0,
            packets: 0,
            lastResetTime: time(),
            limit: $this->limit
        );
    }

    /**
     * 检查是否超出流量限制
     */
    public function isTrafficLimitExceeded(int $additionalBytes): bool
    {
        return ($this->trafficBytes + $additionalBytes) > $this->limit;
    }

    /**
     * 检查是否超出包数限制
     */
    public function isPacketLimitExceeded(): bool
    {
        return ($this->packets + 1) > $this->limit;
    }

    /**
     * 更新限流阈值
     */
    public function withLimit(int $limit): self
    {
        return new self(
            trafficBytes: $this->trafficBytes,
            packets: $this->packets,
            lastResetTime: $this->lastResetTime,
            limit: $limit
        );
    }
}
