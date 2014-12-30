<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/19
 * Time: 18:27
 */
include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessagePublisher {
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

    /**
     * 发送消息
     * @param $data string 发送内容
     * @param $k_route  路由名称
     * @param $w_type   rabbitmq类型
     * @param string $ex_name  交换机名称
     * @param string $ex_type  交换机类型 （fanout, logs, topic）
     * @return mixed
     */
    public function send($data, $k_route, $w_type, $ex_name = '', $ex_type = '') {
        if (!empty($ex_name) && !empty($ex_type) && ($w_type == self::LOG_MQ || $w_type == self::ROUTE_MQ)) {
            $this->ex_name = $ex_name;
            $this->ex_type = $ex_type;
        }
        $data = trim(strval($data));
        if ($data==='' || !$k_route) return False;
        $msg = self::mqDeclare($w_type, $k_route, $data);

        if ($w_type == self::LOG_MQ) {
            $ret = $this->channel->basic_publish($msg, $this->ex_name);
        } else if ($w_type == self::ROUTE_MQ) {
            $ret = $this->channel->basic_publish($msg, $this->ex_name, $k_route);
        } else {
            $ret = $this->channel->basic_publish($msg, '', $k_route);
        }

        echo " [x] Sent ", $data, "\n";
        return $ret;
    }

    private function mqDeclare($w_type, $k_route, $data) {
        switch ($w_type) {
            case self::BASE_MQ:
                //消息持久化
                $this->channel->queue_declare($k_route, false, true, false, false);
                return $msg = new AMQPMessage($data);
                break;
            case self::WORK_MQ:
                $this->channel->queue_declare($k_route, false, true, false, false);
                return $msg = new AMQPMessage($data,array('delivery_mode' => 2));
                break;
            case self::LOG_MQ:
            case self::ROUTE_MQ:
                //创建交换机  消息持久化
                $this->channel->exchange_declare($this->ex_name, $this->ex_type, false, false, false);
                return $msg = new AMQPMessage($data);
                break;

        };
    }

    public function __destruct(){
        if ($this->conn || $this->channel){
            $this->channel->close();
            $this->conn->close();
        }
    }
}