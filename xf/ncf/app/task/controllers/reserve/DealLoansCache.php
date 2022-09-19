<?php
namespace task\controllers\reserve;

use core\dao\reserve\ReservationCacheModel;
use libs\utils\Logger;
use task\controllers\BaseAction;

/**
 * 随鑫约放款设置缓存
 * Class DealLoansCache
 * @package task\controllers\dealcreate
 */
class DealLoansCache extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info("Task DealLoansCache params ".json_encode($params));

            $dealId = $params['dealId'];
            if(!$dealId){
                throw new \Exception('参数错误');
            }
            ReservationCacheModel::instance()->setReserveDealLoansCache($dealId);

        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }

    }
}
