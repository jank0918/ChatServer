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

class IsFriend extends Logic
{
    public function main($server, $frame)
    {
        Kit::debug("IsFriend---start".print_r($frame,1),'debug.log');

        $fd     = $frame->fd;

        $frameData = $this->getFrameData($frame->data);

        $otherId = $frameData['member_id'];
        $memberId = $this->getMemberIdByFd($fd);

        $res = friend::getIsFriend($memberId,$otherId);
        Kit::debug("IsFriend---res".print_r($res,1),'debug.log');

        $server->push($fd,Kit::json_response(code::MSG_IS_FRIEND_SUCCESS,'success',[
            'fd'        =>$frame->fd,
            'is_friend' =>$res,
            'member_id' =>$memberId,
            'other_id'  =>$otherId
        ]));

        Kit::debug("IsFriend-end",'debug.log');
    }
}