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
use core\service\BonusService;
/**
 * BonusAddEvent
 * 红包服务化同步数据
 *
 * @uses AsyncEvent
 */
class AcquireBonusEvent extends BaseEvent
{
    private $bonusId = 0;
    private $info = '';

    public function __construct($bonusId, $info = '')
    {
        $this->bonusId = $bonusId;
        $this->info = $info;
    }

    public function execute()
    {
        $bonus = (new \core\dao\BonusModel())->find($this->bonusId);
        if (is_object($bonus)) {
            $bonus = $bonus->getRow();
        }
        if (empty($bonus)) {
            Logger::info(implode(" | ", array("bonusId:{$this->bonusId}", json_encode($bonus))));
            return false;
        }

        list($token, $itemType, $itemId) = BonusModel::getAcquireItemInfo($bonus);

        //$info = '';
        $accountId = (new BonusService())->getSponsorId($bonus['id'], $bonus['group_id']);
        $result = (new RpcService())->acquireBonus($bonus['owner_uid'], $bonus['money'], $bonus['id'], strval($itemId), $itemType, $bonus['created_at'], $bonus['expired_at'], $this->info, $accountId);
        if ($result  == false) {
            Logger::info("bonusId:{$this->bonusId} | " . implode(" | ", $bonus));
            return false;
        }
        Logger::info("bonusId:{$this->bonusId} | " . implode(" | ", $bonus));
        return true;
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
