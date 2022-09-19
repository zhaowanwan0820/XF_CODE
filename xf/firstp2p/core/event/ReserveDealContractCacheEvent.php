<?php
namespace core\event;
use core\event\BaseEvent;
use libs\utils\Logger;

use core\dao\ReservationCacheModel;
/**
 * 记录随心约合同签署缓存
 */
class ReserveDealContractCacheEvent extends BaseEvent
{

    private $deal_id;
    //$type(0:老版本,1:合同服务)
    private $tpye;

    public function __construct ($deal_id,$type=0)
    {
        $this->deal_id = $deal_id;
        $this->type = $type;
    }

    public function execute ()
    {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("dealId: %d", $this->deal_id))));
        ReservationCacheModel::instance()->setReserveDealContractCache($this->deal_id);
        return true;
    }

    public function alertMails ()
    {
        return array(
                'weiwei12@ucfgroup.com'
        );
    }
}

