<?php

namespace Laravel\Horizon;

use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisHorizonCommandQueue implements HorizonCommandQueue
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * Create a new command queue instance.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Push a command onto a given queue.
     *
     * @param  string  $name
     * @param  string  $command
     * @param  array  $options
     */
    public function push($name, $command, array $options = [])
    {
        $this->connection()->rpush('commands:'.$name, json_encode([
            'command' => $command,
            'options' => $options,
        ]));
    }

    /**
     * Get the pending commands for a given queue name.
     *
     * @param  string  $name
     * @return array
     */
    public function pending($name)
    {
        $results = $this->connection()->eval(LuaScripts::flushList(), 1,
            'commands:'.$name
        );

        return collect($results)->map(function ($result) {
            return (object) json_decode($result, true);
        })->all();
    }

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     * @return void
     */
    public function flush($name)
    {
        $this->connection()->del('commands:'.$name);
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function connection()
    {
        return $this->redis->connection('horizon');
    }
}
