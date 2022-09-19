<?php
namespace task\controllers\reserve;

use core\dao\reserve\ReservationCacheModel;
use libs\utils\Logger;
use task\controllers\BaseAction;

/**
 * 随鑫约还款设置缓存
 * Class DealRepayCache
 * @package task\controllers\dealcreate
 */
class DealRepayCache extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info("Task DealRepayCache params ".json_encode($params));

            $dealId = $params['dealId'];
            $repayId = $params['repayId'];
            if(!$dealId || !$repayId){
                throw new \Exception('参数错误');
            }
            ReservationCacheModel::instance()->setReserveDealRepayCache($dealId, $repayId);

        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }

    }
}
