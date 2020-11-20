<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/11/18
 * Time: 11:14
 */

//phpinfo();

/*Redis缓存 s*/
// 实例化并连接Redis
$r = new Redis();
$r->connect('127.0.0.1', 6379);
$r->auth('foobared');
// 写入Redis缓存
$r->set('user1', 'Redis缓存测试');
// 读取Redis缓存
echo $r->get('user1');
/*Redis缓存 e*/