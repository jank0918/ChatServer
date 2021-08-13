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
use My\Kit;
use My\RedisPackage;
use think\Model;

class Club extends Model{

    // 设置当前模型的数据库连接
    protected $connection = 'db.db2';

    /**
     * @param $memberId
     * @return bool|mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function get_club_members($memberId)
    {
        $posData = self::get_club_member_position($memberId);
        Kit::debug("ChatClub---------getClubList:posData".print_r($posData,1),'debug.log');

        if($posData['position'] == 0){
            return false;
        }

        $clubId = $posData['hqId'];
        $members = self::get_members($clubId);
        Kit::debug("ChatClub---------getClubList:get_members".print_r($members,1),'debug.log');

        return array_column($members,'member_id');
    }

    /**
     * @param $memberId
     * @return array|bool
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function get_club_member_position($memberId)
    {
        $sql = "SELECT `organize_hq_id`, `position`, UNIX_TIMESTAMP(`join_date`) as `join_time` FROM `tbl_member_organize` WHERE `member_id`='{$memberId}' AND `position` > 0";
        Kit::debug("ChatClub---------get_club_member_position-sql".print_r($sql,1),'debug.log');

        $tmp = self::query($sql);
        Kit::debug("ChatClub---------get_club_member_position-tmp".print_r($tmp,1),'debug.log');

        return (empty($tmp) ? false : array(
            'hqId'		=> (int)$tmp[0]['organize_hq_id']
        ,	'position'	=> (int)$tmp[0]['position']
        ,	'join_time'	=> (int)$tmp[0]['join_time']
        )
        );
    }

    /**
     * @param $clubId
     * @return bool|mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function get_members($clubId)
    {
        $_position	= join(',', array(1, 9));
        $_sql		= "SELECT
 `t1`.`member_id`
FROM `tbl_member_organize` `t1`, tbl_member `t2`
WHERE `t1`.`organize_hq_id`={$clubId}
AND `t1`.`position` IN({$_position})
AND `t1`.`member_id`=`t2`.`member_id`";
        Kit::debug("ChatClub---------getClubList:get_members-sql".print_r($_sql,1),'debug.log');

        $members	= self::query($_sql);
        Kit::debug("ChatClub---------getClubList:get_members-sql-members".print_r($members,1),'debug.log');

        return $members;
    }
}