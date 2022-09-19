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
class AcquireBonusGroupEvent extends BaseEvent
{
    private $groupId = 0;

    public function __construct($groupId, $info = '')
    {
        $this->groupId = $groupId;
    }

    public function execute()
    {
        $group = (new \core\dao\BonusGroupModel())->find($this->groupId);
        if (is_object($group)) {
            $group = $group->getRow();
        }
        if (empty($group)) {
            Logger::info(implode("|", [__METHOD__, 'group empty', $this->groupId]));
            return false;
        }

        $group = (new BonusService)->formatGroupItemForSync($group);


        $result = (new RpcService())->acquireBonusGroup($group);

        Logger::info(implode("|", [__METHOD__, 'sync res', $this->groupId, $result]));

        if ($result == false) {
            return false;
        }
        return true;
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
