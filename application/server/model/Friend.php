<?php
/**
 * project: poker
 * Created by PhpStorm.
 * Author: xjc
 * Date: 2018/1/18
 * Time: 15:21
 * File: User.php
 */
namespace app\server\model;

use app\index\model\Model_Keys;
use My\RedisPackage;
use think\Model;

class Friend extends Model{

    // 设置当前模型的数据库连接
    protected $connection = 'db.db1';

    const FRIEND_STATUS_SEND    = 0; //申请
    const FRIEND_STATUS_REPLY   = 1; //确认
    const FRIEND_STATUS_REFUSE  = 2; //拒绝
    const FRIEND_STATUS_DEL     = 3; //删除

    /**
     * @param $data
     * @return int
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function add($data)
    {
        $initiator  = $data['initiator'];
        $received   = $data['received'];
        $time       = date("Y-m-d H:i:s");
        $status     = self::FRIEND_STATUS_SEND;

        return self::execute(
            'insert into t_friends (initiator, received, time, status) values (:initiator, :received, :time, :status)',
            [
                'initiator'=>$initiator,
                'received'=>$received,
                'time'=>$time,
                'status'=>intval($status),
            ]
        );

    }

    /**
     * 获取好友列表
     *
     * @param $personId
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function getFriendList($personId) {
        $sql_init  = "SELECT initiator FROM `t_friends` WHERE  `initiator`={$personId} init_status=1 ORDER BY time DESC";
        $list_init = self::query($sql_init);

        $sql_rec  = "SELECT received FROM `t_friends` WHERE  `received`={$personId} rec_status=1 ORDER BY time DESC";
        $list_rec = self::query($sql_rec);

        $friends = array();
        foreach ($result as $key=>$value){
            if($value['initiator']){
                $friends[$key]['member_id'] = $value['received'];
            }

            if($value['received']){
                $friends[$key]['member_id'] = $value['initiator'];
            }
        }

        return $friends;
    }

    public function isFriend($memberId,$otherId)
    {
        $sql = "select id from t_friends where (initiator='{$memberId}' and received='{$otherId}') or (received='{$memberId}' and initiator='{$otherId}') and status=1";
        $res = self::query($sql);

        return $res ? true : false;
    }

    /**
     * @param $memberId
     * @param $otherId
     * @param $status
     * @return int
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function updateFriendStatus($memberId,$otherId,$status)
    {
        $time = date("Y-m-d H:i:s");
        $sql = "update t_friends set status={$status},update_time='{$time}' where (initiator='{$memberId}' and received='{$otherId}') or (received='{$memberId}' and initiator='{$otherId}')";

        return self::execute($sql);
    }
}