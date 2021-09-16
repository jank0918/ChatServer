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
use app\server\model\Club;
use My\Kit;

class ChatClub extends Logic
{
    const HOLD_TIME = 5; //发送消息冷却时间
    /**
     * @param $server
     * @param $frame
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function main($server, $frame)
    {
        Kit::debug("ChatClub---------start",'debug.log');

        $redis  = $this->redis();
        $fd     = $frame->fd;
        $key    = Model_Keys::pokerReceive($fd);

        $frameData = $this->getFrameData($frame->data);
        Kit::debug("ChatClub---------frameData::".print_r($frameData,1),'debug.log');

        $sessionId  = $frameData['session_id'];
        $data = UserInfo::getDataFromRedis($redis,Model_Keys::uinfo($sessionId));
        $userInfo = new UserInfo($data);

        $redis = $this->redis(0,1);
        if($redis->sismember(redis_key::limit_list,$userInfo->memberId)){
            return $server->push($fd,Kit::json_response(code::CODE_LIMIT,'您已被禁言！'));
        }

        Kit::debug("ChatClub---------userinfo::".print_r($data,1),'debug.log');

        $redis  = $this->redis();
        if(!$redis->SETNX($key,1)) {
            $next = intval($redis->TTL($key));
            Kit::debug("ChatClub---------start--msg-hold",'debug.log');
            $server->push($fd,Kit::json_response(code::MSG_CLUB_HOLD,'消息时间冷却',[
                'msg'  =>'还有'.$next.'s可以发送消息',
                'nick' => '系统消息',
                'fd'   =>$fd,
            ]));
        }else{
            $redis->expire($key,self::HOLD_TIME);
            Kit::debug("ChatClub---------session:".$sessionId,'debug.log');

            $msg = $frameData['msg'];
            $msg = trim($msg);
            $msg = htmlspecialchars_decode($msg);
            $msg = preg_replace("/<(.*?)>/","",$msg);
            $msg = mb_strlen($msg,'utf-8') > 100 ? mb_substr($msg,0,100) : $msg;
            if($msg !== "") {
                Kit::debug("ChatClub---------msg right",'debug.log');

                $info = $server->getClientInfo($fd);
                Kit::debug("ChatClub---------getClientInfo:".print_r($info,1),'debug.log');

                $server->task(json_encode([
                    'msg'=> $msg,
                    'fd'=>$fd,
                    'ip' =>isset($info['remote_ip']) ? $info['remote_ip'] : '',
                ]));

                $user = self::getUser($redis,$sessionId);
                Kit::debug("ChatClub---------user:".print_r($user,1),'debug.log');

                if(empty($user)) {
                    Kit::debug("ChatClub---------login again",'debug.log');

                    return $server->push($fd,Kit::json_response(code::LOGIN_RELOAD,'重新登录'));
                } else {
                    $time    = date("H:i:s");
                    $clubList = $this->getClubList($fd);
                    if(empty($clubList)){
                        return $server->push($fd,Kit::json_response(code::NO_CLUB,'未加入公会！'));
                    }

                    foreach($clubList as $thisMemberId) {
                        $thisSessionId = $this->getSessionByMember($thisMemberId);
                        $thisFd = $this->getFd($thisSessionId);
                        if(! $thisFd) continue;

                        $server->push($thisFd,Kit::json_response(code::MSG_CLUB_SUCCESS,'ok',[
                            'msg'=> $msg,
                            'member_id'=> $userInfo->memberId,
                            'nickname'=> $userInfo->nickname,
                            'is_vip'=> $userInfo->isVip,
                            'session_id'=>$sessionId,
                            'time'=>$time,
                            'fd'=>$fd,
                        ]));
                    }

                    Kit::debug("ChatClub---------msg-success",'debug.log');
                }
            } else {
                Kit::debug("ChatClub---------msg-null-error",'debug.log');

                $redis->expire($key,5);
                $server->push($fd,Kit::json_response(code::MSG_ALL_NULL_ERROR,'不能发送空消息！',[
                    'msg'  =>'不能发送空消息！',
                    'fd'   =>$fd,
                ]));
            }
        }
    }

    /**
     * @param $fd
     * @return bool|mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function getClubList($fd)
    {
        $memberId = $this->getMemberIdByFd($fd);
        Kit::debug("ChatClub---------getClubList:member_id".print_r($memberId,1),'debug.log');

        $club = new Club();
        $members = $club->get_club_members($memberId);
        Kit::debug("ChatClub---------getClubList:members".print_r($members,1),'debug.log');

        return $members;
    }
}