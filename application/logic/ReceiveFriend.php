<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 14:09
 */

namespace app\logic;


use app\common\code;
use My\Kit;
use app\common\friend;

class ReceiveFriend extends Logic
{
    private $to_is_online = true;
    public function main($server, $frame)
    {
        $fd     = $frame->fd;
        $redis  = $this->redis();

        $frameData = $this->getFrameData($frame->data);
        Kit::debug("pokerReceive-toAll---------frameData::".print_r($frameData,1),'debug.log');

        $isReceive = $frameData['is_receive'];
        $sessionId  = $frameData['session_id'];

        $otherId = $frameData['member_id'];
        $toSessionId = $this->getSessionByMember($otherId);
        $toFd = $this->getFd($toSessionId);
        if(! $toFd )
            $this->to_is_online = false;


        $user = self::getUser($redis,$sessionId);
        if(empty($user))
            return $server->push($frame->fd,Kit::json_response(code::LOGIN_RELOAD,'重新登录'));

        $memberId = $this->getMemberIdByFd($fd);

        $time    = date("H:i:s");
        if(! $isReceive){
            return $server->push($fd,Kit::json_response(code::REFUSE_ADD_FRIEND,'好友申请已驳回',[
                'msg'=> "好友申请已驳回",
                'time'=>$time,
                'fd'=>$frame->fd,
            ]));
        }

        /** 互相添加好友 */
        $res = friend::increaseFriend($memberId,$otherId);
        Kit::debug("pokerReceive-todAll---------receive::".print_r($res,1),'debug.log');

        $server->push($fd,Kit::json_response(code::ADD_FRIEND_SUCCESS,'添加好友成功',[
            'msg'=> "添加好友成功",
            'time'=>$time,
            'fd'=>$frame->fd,
        ]));

        if($this->to_is_online){
            $server->push($toFd,Kit::json_response(code::ADD_FRIEND_SUCCESS,'添加好友成功',[
                'msg'=> "添加好友成功",
                'time'=>$time,
                'fd'=>$toFd,
            ]));
        }

        return true;
    }
}