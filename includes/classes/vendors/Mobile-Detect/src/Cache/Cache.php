<?php

declare (strict_types=1);
namespace Detection\Cache;

use DateInterval;
use DateTime;
use function is_int;
use Psr\Simple_Cache\Cache_Interface;
use function time;
/**
 * In-memory cache implementation of PSR-16
 * @See https://www.php-fig.org/psr/psr-16/
 */
class Cache implements Cache_Interface
{
    protected array $cache = [];
    /**
     * @inheritdoc
     * @throws CacheInvalidArgumentException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->check_key($key);
        if (isset($this->cache[$key])) {
            if ($this->cache[$key]['ttl'] === null || $this->cache[$key]['ttl'] > time()) {
                return $this->cache[$key]['content'];
            }
            $this->delete_single($key);
        }
        return $default;
    }
    /**
     * @inheritdoc
     * @throws CacheInvalidArgumentException
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->check_key($key);
        // From https://www.php-fig.org/psr/psr-16/ "Definitions" -> "Expiration"
        // If a negative or zero TTL is provided, the item MUST be deleted from the cache if it exists, as it is expired already.
        if (is_int($ttl) && $ttl <= 0) {
            $this->delete_single($key);
            return false;
        }
        $ttl = $this->get_ttl($ttl);
        if ($ttl !== null) {
            $ttl = time() + $ttl;
        }
        $this->cache[$key] = ['ttl' => $ttl, 'content' => $value];
        return true;
    }
    /** @inheritdoc */
    public function delete(string $key): bool
    {
        $this->check_key($key);
        $this->delete_single($key);
        return true;
    }
    /**
     * Deletes the cache item from memory.
     *
     * @param string $key Cache key
     */
    private function delete_single(string $key): void
    {
        unset($this->cache[$key]);
    }
    /** @inheritdoc */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }
    /**
     * @inheritdoc
     * @throws CacheInvalidArgumentException
     */
    public function has(string $key): bool
    {
        $this->check_key($key);
        if (isset($this->cache[$key])) {
            if ($this->cache[$key]['ttl'] === null || $this->cache[$key]['ttl'] > time()) {
                return true;
            }
            $this->delete_single($key);
        }
        return false;
    }
    /** @inheritdoc */
    public function get_multiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }
    /** @inheritdoc */
    public function set_multiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $return = [];
        foreach ($values as $key => $value) {
            $return[] = $this->set($key, $value, $ttl);
        }
        return $this->check_return($return);
    }
    /** @inheritdoc */
    public function delete_multiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }
    /**
     * @throws CacheInvalidArgumentException
     */
    protected function check_key(string $key): string
    {
        if ($key === '' || !preg_match('/^[A-Za-z0-9_.]{1,64}$/', $key)) {
            throw new Cache_Invalid_Argument_Exception("Invalid key: '{$key}'. Must be alphanumeric, can contain _ and . and can be maximum of 64 chars.");
        }
        return $key;
    }
    /**  */
    protected function get_ttl(DateInterval|int|null $ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTime())->add($ttl)->get_time_stamp() - time();
        }
        // We treat 0 as a valid value.
        if (is_int($ttl)) {
            return $ttl;
        }
        return null;
    }
    /**
     * @param bool[]|int[] $booleans
     */
    protected function check_return(array $booleans): bool
    {
        foreach ($booleans as $boolean) {
            if (!$boolean) {
                return false;
            }
        }
        return true;
    }
    /**
     * Get all cache keys.
     *
     * @internal Needed for testing purposes.
     * @return array{string}
     */
    public function get_keys(): array
    {
        return array_keys($this->cache);
    }
    /**
     * Evict all expired items from the cache.
     *
     * Useful for long-running processes (CLI scripts, workers, daemons)
     * to periodically clean up expired entries and free memory.
     *
     * @return int Number of items evicted
     */
    public function evict_expired(): int
    {
        $evicted = 0;
        $now = time();
        foreach ($this->cache as $key => $item) {
            if ($item['ttl'] !== null && $item['ttl'] <= $now) {
                unset($this->cache[$key]);
                $evicted++;
            }
        }
        return $evicted;
    }
}