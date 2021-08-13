<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 19:22
 */

namespace app\logic;


use app\common\redis;
use app\common\redis_key;
use app\index\model\Model_Keys;
use app\logic\model\UserInfo;
use My\Kit;

abstract class Logic
{
    protected $userInfo;

    abstract protected function main($server, $frame);

    public function redis($workId = 0,$select=0)
    {
        return redis::get_redis($workId,$select);
    }

    public static function getUser($redis,$sessionId)
    {
        $redisKey = Model_Keys::uinfo($sessionId);
        Kit::debug("pokerReceive-getUser---------redisKey:".$redisKey,'debug.log');

        $info = $redis->get($redisKey);

        return json_decode($info,true);
    }

    public function getFrameData($data)
    {
        if(is_string($data)){
            $data = json_decode($data,true);
        }

        return $data;
    }

    public function getMemberIdByFd($fd)
    {
        return $this->redis()->hget(redis_key::fd_memberId,$fd);
    }

    /**
     * 获取自己的公会id
     *
     * @param $memberId
     * @return bool
     */
    public function getMyJoinRequest($memberId)
    {
        $sql = "SELECT * FROM tbl_organize_request WHERE member_id=? AND request_step=? AND request_type=? AND deleted_flg=0";
        $tmp = db_get_row($this->dbShare(), $sql, [$memberId, 1, 1]);
        if (empty($tmp)) {
            return false;
        }
        return $tmp;
    }

    public function getUserInfoBySession($sessionId)
    {
        $redis = $this->redis();
        $data = UserInfo::getDataFromRedis($redis,Model_Keys::uinfo($sessionId));
        return new UserInfo($data);
    }

    public function getSessionByMember($memberId)
    {
        return $this->redis()->hget(redis_key::login_hash_key,$memberId);
    }

    public function getFd($toSessionId)
    {
        $user = self::getUser($this->redis(),$toSessionId);
        return $user['fd'];
    }

    public function is_online($memberId)
    {
        $sessionId = $this->getSessionByMember($memberId);
        return $this->getFd($sessionId) ? 1 : 0;
    }
}