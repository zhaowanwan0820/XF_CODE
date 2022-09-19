<?php
/**
 * 投资成功页
 **/

namespace web\controllers\finplan;

use core\service\DiscountService;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use libs\web\Url;
use core\service\duotou\DuotouService;
use core\service\deal\P2pIdempotentService;
use core\service\supervision\SupervisionDealService;

class Dtsuccess extends BaseAction {

    public function init() {
        $this->check_login();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            app_redirect(url("index"));
        }
    }

    public function invoke() {
        $orderId = $this->form->data['id'];

        if($orderId <= 0){
            app_redirect(url("index"));
        }

        // 多投逻辑
        $p2pIdempotentService = new P2pIdempotentService();
        $orderInfo = $p2pIdempotentService->getInfoByOrderId($orderId);

        if (empty($orderInfo) || $orderInfo['loan_user_id'] != $GLOBALS['user_info']['id']) {
            app_redirect(url("index"));
        }
        
        $projectRequest = array('project_id' =>  $orderInfo['deal_id']);
        $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\Project','getProjectInfoById',$projectRequest));

        if(!$response) {
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }
        $project = $response['data'];
        $project['old_name'] = $project['name'];
        $project['date'] = str_replace(",", "、" ,$project['expiryInterest']);
        $project['rate_show'] = number_format($project['rateYear'], 2);
        $params = json_decode($orderInfo['params'], true);
        $project['url'] = "/finplan/bid/" . $params['activityId'];

        /**********存管逻辑***********************/
        $supervisionDealService = new SupervisionDealService();
        $isShowBankAlert = $supervisionDealService->setQuickBidAuthCount($loan_info['userId']);
        $this->tpl->assign('isShowBankAlert',$isShowBankAlert);
        $this->tpl->assign('deal', $project);
        $this->tpl->assign('loan_info', $orderInfo);
        return true;
    }
}
