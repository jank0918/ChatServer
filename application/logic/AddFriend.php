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
use app\common\redis_key;
use app\index\model\Model_Keys;
use app\logic\model\UserInfo;
use My\Kit;

class AddFriend extends Logic
{
    const TIME = 2;

    public function main($server, $frame)
    {
        $fd     = $frame->fd;

        $frameData = $this->getFrameData($frame->data);
        Kit::debug("pokerReceive-AddFriend---------frameData::".print_r($frameData,1),'debug.log');

        $sessionId  = $frameData['session_id'];
        $data = UserInfo::getDataFromRedis($this->redis(),Model_Keys::uinfo($sessionId));
        $userInfo = new UserInfo($data);
        Kit::debug("pokerReceive-AddFriend---------userInfo::".print_r($userInfo,1),'debug.log');

        $otherId = $frameData['member_id'];
        $toSessionId = $this->getSessionByMember($otherId);
        $toFd = $this->getFd($toSessionId);
        if(! $toFd ){
            return $server->push($frame->fd,Kit::json_response(code::USER_NOT_ONLINE,'目标用户不在线'));
        }
        Kit::debug("pokerReceive-AddFriend---------otherId::".print_r($otherId,1),'debug.log');

        $memberId = $this->getMemberIdByFd($fd);
        Kit::debug("pokerReceive-AddFriend---------memberId::".print_r($memberId,1),'debug.log');

        if(friend::getIsFriend($memberId,$otherId) == 1){
            return $server->push($frame->fd,Kit::json_response(code::HAS_FRIEND,'已经是好友关系，无需添加好友'));
        }

        /** 对方未同意之前只能发起一次 */
        $cacheFd = $this->redis()->get(redis_key::add_friend_key.$fd."_".$toFd);
        if($cacheFd){
            return $server->push($frame->fd,Kit::json_response(code::ADD_FRIEND_REPEAT,'好友请求已经发送给对方，请勿重复操作'));
        }
        Kit::debug("pokerReceive-AddFriend---------cacheFd::".print_r($cacheFd,1),'debug.log');

        $user = self::getUser($this->redis(),$data['session_id']);
        if(empty($user)) {
            return $server->push($frame->fd,Kit::json_response(code::LOGIN_RELOAD,'重新登录'));
        } else {
            Kit::debug("pokerReceive-AddFriend---------55",'debug.log');

            $time    = date("H:i:s");
            $server->push($toFd,Kit::json_response(code::ADD_FRIEND_RECEIVE,'好友申请',[
                'msg'       => "好友申请",
                'time'      => $time,
                'nickname'  => $userInfo->nickname,
                'member_id' => $userInfo->memberId,
                'fd'        => $toFd,
            ]));

            $server->push($fd,Kit::json_response(code::ADD_FRIEND_SEND,'好友申请发送成功',[
                'msg'=> "好友申请发送成功",
                'time'=>$time,
                'fd'=>$frame->fd,
            ]));
            Kit::debug("pokerReceive-AddFriend---------666-end",'debug.log');

            $this->redis()->set(redis_key::add_friend_key.$fd."_".$toFd,1);
            $this->redis()->expire(redis_key::add_friend_key.$fd."_".$toFd,self::TIME);
        }
    }
}