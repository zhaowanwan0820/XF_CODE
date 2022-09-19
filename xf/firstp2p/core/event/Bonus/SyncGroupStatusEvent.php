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
use core\dao\BonusGroupModel;

/**
 * BonusAddEvent
 * 红包服务化同步数据
 *
 * @uses AsyncEvent
 */
class SyncGroupStatusEvent extends BaseEvent
{
    private $groupId = 0;

    public function __construct($sn, $status)
    {
        $this->groupId = (new BonusService)->encrypt($sn, 'D');
        $this->status = $status;
    }

    public function execute()
    {
        $group = BonusGroupModel::instance()->find($this->groupId);
        $group = is_object($group) ? $group->getRow() : $group;
        if ($this->status == BonusService::STATUS_GRABED) {
            if ($group['count'] > $group['get_count']) return true;
        } else {

            if (empty($group)) {
                Logger::info(implode("|", [__METHOD__, 'group empty', $this->groupId]));
                return false;
            }

            $group = (new BonusService)->formatGroupItemForSync($group);

        }
        return (new RpcService())->syncGroupStatus($this->groupId, $this->status, $group);
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
