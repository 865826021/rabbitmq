<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/19
 * Time: 19:11
 */
include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessageConsumer {
    const BASE_MQ = 1;  //hello world
    const WORK_MQ = 2;   //work
    const LOG_MQ = 3;   //Publish/Subscribe
    const ROUTE_MQ = 4;  //routing

    private $conn = Null;
    private $channel = Null;
    private $ex_name = null;
    private $ex_type = null;

    public function __construct() {
        $this->conn = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
        $this->channel = $this->conn->channel();
    }


    public function receiver( $k_route, $w_type, $ex_name = '', $ex_type = '') {
        if (!empty($ex_name) && !empty($ex_type) && ($w_type == self::LOG_MQ || $w_type == self::ROUTE_MQ)) {
            $this->ex_name = $ex_name;
            $this->ex_type = $ex_type;
        }
        self::mqDeclare($w_type, $k_route);
        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            $info = json_decode($msg->body, true);
            switch ($info['type']) {
                case sms:
                    echo 'phone is '.$info['mobile'].', content is '.$info['msg'].', is num  '.$info['num'];
                    break;
            }

        };

        $this->channel->basic_consume('hello', '', false, true, false, false, $callback);

        while(count($$this->channel->callbacks)) {
            $this->channel->wait();
        }

    }

    private function mqDeclare($w_type, $k_route) {
        switch ($w_type) {
            case self::BASE_MQ:
                //消息持久化
                $this->channel->queue_declare($k_route, false, true, false, false);
                break;
            case self::WORK_MQ:
                $this->channel->queue_declare($k_route, false, true, false, false);
                break;
            case self::LOG_MQ:
                $this->channel->exchange_declare($this->ex_name, $this->ex_type, false, false, false);
                list($queue_name, ,) = $this->channel->queue_declare("", false, false, true, false);
                $this->channel->queue_bind($queue_name, $this->ex_name);
            case self::ROUTE_MQ:
                //创建交换机  消息持久化
                $this->channel->exchange_declare($this->ex_name, $this->ex_type, false, false, false);
                list($queue_name, ,) = $this->channel->queue_declare("", false, false, true, false);
                $severities = array('info', 'error', 'warning');
                foreach ($severities as $severitie) {
                    $this->channel->queue_bind($queue_name, $this->ex_name, $severitie);
                }
                break;
        };
    }
}