<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/7/30
 * Time: 11:51
 */

namespace app\common;


use My\Kit;
use My\RedisPackage;

class friend {
    /**
     * 好友列表
     */
    const FRIEND_LIST = "friend_list:";

    /** @var int 好友关系 */
    const FRIEND            = 1;
    const NOT_FRIEND        = 2;
    const NOT_OTHER_FRIEND  = 3;

    /**
     * @param int $workId
     * @return RedisPackage
     */
    public static function getWriteRedis($workId = 0){
        return new RedisPackage(['select' => 2],$workId);
    }

    /**
     * @param int $workId
     * @return RedisPackage
     */
    public static function getReadRedis($workId = 0){
        return new RedisPackage(['select' => 2],$workId);
    }

    /**
     * 添加好友
     *
     * @param $memberId
     * @param $friendId
     * @return bool
     */
    public static function increaseFriend($memberId, $friendId){
        $redis = self::getWriteRedis();
        $redis->sadd(self::FRIEND_LIST.$memberId, $friendId);
        $redis->sadd(self::FRIEND_LIST.$friendId, $memberId);

        return true;
    }

    /**
     * 删除好友
     *
     * @param $memberId
     * @param $friendId
     * @return mixed
     */
    public static function decreaseFriend($memberId,$friendId){
        $redis = self::getWriteRedis();

        return $redis->srem(self::FRIEND_LIST.$memberId, $friendId);
    }

    /**
     * 获取好友列表
     *
     * @param $memberId
     * @return mixed
     */
    public static function getFriendList($memberId)
    {
        $readRedis = self::getReadRedis();

        $friendList = $readRedis->smembers(self::FRIEND_LIST.$memberId);

        return $friendList;
    }

    /**
     * 是否是好友
     *
     * @param $memberId
     * @param $friendId
     * @return int
     */
    public static function getIsFriend($memberId,$friendId)
    {
        Kit::debug("pokerReceive-AddFriend---------getIsFriend-start",'debug.log');

        $redis = self::getReadRedis();
        if(! $redis->sismember(self::FRIEND_LIST.$memberId,$friendId)){
            Kit::debug("pokerReceive-AddFriend---------NOT_FRIEND",'debug.log');
            return self::NOT_FRIEND;
        }


        if(! $redis->sismember(self::FRIEND_LIST.$friendId,$memberId)){
            Kit::debug("pokerReceive-AddFriend---------NOT_OTHER_FRIEND",'debug.log');
            return self::NOT_OTHER_FRIEND;
        }

        Kit::debug("pokerReceive-AddFriend---------FRIEND",'debug.log');
        return self::FRIEND;
    }
}