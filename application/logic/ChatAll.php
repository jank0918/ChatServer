<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 14:09
 */

namespace app\logic;


use app\common\code;
use app\common\redis_key;
use app\index\model\Model_Keys;
use app\logic\model\UserInfo;
use My\Kit;

class ChatAll extends Logic
{
    const HOLD_TIME = 1; //发送消息冷却时间

    public function main($server, $frame)
    {
        Kit::debug("pokerReceive-toAll---------start",'debug.log');

        $redis  = $this->redis();
        $fd     = $frame->fd;
        $key    = Model_Keys::pokerReceive($fd);

        $frameData = $this->getFrameData($frame->data);
        Kit::debug("pokerReceive-toAll---------frameData::".print_r($frameData,1),'debug.log');

        $sessionId  = $frameData['session_id'];
        $data = UserInfo::getDataFromRedis($redis,Model_Keys::uinfo($sessionId));
        $userInfo = new UserInfo($data);
        Kit::debug("pokerReceive-toAll---------member_id::".print_r($userInfo->memberId,1),'debug.log');

        $redis = $this->redis(0,1);
        if($redis->sismember(redis_key::limit_list,$userInfo->memberId)){
            return $server->push($fd,Kit::json_response(code::CODE_LIMIT,'您已被禁言！'));
        }

        Kit::debug("pokerReceive-toAll---------userinfo::".print_r($data,1),'debug.log');

        $redis  = $this->redis();
        if(!$redis->SETNX($key,1)) {
            $next = intval($redis->TTL($key));
            Kit::debug("pokerReceive-toAll---------start--msg-hold",'debug.log');
            $server->push($fd,Kit::json_response(code::MSG_ALL_HOLD,'消息时间冷却',[
                'msg'  =>'还有'.$next.'s可以发送消息',
                'nick' => '系统消息',
                'fd'   =>$fd,
            ]));
        }else{
            $redis->expire($key,self::HOLD_TIME);
            Kit::debug("pokerReceive-toAll---------session:".$sessionId,'debug.log');

            $msg = $frameData['msg'];
            $msg = trim($msg);
            $msg = htmlspecialchars_decode($msg);
            $msg = preg_replace("/<(.*?)>/","",$msg);
            $msg = mb_strlen($msg,'utf-8') > 100 ? mb_substr($msg,0,100) : $msg;
            if($msg !== "") {
                Kit::debug("pokerReceive-toAll---------msg right",'debug.log');

                $info = $server->getClientInfo($fd);
                Kit::debug("pokerReceive-toAll---------getClientInfo:".print_r($info,1),'debug.log');

                $server->task(json_encode([
                    'msg'=> $msg,
                    'fd'=>$fd,
                    'ip' =>isset($info['remote_ip']) ? $info['remote_ip'] : '',
                ]));

                $user = self::getUser($redis,$sessionId);
                Kit::debug("pokerReceive-toAll---------user:".print_r($user,1),'debug.log');

                if(empty($user)) {
                    Kit::debug("pokerReceive-toAll---------login again",'debug.log');

                    return $server->push($fd,Kit::json_response(code::LOGIN_RELOAD,'重新登录'));
                } else {
                    $time    = date("H:i:s");
                    foreach($server->connections as $thisFd) {
                        $server->push($thisFd,Kit::json_response(code::MSG_ALL_SUCCESS,'ok',[
                            'msg'=> $msg,
                            'member_id'=> $userInfo->memberId,
                            'nickname'=> $userInfo->nickname,
                            'is_vip'=> $userInfo->isVip,
                            'session_id'=>$sessionId,
                            'time'=>$time,
                            'fd'=>$fd,
                        ]));
                    }

                    Kit::debug("pokerReceive-toAll---------msg-success",'debug.log');
                }
            } else {
                Kit::debug("pokerReceive-toAll---------msg-null-error",'debug.log');

                $redis->expire($key,5);
                $server->push($fd,Kit::json_response(code::MSG_ALL_NULL_ERROR,'不能发送空消息！',[
                    'msg'  =>'不能发送空消息！',
                    'icon' =>"http://pics.sc.chinaz.com/Files/pic/icons128/5938/i6.png",
                    'fd'   =>$fd,
                ]));
            }
        }
    }
}