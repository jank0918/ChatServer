<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/22
 * Time: 21:21
 */

namespace app\logic\model;


use app\common\redis;
use app\common\redis_key;

class MsgQueue extends Common
{
    public $redis_queue_key = redis_key::msg_queue;
    public $redis;

    public function __construct()
    {
        $this->redis = redis::getInstance(0,0);
    }

    public function add_queue($frame)
    {
        $this->redis->LPUSH($this->redis_queue_key,$frame);
    }

    public function out_queue()
    {
        return $this->redis->RPOP($this->redis_queue_key);
    }

    public function length_queue()
    {
        return $this->redis->LLEN($this->redis_queue_key);
    }
}