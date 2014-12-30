<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 15:59
 */

/**
 * hello world
 */

include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;

$connection = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg) {
    echo " [x] Received ", $msg->body, "\n";
    $info = json_decode($msg->body, true);
    switch ($info['type']) {
        case sms:
            echo 'phone is '.$info['mobile'].', content is '.$info['msg'].', is num  '.$info['num'];
            break;
    }

};

$channel->basic_consume('hello', '', false, true, false, false, $callback);


$channel->close();
$connection->close();

while(count($channel->callbacks)) {
    $channel->wait();
}