<?php

namespace Autocom\Smarty\Redis;

/**
 * Redis CacheResource
 *
 * CacheResource implementation based on the KeyValueStore API to use
 * Redis as the storage resource for Smarty's output caching.
 *
 * @package smarty-redis
 * @author Martin Häger <martin.hager@autocom.se>
 */
class CacheResource extends \Smarty_CacheResource_KeyValueStore {

  /**
   * Redis client instance
   * @var \Predis\Client
   */
  protected $client;

  /**
   * @param \Predis\Client $client Redis client instance
   */
  public function __construct($client) {
    $this->client = $client;
  }

  /**
   * CacheResource factory
   *
   * @param string|array $url URL to Redis server (RFC 3986 string or parse_url() array)
   * @param string $keyPrefix (optional) Cache key prefix (defaults to 'smarty_cache:')
   * @return \Autocom\Smarty\Redis\CacheResource CacheResource instance
   */
  public static function create($url, $keyPrefix = 'smarty_cache:') {
    $client = new \Predis\Client($url, array('prefix' => $keyPrefix));
    return new self($client);
  }

  /**
   * {@inheritdoc}
   */
  protected function read(array $keys) {
    $values = $this->client->mget($keys);

    return array_combine($keys, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function write(array $keys, $expire = null) {
    try {
      $this->client->transaction(function($tx) use ($keys, $expire) {
        $tx->mset($keys);

        if ($expire != null) {
          foreach ($keys as $k => $v) {
            $tx->expire($k, $expire);
          }
        }
      });
    } catch (\Predis\Transaction\AbortedMultiExecException $e) {
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  protected function delete(array $keys) {
    try {
      $this->client->del($keys);
    } catch (\Predis\Response\ServerException $e) {
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  protected function purge() {
    try {
      $this->client->transaction(function($tx) {
        $keyPrefix = $this->client->getOptions()->prefix;

        foreach (new \Predis\Collection\Iterator\Keyspace($this->client, '*') as $key) {
          $tx->del(substr($key, strlen($keyPrefix)));
        }
      });
    } catch (\Predis\Transaction\AbortedMultiExecException $e) {
      return false;
    }

    return true;
  }

}
