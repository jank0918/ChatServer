<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/28
 * Time: 17:41
 */

namespace app\common;


use My\RedisPackage;

class redis
{
    public static function get_redis($workId = 0,$select=0)
    {
        return new RedisPackage(['select'=>$select],$workId);
    }
}