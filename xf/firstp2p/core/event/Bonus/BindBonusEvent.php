<?php
/**
 *-------------------------------------------------------
 * 生成红包同步数据
 *-------------------------------------------------------
 * 2016年 03月 09日 星期三 14:36:22 CST
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\bonus\RpcService;
use core\dao\BonusModel;
use core\service\WXBonusService;
/**
 * BonusAddEvent
 * 红包服务化同步数据
 *
 * @uses AsyncEvent
 */
class BindBonusEvent extends BaseEvent
{
    public function __construct($userId, $mobile)
    {
        $this->userId = $userId;
        $this->mobile = $mobile;
    }

    public function execute()
    {
        (new WXBonusService)->bind($this->userId, $this->mobile);
        Logger::info(implode('|', [__METHOD__, $this->userId, $this->mobile]));
        return true;
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
