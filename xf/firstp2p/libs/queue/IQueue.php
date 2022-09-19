<?php
namespace libs\queue;

/**
 * 队列接口，任何队列实现应实现本接口
 *
 **/
interface IQueue
{
    public function send($message);
    public function receive();
    public function delete();
    public function len();
    public function flush();
}
