<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 16:49
 */

/**
 * 工作和订阅模式
 * 一个发送多个接收  消息推送
 */

include(__DIR__ . '/../demo/config.php');


use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

//创建交换机  消息持久化
$channel->exchange_declare('logs', 'fanout', false, false, false);

$data = implode(' ', array_slice($argv, 1));
if(empty($data)) $data = "info: Hello World!";
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, 'logs');

echo " [x] Sent ", $data, "\n";

$channel->close();
$connection->close();

