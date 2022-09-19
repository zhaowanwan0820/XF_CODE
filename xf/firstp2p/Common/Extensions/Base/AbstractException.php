<?php
namespace NCFGroup\Common\Extensions\Base;
use NCFGroup\Common\Library\LoggerLevel;
use NCFGroup\Common\Library\Logger;

class AbstractException extends \Exception
{
    protected $message = "";
    protected $level = LoggerLevel::DEBUG;
    protected $code = 0;

    public function __construct($message = "", $args = array())
    {
        Logger::log("ExceptionClass: " . get_called_class() . ", message: " . $message . ", args: " . json_encode($args), $this->getLevel());

        if (empty($this->message)) {
            $this->message = $message;
        }
        $count = substr_count($this->message, "%s");
        if($count > count($args)) {
            throw new \InvalidArgumentException("消息格式不正确, 请填充后重试");
        }
        $this->message = vsprintf($this->message, $args);
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function setLevel($level)
    {
        $this->level = $level;
        return this;
    }
}
