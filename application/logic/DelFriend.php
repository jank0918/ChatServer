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

class DelFriend extends Logic
{
    public function main($server, $frame)
    {
        Kit::debug("DelFriend---start".print_r($frame,1),'debug.log');

        $fd     = $frame->fd;

        $frameData = $this->getFrameData($frame->data);

        $otherId = $frameData['member_id'];
        $memberId = $this->getMemberIdByFd($fd);

        $res = friend::decreaseFriend($memberId,$otherId);
        Kit::debug("DelFriend---res".print_r($res,1),'debug.log');

        $server->push($fd,Kit::json_response(code::MSG_DEL_FRIEND_RESPONSE,'删除好友成功',[
            'msg'=> "del friend success",
            'fd'=>$frame->fd,
        ]));

        Kit::debug("Friends-end",'debug.log');
    }
}