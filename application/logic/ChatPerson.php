<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 14:09
 */

namespace app\logic;


use app\common\code;
use app\common\friend;
use app\index\model\Model_Keys;
use My\Kit;

class ChatPerson extends Logic
{
    /** 私聊类型 */
    const FRIEND = 1; // 好友聊天
    const PRIVET = 2; // 私聊

    public function main($server, $frame)
    {
        Kit::debug("pokerReceive-ChatPerson---------start",'debug.log');

        $fd     = $frame->fd;

        $frameData = $this->getFrameData($frame->data);
        Kit::debug("pokerReceive-ChatPerson---------frameData::".print_r($frameData,1),'debug.log');

        $sessionId  = $frameData['session_id'];
        $type = $frameData['type'];// 1好友 2私聊
        $otherId = $frameData['member_id'];
        $memberId = $this->getMemberIdByFd($fd);

        $userInfo   = $this->getUserInfoBySession($sessionId);

        $toSession = $this->getSessionByMember($otherId);
        Kit::debug("pokerReceive-ChatPerson---------to_session::".$toSession,'debug.log');

        $toFd = $this->getFd($toSession);
        Kit::debug("pokerReceive-ChatPerson---------to_fd::".$toFd,'debug.log');

        if( empty($toFd) ){
            Kit::debug("pokerReceive-ChatPerson---------other_id::".$otherId,'debug.log');

            return $server->push($frame->fd,Kit::json_response(code::USER_NOT_ONLINE,'目标用户不在线'));
        }

        $msg = trim($frameData['msg']);
        $msg = htmlspecialchars_decode($msg);
        $msg = preg_replace("/<(.*?)>/","",$msg);
        $msg = mb_strlen($msg,'utf-8') > 100 ? mb_substr($msg,0,100) : $msg;

        if($msg !== "") {
            Kit::debug("pokerReceive-ChatPerson---------44",'debug.log');

            $user = self::getUser($this->redis(),$sessionId);
            if(empty($user)) {
                return $server->push($frame->fd,Kit::json_response(code::LOGIN_RELOAD,'重新登录'));
            } else {
                Kit::debug("pokerReceive-ChatPerson---------55",'debug.log');

                $isFriend = friend::getIsFriend($memberId,$otherId);
                $time    = date("H:i:s");

                if(($type == self::FRIEND && $isFriend == friend::FRIEND) || $type == self::PRIVET){

                    $server->push($toFd,Kit::json_response(code::MSG_PERSON_RECEIVE_SUCCESS,'私聊消息接收成功',[
                        'msg'       => $msg,
                        'time'      => $time,
                        'nickname'  => $userInfo->nickname,
                        'fd'        => $toFd,
                        'member_id' => $otherId,
                        'other_id'  => $memberId,
                        'type'      => $type,
                        'is_friend' => $isFriend,
                        'f_session' => $sessionId
                    ]));


                    $server->push($fd,Kit::json_response(code::MSG_PERSON_SUCCESS,'私聊消息发送成功',[
                        'msg'       => $msg,
                        'time'      =>$time,
                        'fd'        =>$frame->fd,
                        'type'      => $type,
                        'is_friend' => $isFriend,
                        'member_id' => $memberId,
                        'other_id'  => $otherId,
                    ]));

                } else {

                    $server->push($fd,Kit::json_response(code::PERSON_NOT_FRIEND,'你不是对方的好友,不能发送好友消息',[
                        'msg'       => "对方不是你的好友,不能发送好友消息",
                        'time'      =>$time,
                        'fd'        =>$frame->fd,
                        'is_friend' => $isFriend,
                    ]));

                }

            }
        } else {
            $server->push($frame->fd,Kit::json_response(code::PARAM_ERROR,'不能发送空消息！',[
                'msg'  =>'不能发送空消息！',
                'fd'   =>$frame->fd,
            ]));
        }
    }
}