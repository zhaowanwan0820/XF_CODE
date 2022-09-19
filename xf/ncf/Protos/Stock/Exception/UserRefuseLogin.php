<?php
namespace NCFGroup\Protos\Stock\Exception;
/**
 * 此类由代码自动生成，请不要修改
 */
class UserRefuseLogin extends \NCFGroup\Common\Extensions\Base\AbstractException
{
    protected $code = 20005;
    protected $message = '你的请求已经超过了请求次数，不可以再请求！';
    protected $level = 'INFO';
}