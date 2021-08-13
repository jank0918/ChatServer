<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 18:53
 */

namespace app\logic\model;


use app\common\redis;
use app\common\redis_key;
use app\index\model\Model_Keys;
use My\Kit;

class Club extends Common
{
    public function getClubList($fd)
    {
        $redis = $this->redis();
        $memberId = $redis->hget(redis_key::fd_memberId,$fd);
    }
}