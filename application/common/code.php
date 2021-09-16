<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/18
 * Time: 14:42
 */

namespace app\common;


class code
{
    /** 成功返回 */
    const OK = 0;

    /** @var int 操作成功 */
    const SUCCESS                       = 1;
    const HEART_BEAT                    = 100; //心跳协议
    const CONNECT_SUCCESS               = 10000;//连接成功
    const LOGIN_SUCCESS                 = 11000;//登录成功
    const MSG_ALL_SUCCESS               = 11001;//世界消息发送成功
    const ADD_FRIEND_SEND               = 11002;//添加好友消息发送
    const ADD_FRIEND_RECEIVE            = 11003;//添加好友消息接收
    const REFUSE_ADD_FRIEND             = 11004;//拒绝好友
    const ADD_FRIEND_SUCCESS            = 11005;//添加好友成功
    const MSG_PERSON_SUCCESS            = 11006;//私聊消息发送成功
    const MSG_PERSON_RECEIVE_SUCCESS    = 11007;//私聊消息接收成功
    const MSG_FRIENDS_LIST_RESPONSE     = 11008;//好友列表返回成功
    const MSG_DEL_FRIEND_RESPONSE       = 11009;//删除好友成功
    const MSG_CLUB_SUCCESS              = 11010;//公会消息发送成功
    const MSG_IS_FRIEND_SUCCESS         = 11011;//是否是好友

    /** @var int 操作失败 */
    const ERROR                 = -1;
    const MSG_ALL_NULL_ERROR    = -100; //世界空消息
    const MSG_ALL_HOLD          = -101; //世界消息时间冷却
    const MSG_CLUB_NULL_ERROR   = -102; //公会空消息
    const MSG_CLUB_HOLD         = -103; //公会空消息

    /** @var int 服务器主动关闭连接 */
    const CONNECT_CLOSE = 999;

    /** @var int 服务器未连接 */
    const SERVER_NOT_CONNECT = 1000;

    /** @var int 用户不存在 */
    const USER_NOT_FIND = 10001;

    /** @var int 操作有误 */
    const OPERATE_ERROR = 10002;

    /** @var int  */
    const TOKEN_IS_EMPTY = 10003;

    /** @var int  参数错误*/
    const PARAM_ERROR = 10004;

    /** @var int  重复登录*/
    const REPEAT_LOGIN = 10005;

    /** 目标用户不在线 */
    const USER_NOT_ONLINE = 10006;

    /** @var int 用户黑名单 */
    const IP_BLACK_LIST = 10007;

    /** @var int 请重新登录 */
    const LOGIN_RELOAD = 10008;

    /** @var int 添加好友失败 */
    const ADD_FRIEND_FAIL = 10009;

    /** 好友请求已经发送给对方，请勿重复操作 */
    const ADD_FRIEND_REPEAT = 10010;

    /** 好友列表为空 */
    const NO_FRIENDS = 10011;

    /** @var int 已经是好友关系，无需添加好友 */
    const HAS_FRIEND = 10012;

    /** 对方不是你的好友 */
    const PERSON_NOT_FRIEND = 10013;

    /** 账号异地登录 */
    const ACCOUNT_LOGIN_OTHER = 10014;

    /** @var int 发起聊天类型不存在 */
    const CODE_IS_NOT_EXISTS = 10015;

    /** @var int 禁言 */
    const CODE_LIMIT = 10016;

    /** @var int 没加入公会 */
    const NO_CLUB = 10017;
}