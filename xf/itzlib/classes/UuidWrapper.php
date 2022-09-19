<?php

class UuidWrapper
{
    const FORMAT_STRING = 'string';
    const FORMAT_BYTES = 'bytes';

    /**
     * @var null|\Ramsey\Uuid\Uuid
     */
    public $uuid = null;

    public static function autoload($className)
    {
        $classPath = str_replace('Ramsey\\Uuid', WWW_DIR . '/thirdlib/uuid/src', $className);
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);
        $file = $classPath . '.php';
        if (file_exists($file)) {
            require_once($file);
        }
    }

    public function __construct($uuid = '', $format = self::FORMAT_STRING)
    {
        spl_autoload_register([__CLASS__, 'autoload']);

        if (empty($uuid)) {
            $this->uuid = \Ramsey\Uuid\Uuid::uuid1();
        } else {
            switch ($format) {
                case self::FORMAT_BYTES:
                    $this->uuid = \Ramsey\Uuid\Uuid::fromBytes($uuid);
                    break;
                case self::FORMAT_STRING:
                    $this->uuid = \Ramsey\Uuid\Uuid::fromString($uuid);
                    break;
                default:
                    throw new InvalidArgumentException("Unknown uuid's type");
            }
        }
    }

    public function getBytes()
    {
        return $this->uuid->getBytes();
    }

    public function getTimestamp()
    {
        return $this->uuid->getDateTime()->getTimestamp();
    }

    public function getString()
    {
        return $this->uuid->toString();
    }

    public function getHex()
    {
        return $this->uuid->getHex();
    }
}
