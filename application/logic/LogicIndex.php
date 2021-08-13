<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 20:01
 */

namespace app\logic;

use app\common\char_room;
use app\logic\model\MsgQueue;
use My\Kit;

class LogicIndex
{
    /**
     * @param $server
     * @param $frame
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public static function run($server,$frame)
    {
        $data = json_decode($frame->data,true);

        switch ($data['code']){
            case char_room::LOGIN:
                $class = new Login();
                break;
            case char_room::ALL: //世界聊天
                $class = new ChatAll();
                break;
            case char_room::CLUB: //公会
                $class = new ChatClub();
                break;
            case char_room::PERSON: //私聊
                $class = new ChatPerson();
                break;
            case char_room::ADD_FRIEND: //添加好友
                $class = new AddFriend();
                break;
            case char_room::HEART: //心跳
                $class = new HeartBeat();
                break;
            case char_room::FRIENDS: //好友列表
                $class = new Friends();
                break;
            case char_room::DEL_FRIEND: //删除好友
                $class = new DelFriend();
                break;
            case char_room::RECEIVE_FRIEND: //接受好友
                $class = new ReceiveFriend();
                break;
            case char_room::IS_FRIEND: //是否是好友
                $class = new IsFriend();
                break;
            default:
                $class = null;
                break;
        }

        if( $class )
            $class->main($server,$frame);

    }
}