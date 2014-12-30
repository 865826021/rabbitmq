<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 15:59
 */

/**
 * hello world
 * 一对一直送
 */
include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 做一个连接
$connection = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();


//消息持久化
$channel->queue_declare('hello', false, false, false, false);

for ($i=1; $i < 10; $i++) {
    $arr = array(
        'type' => 'sms',
        'mobile' => '18983938829',
        'num' => 1,
        'msg' => 'rabbitmq的测试用例'
    );
    $data = json_encode($arr);
    $msg = new AMQPMessage($data);
    $channel->basic_publish($msg, '', 'hello');

    echo " [x] Sent '".$data."'\n";
}


$channel->close();
$connection->close();