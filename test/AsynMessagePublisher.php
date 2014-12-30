<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 18:29
 */
include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPConnectionException;
/**
 * 生产者类
 */
class AsynMessagePublisher{
    const BASE_MQ = 1;
    const WORK_MQ = 2;
    const LOG_MQ = 3;
    const ROUTE_MQ = 4;

    private $config = array();
    private $ex_name = '';
    private $ex_type = '';
    private $conn = Null;
    private $channel = Null;
    //private $exchange = Null;

    /**
     * 创建连接，并指定交换机
     * @param array $config RabbitMQ服务器信息
     * @param string $e_name 交换机名称
     * @param string $e_type exchange 类型
     * @return void
     */
    public function __construct($config, $e_name = '', $e_type = ''){
        if (!($config)) return False;
        $this->config = $config;
        $this->ex_name = $e_name;
        $this->ex_type = $e_type;
        if (!self::connect()) return False;
        $this->channel = $this->conn->channel();

    }

    /**
     * 发送消息
     * @param string $msg 消息体
     * @param string $k_route 路由键
     * @param int $w_type tutorials
     * @return int / False
     */
    public function send($data, $k_route, $w_type){

        $data = trim(strval($data));
        #if (!$this->exchange || $msg==='' || !$k_route) return False;
        if ($data==='' || !$k_route) return False;
        $msg = self::mqDeclare($w_type, $k_route, $data);
        if ($w_type == self::LOG_MQ) {
            $ret = $this->channel->basic_publish($msg, $this->ex_name);
        } else if ($w_type == self::ROUTE_MQ) {
            $ret = $this->channel->basic_publish($msg, $this->ex_name, $k_route);
        } else {
            $ret = $this->channel->basic_publish($msg, $this->ex_name, $k_route);
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
    // 以下为私有方法，无需手动调用

    /**
     * 创建链接
     * 无法链接时则会自动选择下一个配置项（IP不通的情况下会有5秒等待）
     * @param int $i 配置项索引
     * @return bool
     */
    private function connect($i = 0){
        if (array_key_exists($i, $this->config)){
            try{
                list($host, $port, $user, $pass, $vhost) = $this->config[$i];
                $this->conn = new AMQPConnection($host, $port, $user, $pass, $vhost);

                $ret = True;
            }catch(AMQPConnectionException $e){
                $ret = $this->connect(++$i);
            }
        } else {
            $ret = False;
        }
        return $ret;
    }



    public function __destruct(){
        if ($this->conn || $this->channel){
            $this->channel->close();
            $this->conn->close();
        }
    }

}