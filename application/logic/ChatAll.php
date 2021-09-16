<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 14:09
 */

namespace app\logic;


use app\common\code;
use app\index\model\Model_Keys;
use app\logic\model\UserInfo;
use My\Kit;

class ChatAll extends Logic
{
    const HOLD_TIME = 1; //发送消息冷却时间
    public $limit = array();
    public function main($server, $frame)
    {
        Kit::debug("pokerReceive-toAll---------start",'debug.log');

        $fd     = $frame->fd;
        $key    = Model_Keys::pokerReceive($fd);

        $frameData = $this->getFrameData($frame->data);
        Kit::debug("pokerReceive-toAll---------frameData::".print_r($frameData,1),'debug.log');

        $sessionId  = trim($frameData['session_id']);
        $redisKey = Model_Keys::uinfo($sessionId);
        Kit::debug("pokerReceive-toAll---------redisKey::".$redisKey,'debug.log');
        Kit::debug("pokerReceive-toAll---------redisData::".$server->worker_id.'---'.$this->redis($server->worker_id)->get($redisKey),'debug.log');

        $data = UserInfo::getDataFromRedis($this->redis(),$redisKey);
        Kit::debug("pokerReceive-toAll---------data::".print_r($data,1),'debug.log');

        if(in_array($data['member_id'],$this->limit)){
            return $server->push($fd,Kit::json_response(code::CODE_LIMIT,'您已被禁言！'));
        }
        $isSet = $this->redis()->setnx($key,1);
        if( empty($isSet) ) {
            Kit::debug("pokerReceive-toAll---------start--hold--".$isSet,'debug.log');

            $next = intval($this->redis()->ttl($key));
            Kit::debug("pokerReceive-toAll---------start--msg-hold",'debug.log');
            $server->push($fd,Kit::json_response(code::MSG_ALL_HOLD,'消息时间冷却',[
                'msg'  =>'还有'.$next.'s可以发送消息',
                'nick' => '系统消息',
                'fd'   =>$fd,
            ]));
        }else{
            Kit::debug("pokerReceive-toAll---------start--expire--".$isSet,'debug.log');

            $this->redis()->expire($key,self::HOLD_TIME);
            Kit::debug("pokerReceive-toAll---------session:".$sessionId,'debug.log');

            $msg = $frameData['msg'];
            $msg = trim($msg);
            if( ! empty($msg) ) {
                Kit::debug("pokerReceive-toAll---------msg right",'debug.log');

                $info = $server->getClientInfo($fd);
                Kit::debug("ChatClub---------getClientInfo:".print_r($info,1),'debug.log');

                $server->task(json_encode([
                    'msg'=> $msg,
                    'fd'=>$fd,
                    'ip' =>isset($info['remote_ip']) ? $info['remote_ip'] : '',
                ]));

                $user = self::getUser($this->redis(),$sessionId);
                Kit::debug("pokerReceive-toAll---------user:".print_r($user,1),'debug.log');

                if(empty($user)) {
                    Kit::debug("pokerReceive-toAll---------login again",'debug.log');

                    return $server->push($fd,Kit::json_response(code::LOGIN_RELOAD,'重新登录'));
                } else {
                    $time    = date("H:i:s");
                    foreach($server->connections as $thisFd) {
                        if(!$server->isEstablished($thisFd)){
                            Kit::debug("pokerReceive-toAll---------not online--:".$thisFd,'online.log');
                            continue;
                        }

                        $server->push($thisFd,Kit::json_response(code::MSG_ALL_SUCCESS,'ok',[
                            'msg'=> $msg,
                            'member_id'=> $data['member_id'],
                            'nickname'=> $data['nickname'],
                            'is_vip'=> $data['is_vip'],
                            'session_id'=>$sessionId,
                            'time'=>$time
                        ]));
                    }

                    Kit::debug("pokerReceive-toAll---------msg-success",'debug.log');
                }
            } else {
                $server->push($fd,Kit::json_response(code::MSG_ALL_NULL_ERROR,'不能发送空消息！',[
                    'msg'  =>'不能发送空消息！',
                    'fd'   =>$fd,
                ]));
            }
        }
    }
}