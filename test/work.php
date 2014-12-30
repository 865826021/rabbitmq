<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 16:27
 */
/**
 * 工作模式
 * 运行2个work_receiver.php
 * 能查看到一个发送者发送多条消息
 * 接收者轮询接收message
 */


include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);

$channel = $connection->channel();

//消息持久化
$channel->queue_declare('task_queue', false, true, false, false);


//$data = implode(' ', array_slice($argv, 1));
//
//if(empty($data)) $data = "Hello World!";
for ($i=1; $i < 100; $i++) {
    $arr = array(
        'type' => 'sms',
        'mobile' => '18983938829',
        'num' => $i,
        'msg' => 'rabbitmq的测试用例'.$i
    );
    $data = json_encode($arr);
    $msg = new AMQPMessage($data,
        array('delivery_mode' => 2) # make message persistent
    );
    $channel->basic_publish($msg, '', 'task_queue');

    echo " [x] Sent ", $data, "\n";
}



$channel->close();
$connection->close();