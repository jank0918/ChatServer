基于PHP7+Swoole+Redis+Mysql 完成的聊天服务器（不包含客户端，所以没有演示地址，纯服务器代码，服务器使用的是swoole的websocket协议，和客户端交互使用的json传输，后期打算用protobuf,不过很麻烦），底子借鉴的是github上面一个叫 ChatServer 的 项目，有兴趣的可以去了解一下，这个项目是一个完整的网页聊天结构 (源码地址：https://github.com/lyxlk/ChatServer.git)，不过好久没有更新了

服务器启动/关闭
===============
 + 一律需要将项目“ChatServer”放置在 /var/www/ 下，没有就自己创建；注意项目名的大小写！！！
 + cd /var/www/ChatServer  && php think Swoole -m "start"
 + cd /var/www/ChatServer  && php think Swoole -m "stop"
 
服务器监控相关脚本如下
===============

#### 每天凌晨 4点 重启各种服务器
 + 5  4 * * * service php-fpm restart  >/dev/null 2>&1 &

 + 10 4 * * * service nginx restart  >/dev/null 2>&1 &

 + 15 4 * * * service mysql restart  >/dev/null 2>&1 &

 + 20 4 * * * service redis restart  >/dev/null 2>&1 &
 
#### 防止redis 超负荷运行 挂掉了
 + 18 4 * * * redis-server  /etc/redis.conf  >/dev/null 2>&1 &

 + 19 4 * * * redis-server  /etc/redis6380.conf  >/dev/null 2>&1 &

#### 每5分钟
 + */5 * * * * cd /var/www/ChatServer  && php think Swoole -m "monitor"  >/dev/null 2>&1 &

#### 每小时执行一次 重启一下worker
 + 1 * * * *  cd /var/www/ChatServer  && php think Swoole -m "reload"  >/dev/null 2>&1 &
