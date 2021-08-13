<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/28
 * Time: 17:36
 */

namespace app\logic\model;


use app\common\redis;

class Common
{
    public function redis($workId = 0)
    {
        return redis::get_redis($workId);
    }
}