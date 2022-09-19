<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RmsgMethods extends AbstractEnum
{
    const SUBSCRIBE = 'subscribeTransaction';//订阅
    const UNSUBSCRIBE = 'unSubscribeTransaction';//取消订阅
    protected static $details = [
        self::SUBSCRIBE => [
            'funcCode' => '100018',
            'paramDTO' => 'SubscribeParamDTO',
            'args' => [
                 'cifNo',
                 'cifPwd',
            ],
        ],
        self::UNSUBSCRIBE => [
            'funcCode' => '100018',
            'paramDTO' => 'UnSubscribeParamDTO',
            'args' => [
                  'subscribeId',
            ],
        ],
   ];

    public function getArgs()
    {
        return self::$details[$this->getValue()]['args'];
    }

    public function getFuncCode()
    {
        return self::$details[$this->getValue()]['funcCode'];
    }

    public function getParamDTO()
    {
        return self::$details[$this->getValue()]['paramDTO'];
    }
}
