<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/19
 * Time: 11:29
 */

namespace app\common;


class char_room
{
    /** @var int 世界聊天 */
    const ALL = 0;

    /** @var int 公会聊天 */
    const CLUB = 1;

    /** @var int 私聊 */
    const PERSON = 2;

    /** @var int 添加好友 */
    const ADD_FRIEND = 3;

    /** @var int 好友列表 */
    const FRIENDS = 4;

    /** @var int 删除好友 */
    const DEL_FRIEND = 5;

    /** @var int 接受好友 */
    const RECEIVE_FRIEND=6;

    /** @var int 是否是好友 */
    const IS_FRIEND=7;

    /** @var int 连接 */
    const CONNECT = 20;

    /** @var int 登录 */
    const LOGIN = 10;

    /** @var int 心跳 */
    const HEART = 100;
}