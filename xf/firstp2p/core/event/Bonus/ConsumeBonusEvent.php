<?php
/**
 *-------------------------------------------------------
 * 生成红包同步数据
 *-------------------------------------------------------
 * 2016年 03月 09日 星期三 17:39:05 CST
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\bonus\RpcService;

/**
 * BonusAddEvent
 * 红包服务化同步数据
 *
 * @uses AsyncEvent
 */
class ConsumeBonusEvent extends BaseEvent
{
    private $consumeId   = '';

    public function __construct($consumeId)
    {
        $this->consumeId = $consumeId;
    }

    public function execute()
    {
        return true;
        $consumeId = $this->consumeId;

        $bonuses = \core\dao\BonusUsedModel::instance()->findAll("deal_load_id = '$consumeId'", true, 'bonus_id, used_at');
        $bonusIds = array();
        foreach ($bonuses as $row) {
            $useTime = $row['used_at'];
            $bonusIds[] = $row['bonus_id'];
        }

        $bonusIds = implode(',', $bonusIds);
        if (empty($bonusIds)) {
            Logger::info(implode(" | ", array($consumeId, 'ConsumeBonusEvent:ERROR_EMPTY_BONUSIDS')));
            return false;
        }

        $records = \core\dao\BonusModel::instance()->findAll("`id` IN ($bonusIds)", true, "id, owner_uid, money, group_id");
        $money = 0.00;
        foreach ($records as &$row) {
            $userId = $row['owner_uid'];
            $money = bcadd($money, $row['money'], 2);
            $row['account_id'] = (new \core\service\BonusService())->getSponsorId($row['id'], $row['group_id']);
        }

        $consumeType = 0;
        $token = "dealLoadId:{$consumeId}";

        $data = [__CLASS__, $userId, $consumeId, $consumeType, $money, $token, $info, json_encode($records)];

        $result = \core\dao\DealLoadModel::instance()->find($consumeId, 'user_id, deal_id');
        if (empty($result)) {
            array_push($data, 'ConsumeBonusEvent:ERROR_EMPTY_DEAL_LOAD');
            Logger::info(implode(" | ", $data));
            return false;
        }

        $dealInfo = \core\dao\DealModel::instance()->find($result['deal_id'], 'name', true);
        $info = $dealInfo['name'];

        if (empty($records)) {
            array_push($data, 'ConsumeBonusEvent:ERROR_EMPTY_RECORDS');
            Logger::info(implode(" | ", $data));
            return false;
        }

        if (empty($userId)) {
            Logger::info(implode(" | ", $data));
            return false;
        }

        $result = (new RpcService())->consumeBonus($userId, $records, $money, $token, $consumeId, $consumeType, $useTime, $info);
        array_unshift($data, json_encode($result));
        if ($result == false) {
            array_push($data, 'ConsumeBonusEvent:FAILED');
            Logger::info(implode(" | ", $data));
            return false;
        }

        array_push($data, 'ConsumeBonusEvent:SUCCESS');
        Logger::info(implode(" | ", $data));
        return true;
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
