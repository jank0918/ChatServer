<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/21
 * Time: 19:22
 */

namespace app\logic;


use app\common\code;
use My\Kit;

class HeartBeat
{
    public function main($server, $frame)
    {
        $date_time = date("Y-m-d H:i:s");
        Kit::debug($date_time."-....heart beat....",'heart.beat.log');
        $server->push($frame->fd,Kit::json_response(code::HEART_BEAT,'heart beat',[
            'msg'  =>'heart beat',
            'fd'   =>$frame->fd,
            'time' =>$date_time,
        ]));
    }
}