<?php
namespace NCFGroup\Common\Extensions;

class EmptyLoggerAdapter extends Phalcon\Logger\AdapterInterface
{

    private $logLevel;

    public function setFormatter(Phalcon\Logger\FormatterInterface $formatter)
    {
        return $this;
    }

    public function getFormatter()
    {
        return null;
    }

    public function setLogLevel(int $level)
    {
        $this->logLevel = $level;
    }

    public function getLogLevel()
    {
        return $this->logLevel;
    }

    public function begin()
    {
        return $this;
    }

    public function commit()
    {
        return $this;
    }

    public function rollback()
    {
        return $this;
    }

    public function close()
    {
        return true;
    }
}