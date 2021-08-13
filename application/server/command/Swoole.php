<?php
/**
 * Created by PhpStorm.
 * User: KevinLin
 * Date: 2017/4/10
 * Time: 16:53
 * http 请求格式业务层代码格式： http://poker.com/index/swoole/server
 *
 * 服务器启动：
 * service nginx  restart
 * service redis  restart
 * service mysqld restart
 *
 * 查看nginx 所属组
 * ps aux | grep nginx
 * www ***** worker
 *
 * chmod -R 755 /var/www/html/poker
 * chown -R www.www /var/www/html/poker
 *
 * cli命令启动server： cd  /var/www/html/poker  &&  php think start
 *
 * shell 计划任务
 * # 每5分钟监控牌局server
 * /5 * * * * cd /var/www/html/poker && /usr/bin/php think swoole -m "monitor"
 * # 每小时执行一次 重启一下worker
 * 1 * * * *   cd /var/www/html/poker && /usr/bin/php think swoole -m "reload"
 *
 * swoole 总结
 * 当worker进程内发生致命错误或者人工执行exit时，进程会自动退出。
 * master进程会重新启动一个新的worker进程来继续处理请求
 *
 * 暴力杀死全部进程【不建议用于生成环境】
 * ps  aux | grep  Swoole_of_poker  | awk '{print $2}' | xargs kill -9
 *
 * #重启所有worker进程
 * kill -USR1 主进程PID
 *
 */
namespace app\server\command;

use app\common\char_room;
use app\common\code;
use app\common\redis_key;
use app\index\model\Model_Keys;
use app\logic\LogicIndex;
use app\logic\Login;
use app\server\model\asyncTask;
use My\RedisPackage;
use think\console\Command;
use think\console\input\Option;
use My\Kit;
use think\console\Input;
use think\console\output;
use app\logic\model\MsgQueue;

class Swoole extends Command {

    protected $process_name   = "Swoole_of_chatRoom"; //当前进程名称
    protected $master_pid_file = '/data/CharRoom/runtime/swoole_master_pid.txt'; //保存当前进程pid
    public static $md5Key = "";//自定义一个签名，暂时没用

    protected $redis;
    protected $option_name = 'opt';

    protected $server        = null;
    protected $swoole_ip   = "0.0.0.0";
    protected $swoole_port = 9801;

    protected $count_down_tick = 2;

    protected $block_ips = ['14.213.152.7' , '43.255.191.84'];

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public function __construct() {
        parent::__construct();
    }

    /**
     * 设置计划任务名称
     */
    protected function configure() {
        $this->addOption($this->option_name, 'm', Option::VALUE_OPTIONAL, 'start'); //选项值必填

        //设置命令启动的名称 php think Swoole -m "start"
        $this->setName('Swoole')->setDescription('Here is the swoole server ');
    }

    /**
     * @param Input $input
     * @param output $output
     * 执行入口
     * shell 实现无人值守
     */
    protected function execute(Input $input, Output $output) {
        $options = $input->getOptions();
        if(isset($options[$this->option_name])) {
            switch ($options[$this->option_name]) {
                case "start"    : $this->start();break;
                case "reload"   : $this->reload();break;
                case "monitor"  : $this->monitor();break;
                case "stop"     : $this->stop();break;
                default : die("Usage:{start|stop|reload|monitor}");
            }
        } else {
            die("缺少必要参数");
        }
    }

    /**
     * 子进程重启
     * 建议每小时一次
     */
    public function reload() {
        $master_pid = intval(Kit::getConfig($this->master_pid_file));
        if($master_pid) {
            $is_alive = \swoole_process::kill($master_pid, 0);
            if($is_alive === true) {

                exec("ps  aux | grep  {$this->process_name}  | awk '{print $2}'",$bpids);
                exec("kill -USR1 {$master_pid}",$retval, $status);
                exec("ps  aux | grep  {$this->process_name}  | awk '{print $2}'",$apids);

                //$status 0 是成功
                $debug  = "work reload Info >> before pids:".json_encode($bpids);
                $debug .= " |status:{$status}| Msg :".json_encode($retval,JSON_UNESCAPED_UNICODE);
                $debug .= " |after pids :".json_encode($apids);

                Kit::debug($debug,"server.reload");
            } else {
                Kit::debug("Server was not run","server.reload");
            }
        }
    }

    /**
     * 监控主进程状态
     * 建议5每分钟执行一次
     */
    public function monitor() {
        $master_pid = intval(Kit::getConfig($this->master_pid_file));
        if($master_pid) {
            $is_alive = \swoole_process::kill($master_pid, 0);
            if($is_alive === false) {
                Kit::debug("Server Not Start Now starting...","server.monitor");
                $this->start();
            } else {
                Kit::debug("Server is running... pid is {$master_pid}","server.monitor");
            }
        } else {
            Kit::debug("master_pid not exist, now starting server...","server.monitor");
            $this->start();
        }
    }

    /**
     * @param string $msg
     * 强制杀死server所有进程
     */
    public function dangerKill($msg=''){
        //强制杀死
        exec("ps  aux | grep  {$this->process_name}  | awk '{print $2}' | xargs kill -9",$retval, $status);

        Kit::debug("{$msg} Now This is Dangers Option|status：{$status}|retval:".json_encode($retval),"server.stop.dangers");
    }

    /**
     * 安全停止 server
     */
    public function stop() {
        if(file_exists($this->master_pid_file)) {
            $master_pid = intval(Kit::getConfig($this->master_pid_file));
            if($master_pid) {
                $is_alive = \swoole_process::kill($master_pid, 0);
                if($is_alive === true) {
                    $flag = \swoole_process::kill($master_pid, SIGTERM);
                    $msg  = $flag ? "终止进程成功" : "终止进程失败！！！";
                    Kit::debug($msg,"server.stop");
                } else {
                    $msg = "stop Server false!!!";
                    $this->dangerKill($msg);
                }
            }
        } else {

            exec("ps  aux | grep  {$this->process_name}  | awk '{print $2}'",$bpids);

            if(!empty($bpids)) {
                $msg = "master_pid_file not exist !!!!";
                $this->dangerKill($msg);
            }
        }
    }

    /**
     * 捕获Server运行期致命错误
     * 'https://wiki.swoole.com/wiki/page/305.html
     */
    public function handleFatal() {
        $error = error_get_last();
        $error = is_array($error) ? json_encode($error) : $error;
        Kit::debug($error,'handleFatal');
    }

    /**
     * 开启 server
     */
    public function start() {
        $redis  = new RedisPackage([],0);
        $redis->flushdb();

        \swoole_set_process_name($this->process_name);

        //\swoole_server 加反斜杠 表示当前类不在当前的命名空间内
        $this->server = new \swoole_websocket_server($this->swoole_ip, $this->swoole_port);
        $this->server->set(array(
            'reactor_num' => 6, //通过此参数来调节poll线程的数量，以充分利用多核
            'daemonize' => true, //加入此参数后，执行php server.php将转入后台作为守护进程运行,ps -ef | grep {this->process_name}
            'worker_num' => 10,//worker_num配置为CPU核数的1-4倍即可
            'dispatch_mode' => 2,//'https://wiki.swoole.com/wiki/page/277.html
            'max_request' => 1000,//此参数表示worker进程在处理完n次请求后结束运行，使用Base模式时max_request是无效的
            'backlog' => 1280,   //此参数将决定最多同时有多少个待accept的连接，swoole本身accept效率是很高的，基本上不会出现大量排队情况。
            'log_level' => 5,//'https://wiki.swoole.com/wiki/page/538.html
            'log_file' => '/data/CharRoom/runtime/log_file.'.date("Ym").'.txt',// 'https://wiki.swoole.com/wiki/page/280.html 仅仅是做运行时错误记录，没有长久存储的必要。
            'heartbeat_check_interval' => 30, //每隔多少秒检测一次，单位秒，Swoole会轮询所有TCP连接，将超过心跳时间的连接关闭掉
            'heartbeat_idle_time' => 3600, //TCP连接的最大闲置时间，单位s , 如果某fd最后一次发包距离现在的时间超过heartbeat_idle_time会把这个连接关闭。
            'task_worker_num' => 10,
            'pid_file'=> $this->master_pid_file,//kill -SIGUSR1 $(cat server.pid)  重启所有worker进程
            'task_max_request' => 1000,//设置task进程的最大任务数，一个task进程在处理完超过此数值的任务后将自动退出，防止PHP进程内存溢出
            'user'  => 'apache',
            'group' => 'apache',
            //'chroot' => '/tmp/root'
            'open_eof_split' => true,
            'package_eof' => "\r\n"
        ));

        $this->server->on('open', array(&$this,'pokerOpen'));

        $this->server->on('message', array($this,'pokerReceive'));

        $this->server->on('Task', array(&$this,'pokerTask'));//处理异步任务

        $this->server->on('Finish', array(&$this,'pokerFinish'));//有Task就得有Finish

        $this->server->on('WorkerStart', array(&$this,'pokerWorkerStart'));//定时器

        $this->server->on('close', array(&$this,'pokerClose'));

        //'https://wiki.swoole.com/wiki/page/19.html
        $this->server->start();//启动成功后会创建worker_num+2个进程

    }

    /**
     * @return int
     * 获取在线人数
     */
    public function getOnlineUsers() {
        $redis = new RedisPackage([],0);
        $sessidAndFd = Model_Keys::sessidAndFd();
        $count       = $redis->HLEN($sessidAndFd);

        return  intval($count);
    }

    /**
     * 客户端连接服务器
     *
     * @param $server
     * @param $frame
     * @return bool
     */
    public function pokerOpen($server, $frame) {
        Kit::debug("pokerOpen-msg---------start".print_r($frame,1),'debug.log');

        $info      = $server->getClientInfo($frame->fd);
        $remote_ip = isset($info['remote_ip']) ? $info['remote_ip'] : "";
        Kit::debug($remote_ip,'debug.log');

        if(empty($remote_ip) || in_array($remote_ip,$this->block_ips)) {
            $server->push($frame->fd,Kit::json_response(code::IP_BLACK_LIST,'ip限制登录',[
                'msg'  =>'ip限制登录',
                'fd'   =>$frame->fd,
            ]));
            $server->close($frame->fd);

            return false;
        }

        $server->push($frame->fd,Kit::json_response(code::CONNECT_SUCCESS,'ok',[
            'fd'     => $frame->fd,
            'msg'    => 'connect success'
            ]
        ));

        Kit::debug("pokerOpen-connect success---------",'debug.log');

        return true;
    }

    /**
     * @param $server
     * @param $frame
     * @return mixed
     */
    public function pokerReceive($server, $frame) {
        try {
            LogicIndex::run($server,$frame);
        } catch(\Exception $e) {
            $err_msg = $e->getMessage(). '==='. $e->getFile(). '==>'. $e->getLine();
            Kit::debug($err_msg,'pokerReceive_err');
            die(-1);
        }
    }

    /**
     * @param $server
     * @param $fd
     * @return bool
     * 客户端链接关闭回掉
     */
    public function pokerClose($server, $fd) {
        Kit::debug("pokerClose-msg---------".print_r($fd,1),'debug.log');

        $redis       = new RedisPackage([],$server->worker_id);

        $sessidAndFd = Model_Keys::sessidAndFd();
        Kit::debug("pokerClose-msg---------sessidAndFd::".$sessidAndFd,'debug.log');

        $sessid  = $redis->hget($sessidAndFd,$fd);
        $ukey    = Model_Keys::uinfo($sessid);
        Kit::debug("pokerClose-msg---------ukey::".$ukey,'debug.log');


        $memberId = $redis->hget(redis_key::fd_memberId,$fd);
        Kit::debug("pokerClose-msg---------member_id::".$memberId,'debug.log');


        $redis->hdel($sessidAndFd,$fd);
        $redis->del($ukey);
        $redis->hdel(redis_key::login_hash_key,$memberId);
        $redis->hdel(redis_key::fd_memberId,$fd);

        return true;
    }

    /**
     * @param $server
     * @param $task_id
     * @param $src_worker_id
     * @param $data
     * @return bool
     */
    public function pokerTask($server, $task_id, $src_worker_id, $data) {
        return asyncTask::LogToDb($server, $task_id, $src_worker_id, $data,1);
    }

    public function pokerFinish($server, $task_id, $data) {

    }

    /**
* @param $server
* @param $worker_id
* tick定时器
*/
    public function pokerWorkerStart($server, $worker_id) {

        /* //定时检测 非正常客户端连接
         if (!$server->taskworker) {
             $server->tick(60000, function ($id) use ($server,$worker_id){
                 $sessidAndFd = Model_Keys::sessidAndFd();
                 $redis = new RedisPackage([],$worker_id);
                 foreach($server->connections as $fd) {
                     $ret = $redis->hget($sessidAndFd,$fd);
                     if($ret == "+OK") {
                         continue;
                     }

                     if(empty($ret)) {
                         $server->close($fd);
                     }
                 }
             });
         }*/
    }

    public static function getToken($memberId) {
        $sessionId = md5(uniqid().mt_rand(100000,999999).md5($memberId));
        echo $sessionId;exit;
    }
}
