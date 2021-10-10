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
    public static $_instance;

    private function __construct()
    {
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance($workId = 0, $db = 0)
    {
        try{
            if (isset(self::$_instance[$db]) && self::$_instance[$db]->Ping() == 'Pong') {
                return self::$_instance[$db];
            }
        } catch (Exception $e) {

        }

        self::$_instance[$db] = new RedisPackage(['select'=>$db],$workId);

        return self::$_instance[$db];
    }
}