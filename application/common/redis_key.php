<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/18
 * Time: 14:42
 */

namespace app\common;


class redis_key
{
    const login_hash_key    = 'login_hash_key';

    const fd_memberId       =  'fd_member_id';

    const msg_queue         = 'msg_queue';

    const add_friend_key    = 'add_friend_key_';

    const limit_list        = 'limit_list';
}