<?php
namespace task\controllers\dealprepay;


use core\dao\deal\FeeAfterGrantModel;

use core\dao\jobs\JobsModel;
use core\enum\JobsEnum;
use libs\utils\Logger;
use task\controllers\BaseAction;

class CheckFeeAfterGrant extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            $GLOBALS['db']->startTrans();
            Logger::info("Task receive params ".json_encode($params));
            $dealId = $params['dealId'];
            $is_exist = FeeAfterGrantModel::instance()->isDealIdExists($dealId);
            if(!empty($is_exist)){

                $data['charge_result'] = 3;//失败
                $data['fail_reason'] = '标的已使用多账户提前结清接口执行提前结清';
                $ret= FeeAfterGrantModel::instance()->saveFeeAfterGrant($is_exist['id'],$data);
                if(!$ret){
                    throw new \Exception("更新FeeAfterGrant状态失败");
                }

                $orderId = $is_exist['out_order_id'];

                // 添加jobs，推送结果给功夫贷
                $jobsModel = new JobsModel();
                $param = array(
                    'orderId' => $orderId,
                );
                $jobsModel->priority = JobsEnum::PRIORITY_NOTIFY_GFD;
                $r = $jobsModel->addJob('\core\service\notify\NotifyGfdService::notifyFeeAfterGrant', $param,false,1440);
                if ($r === false) {
                    throw new \Exception("添加推送结果给功夫贷jobs失败");
                }
            }


            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}