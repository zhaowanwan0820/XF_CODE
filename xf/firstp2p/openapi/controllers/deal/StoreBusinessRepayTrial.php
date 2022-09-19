<?php
namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\Aes;
use libs\utils\Block;
use core\dao\DealModel;
use core\dao\DealRepayModel;

class StoreBusinessRepayTrial extends BaseAction {
    
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $approve_number = $data['approve_number'];

        $checkCounts = Block::check('DSD_REPAYTRIAL_DOWN_MINUTE','dsd_repaytrial_down_minute');
        if ($checkCounts === false) {
            $this->setErr('ERR_MANUAL_REASON','请不要频繁发送请求');
            return false;
        }

        try{
            $deal = DealModel::instance()->findBy("approve_number='{$approve_number}'");
            if(!$deal){
                throw new \Exception("标的信息不存在");
            }
            $repayInfo = DealRepayModel::instance()->getNextRepayByDealId($deal['id']);
            if(!$repayInfo){
                throw new \Exception("标的还款信息不存在");
            }
            $ds = new \core\service\DealService();
            $repayData = $ds->dealRepayTrial($deal,$repayInfo->id,date('Y-m-d'));
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",approve_number:".$approve_number.", repayData:".json_encode($repayData));
            $this->setErr("ERR_REPAY_TRIAL_ERR", $ex->getMessage());
            return false;
        }
        $this->json_data = $repayData;
    }
}