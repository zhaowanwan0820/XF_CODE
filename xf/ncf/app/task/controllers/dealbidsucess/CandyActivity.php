<?php
namespace task\controllers\dealbidsucess;

use task\controllers\BaseAction;
use libs\utils\Logger;
use core\service\candyactivity\CandyActivityService;
use core\service\deal\DealService;
use core\enum\CandyEnum;

/**
 * 投资完成后增加信力
 * Class Create
 * @package task\controllers\dealbidsucess
 */
class CandyActivity extends BaseAction {

    public function invoke() {
        // msgbus传递的msg信息本身也需要json_decode
        $param = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ ."," .__LINE__ . ", Task receive params ".json_encode($param));
            $rpcRes = true;
            if (!$param['isDt']) {
                $token = CandyEnum::SOURCE_TYPE_P2P.'ncfph'.$param['user_id'].'_'.$param['deal_id'].'_'.$param['load_id'];
                $sourceType = CandyEnum::SOURCE_TYPE_P2P;
                $sourceValue = DealService::getAnnualizedAmountByDealIdAndAmount($param['deal_id'],$param['money']);
                $sourceValueExtra = $param['money'];
                $rpcRes = CandyActivityService::activityCreateByType($token,$param['user_id'],$sourceType,$sourceValue,$sourceValueExtra);
            }
            if(!$rpcRes){
                Logger::error(__CLASS__ ."," .__LINE__ . ",  false");
                throw new \Exception('添加普惠投资成功信力失败');
            }
        } catch (\Exception $ex) {
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
