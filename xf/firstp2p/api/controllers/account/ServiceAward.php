<?php
/**
 * Coupon.php.
 *
 * @date 2014-03-27
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\CouponLogService;

class ServiceAward extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
         "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $couponLogService = new CouponLogService(CouponLogService::MODULE_TYPE_P2P,CouponLogService::DATA_TYPE_SERVICE);
        $result['totalRefererRebateAmount'] = $couponLogService->getTotalRefererRebateAmount($user['id']);
        $result['totalRefererRebateAmount']['referer_rebate_amount'] = number_format($result['totalRefererRebateAmount']['referer_rebate_amount'],2);
        $result['totalRefererRebateAmount']['referer_rebate_amount_no'] = number_format($result['totalRefererRebateAmount']['referer_rebate_amount_no'],2);
        $couponModelTypes = CouponLogService::getModelTypes();
        $couponModelTypes['p2p'] = '服务奖励';
        unset($couponModelTypes['reg']);
        foreach($couponModelTypes as $modelKey => $modelName){
            $result['types'][] = array('typeid' => $modelKey,'typename' => $modelName);
        }
        $this->json_data = $result;
    }

    public function _after_invoke(){
        $format = isset($_REQUEST['format'])?$_REQUEST['format']:'h5';
        if($format == 'json'){
            parent::afterInvoke();
        }else{
            $data = $this->form->data;
            $this->tpl->assign("token",$data['token']);
            $this->tpl->assign($this->json_data);
            $this->template = 'api/views/_v497/service_award/service_award.html';
            $this->tpl->display($this->template);
        }
    }
}
