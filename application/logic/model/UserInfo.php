<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 18:53
 */

namespace app\logic\model;


use app\index\model\Model_Keys;
use My\Kit;

class UserInfo extends Common
{
    public $memberId;

    public $nickname;

    public $isVip;

    public $fd;

    public $sessionId;

    public $msg;

    public function __construct($data)
    {
        $this->initData($data);

        return $this;
    }

    public function initData($data)
    {
        $this->memberId = isset($data['member_id']) ? $data['member_id'] : '';
        $this->nickname = isset($data['nickname']) ? $data['nickname'] : '';
        $this->isVip = isset($data['is_vip']) ? $data['is_vip'] : '';
        $this->fd = isset($data['fd']) ? $data['fd'] : '';
        $this->sessionId = isset($data['session_id']) ? $data['session_id'] : '';
        $this->msg = isset($data['msg']) ? $data['msg'] : '';
    }

    public static function saveRedis($redis,$redisKey,$data)
    {
        $redis->SET($redisKey,json_encode($data));
        $sessionIdAndFd = Model_Keys::sessidAndFd();
        $redis->hset($sessionIdAndFd,$data['fd'],$data['session_id']);
    }

    public static function getDataFromRedis($redis,$redisKey)
    {
        $data = $redis->get($redisKey);
        return json_decode($data,true);
    }
}