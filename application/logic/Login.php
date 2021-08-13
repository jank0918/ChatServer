<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 19:08
 */

namespace app\logic;


use app\common\code;
use app\common\redis_key;
use app\index\model\Model_Keys;
use app\logic\model\UserInfo;
use My\Kit;
use think\Exception;

class Login extends Logic
{
    const SESSION_KEY = "shijun256";
    public function main($server, $frame)
    {
        Kit::debug("toLogin-msg----start-----".print_r($frame,1),'debug.log');

        $redis = $this->redis();
        $fd = $frame->fd;
        $data = $this->getFrameData($frame->data);
        $data['fd'] = $fd;

        Kit::debug("toLogin-msg----data-----".print_r($data,1),'debug.log');

        $userInfo = new UserInfo($data);
        Kit::debug("toLogin-msg----UserInfo-----".print_r($userInfo,1),'debug.log');

        $sessionId = $this->getToken($userInfo->memberId,$fd);
        $data['session_id'] = $sessionId;

        Kit::debug("toLogin-msg----session-----".$sessionId,'debug.log');

        $ukey    = Model_Keys::uinfo($sessionId);
        $user    = UserInfo::getDataFromRedis($redis,$ukey);

        if(! empty($user)) {
            Kit::debug("toLogin-msg----repeat login-----",'debug.log');

            $this->replaceAccount($server,$user);

            /** 生成新的token */
            $this->getToken($userInfo->memberId,$fd);
        }
        Kit::debug("toLogin-msg----saveRedis-----".print_r($data,1),'debug.log');

        UserInfo::saveRedis($redis,$ukey,$data);

        Kit::debug("toLogin-msg----user redis2222-----".print_r($user,1),'debug.log');

        $server->push($fd,Kit::json_response(code::LOGIN_SUCCESS,'登录成功',[
            'msg'           =>'登录成功',
            'fd'            =>$fd,
            'session_id'    =>$sessionId
        ]));

        Kit::debug("toLogin-msg----end-----",'debug.log');
    }

    /**
     * @param $memberId
     * @return string
     */
    private function getToken($memberId,$fd) {
        Kit::debug("toLogin-msg----getToken-----".$memberId,'debug.log');

        $redis = $this->redis();
        $sessionId = $redis->hget(redis_key::login_hash_key,$memberId);
        if(empty($sessionId)) {
            $sessionId = md5(md5(self::SESSION_KEY).md5($memberId));

            $redis->hset(redis_key::login_hash_key,$memberId,$sessionId);
            $redis->hset(redis_key::fd_memberId,$fd,$memberId);
        }

        return $sessionId;
    }

    private function replaceAccount($server,$user)
    {
        Kit::debug("---------replaceAccount-------".print_r($user,1),'debug.log');

        $oldFd = $user['fd'];
        try{

            $server->push($oldFd,Kit::json_response(code::ACCOUNT_LOGIN_OTHER,'账号异地登录！',[
                'msg'  =>'账号异地登录！',
                'fd'   =>$oldFd,
            ]));

        }catch (Exception $exception){
            Kit::debug("---------replaceAccount-------error".print_r($exception,1),'debug.log');
        }

        Kit::debug("---------replaceAccount-------out-success",'debug.log');

        $server->close($oldFd);
    }
}