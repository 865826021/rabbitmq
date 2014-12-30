<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 18:30
 */

include(__DIR__ . '/../demo/config.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPConnectionException;
/**
 * 消费者类
 */
class AsynMessageConsumer{
    const BASE_MQ = 1;
    const WORK_MQ = 2;
    const LOG_MQ = 3;
    const ROUTE_MQ = 4;

    private $config = array();
    private $durable = True;
    private $mirror = False;
    private $autodelete = False;
    private $conn = Null;
    private $channel = Null;
    private $queue = Null;
    public $is_ready = False;

    /**
     * 创建连接、交换机、队列，并绑定
     * @param array $config RabbitMQ服务器信息
     * @param string $e_name 交换机名称
     * @param string $k_route 路由键
     * @param string $q_name 队列名称
     * @param bool $durable 队列是否持久化
     * @param bool $mirror 队列是否镜像
     * @return void
     */
    public function __construct($config, $e_name, $k_route, $q_name, $durable = True, $mirror = False, $autodelete = False){
        if (!($config && $e_name && $q_name && $k_route)) return False;
        $this->config = $config;
        if (!self::connect()) return False;
        $this->channel = $this->conn->channel();

        $this->durable = (bool)$durable;
        $this->mirror = (bool)$mirror;
        $this->autodelete = (bool)$autodelete;
        $this->establishExchange($e_name);
        $this->establishQueue($q_name, $e_name, $k_route);
        $this->is_ready = True;
    }

    /**
     * 循环阻塞方式接收消息
     * @param string $fun_name 自定义处理函数的函数名
     * @param bool $autoack 是否自动发送ACK应答，否则需要在自定义处理函数中手动发送
     * @return bool
     */
    public function run($fun_name, $autoack = True){
        $fun_name = strval($fun_name);
        if (!$fun_name || !$this->queue) return False;
        while(True){
            if ($autoack) $this->queue->consume($fun_name, AMQP_AUTOACK);
            else $this->queue->consume($fun_name);
        }
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

    /**
     * 创建交换机
     * @param string $name 名称
     * @return int
     */
    private function establishExchange($name){
        $ex = new AMQPExchange($this->channel);
        $ex->setName($name);
        $ex->setType(AMQP_EX_TYPE_DIRECT); //direct类型
        if ($this->durable) $ex->setFlags(AMQP_DURABLE); //持久化
        return $ex->declare();
    }

    /**
     * 创建队列
     * @param string $name 名称
     * @param string $e_name 交换机名称
     * @param string $k_route 路由键
     * @return int
     */
    private function establishQueue($name, $e_name, $k_route){
        $this->queue = new AMQPQueue($this->channel);
        $this->queue->setName($name);
        if ($this->durable) $this->queue->setFlags(AMQP_DURABLE); //持久化
        if ($this->mirror) $this->queue->setArgument('x-ha-policy', 'all'); //镜像
        if ($this->autodelete) $this->queue->setFlags(AMQP_AUTODELETE);  //auto-delete
        $this->queue->declare();
        $ret = $this->queue->bind($e_name, $k_route);
        return $ret;
    }


    public function __destruct(){
        if ($this->conn){
            $this->conn->disconnect();
        }
    }
}