<?php
/**
 * Created by PhpStorm.
 * User: klj
 * Date: 14/12/17
 * Time: 18:37
 */
include 'config.php';
include 'AsynMessagePublisher.php';

use \AsynMessagePublisher;


$rabbitmq = new AsynMessagePublisher($rabbit_conf);

$rabbitmq->send('hello alex kong test123', 'spd_base', 1);