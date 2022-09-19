<?php
namespace NCFGroup\Protos\Future\Exception;
/**
 * 此类由代码自动生成，请不要修改
 */
class MoneyLessThanMinDeposit extends \NCFGroup\Common\Extensions\Base\AbstractException
{
    protected $code = 10010;
    protected $message = '保证金低于最小金额(%s)，无法进行配资';
    protected $level = 'INFO';
}