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
use My\Kit;

/**
 * 好友列表
 *
 * Class Friends
 * @package app\logic
 */
class Friends extends Logic
{
    public function main($server, $frame)
    {
        Kit::debug("Friends--start",'debug.log');

        $fd     = $frame->fd;

        $memberId = $this->getMemberIdByFd($fd);
        $friends = friend::getFriendList($memberId);
        if(! $friends){
            return $server->push($frame->fd,Kit::json_response(code::NO_FRIENDS,'暂无好友哦'));
        }

        Kit::debug("Friends-data".json_encode($friends),'debug.log');

        $data = array();
        foreach ($friends as $key=> $val){
            $data[$key]['member_id'] = $val;
            $data[$key]['is_online'] = $this->is_online($val);
        }

        Kit::debug("Friends-list".json_encode($data),'debug.log');

        $server->push($fd,Kit::json_response(code::MSG_FRIENDS_LIST_RESPONSE,'好友列表',[
            'msg'=> "好友列表",
            'list'=> $data,
            'fd'=>$fd,
        ]));

        Kit::debug("Friends-end",'debug.log');
    }
}