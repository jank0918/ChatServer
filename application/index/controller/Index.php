<?php
/**
 * Created by PhpStorm.
 * User: Kevin
 * Date: 2018/7/5
 * Time: 15:18
 */
namespace app\index\controller;
use app\common\code;
use app\index\model\Model_Keys;
use app\index\model\Model_Upload;
use app\server\command\Swoole;
use app\server\model\asyncTask;
use My\Kit;
use My\Randomname;
use My\RedisPackage;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;
use think\View;

class Index extends Controller {

    public $memberId;

    public function index() {
        Swoole::getSessid();
        $request = Request::instance();
        $msg = json_encode([
            'msg'=>"【用户登陆】|CLASS:".__CLASS__."|Func:".__FUNCTION__,
            'fd'=>0,
            'ip'=> $request->ip()
        ]);
        asyncTask::LogToDb(null, null, null, $msg);
        $view = new View();
        return $view->fetch('v2');
    }

    /**
     * @param $memberId
     * @throws \think\Exception
     */
    public function login($memberId) {
        $this->memberId = $memberId;
        if(! $this->checkUser() ){
            echo Kit::json_response(code::USER_NOT_FIND,'玩家不存在',null,true);
            exit;
        }
        $token = Swoole::getToken($this->memberId);

        $request = Request::instance();

        $msg = json_encode([
            'msg'=>"【用户登陆】|CLASS:".__CLASS__."|Func:".__FUNCTION__,
            'fd'=>0,
            'ip'=> $request->ip()
        ]);

        asyncTask::LogToDb(null, null, null, $msg);

        echo json_encode(['status'=>code::OK,'msg'=>'登录成功','token'=>$token]);exit;
    }

    public function shake() {
        if( $this->request->isAjax()) {
            try {
                $PlayerLogObj = asyncTask::getDbObj();
                //百分之60的概率获取一条记录
                if(mt_rand(0,100) <= 60) {
                    return Kit::json_response(-1,'');
                }

                list($status,$ret) = $PlayerLogObj->getOneLog();
                if(!$status) {
                    echo Kit::json_response(-1,'',null,true);
                }

                $icon = Swoole::getIconByFd($ret['fd']);
                echo Kit::json_response(1,'ok',['icon'=>$icon,'msg'=>$ret['msg']],true);
            }catch (\Exception $e) {
                echo Kit::json_response(-1,$e->getMessage());
            }
        } else {
            $msg = json_encode(['msg'=>"【摇一摇】|CLASS:".__CLASS__."|Func:".__FUNCTION__,'fd'=>0]);
            asyncTask::LogToDb(null, null, null, $msg);

            $view = new View();
            return $view->fetch('shake');
        }

    }

    public function upload() {
        $redis  = new RedisPackage([],1);
        $sessid = Swoole::getSessid();

        $user   = Swoole::getUser($redis,$sessid);
        $key    = Model_Keys::pokerReceive($sessid);
        if(empty($user)) {
            echo Kit::json_response(-1,'先链接服务器',null,true);
        } else {
            if(!$redis->SETNX($key,1)) {
                $redis->expire($key,100);
                echo Kit::json_response(-1,'慢点、不要无节操',null,true);
            } else {
                $redis->expire($key,5);
                $file  = isset($_FILES['img']) ? $_FILES['img'] : "";//得到传输的数据
                if(!empty($file['tmp_name'])) {
                    list($istatus,$img) = Model_Upload::uploadToLocal($file);
                    if(!$istatus) {
                        echo Kit::json_response(-1,$img,null,true);
                    } else {
                        echo Kit::json_response(1000,'ok',['img'=>$img]);
                    }
                } else {
                    echo Kit::json_response(-1,'图片最大不超过1~2M<br /> 压缩图片地址：<a href=\'https://tinypng.com/\' target=\'_blank\'>https://tinypng.com/</a>');
                }
            }
        }

    }

    public function modify() {
        $redis  = new RedisPackage([],1);
        $sessid = Swoole::getSessid();
        $ukey   = Model_Keys::uinfo($sessid);
        $userStr = $redis->get($ukey);
        $user    = json_decode($userStr,true);

        $key    = Model_Keys::pokerReceive($sessid);
        if(empty($user)) {
            echo Kit::json_response(code::SERVER_NOT_CONNECT,'先链接服务器',null,true);
        } else {
            if(!$redis->SETNX($key,1)) {
                $redis->expire($key,100);
                echo Kit::json_response(code::OPERATE_ERROR,'慢点、不要无节操',null,true);
            } else {
                $redis->expire($key,5);
                $file  = isset($_FILES['file']) ? $_FILES['file'] : "";//得到传输的数据
                $img   = "";
                if(!empty($file['tmp_name'])) {
                    list($istatus,$img) = Model_Upload::uploadToLocal($file);
                    if(!$istatus) {
                        echo Kit::json_response(code::ERROR,$img,null,true);
                    } else {
                        $user['icon'] = $img;
                    }
                }

                $nick = Request::instance()->post('nick');
                if(!empty($nick)) {
                    $nick = htmlspecialchars_decode($nick);
                    $nick = preg_replace("/<(.*?)>/","",$nick);
                    $nick = mb_strlen($nick,'utf-8') > 15 ? mb_substr($nick,0,16) : $nick;
                    $user['nick'] = $nick;
                } else {
                    $nick = Randomname::createName();
                }

                $ip = Request::instance()->ip();

                asyncTask::LogUserInfoToDb($user['fd'],$img,$nick,$ip);

                $redis->SETEX($ukey,600,json_encode($user));
                echo Kit::json_response(code::SUCCESS,'ok',['nick'=>$nick,'icon'=>$img]);
            }
        }
    }

    /**
     * @return bool
     * @throws \think\Exception
     */
    public function checkUser()
    {
        $user = Db::connect('db.db2')->name('tbl_member')->where('member_id',$this->memberId)->find();
        if(! $user ){
            return false;
        }

        return true;
    }
}