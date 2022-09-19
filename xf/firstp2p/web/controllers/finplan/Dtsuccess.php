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
        $orderInfo = $this->rpc->local('P2pIdempotentService\getInfoByOrderId', array($orderId));

        if (empty($orderInfo) || $orderInfo['loan_user_id'] != $GLOBALS['user_info']['id']) {
            app_redirect(url("index"));
        }
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $projectRequest = new \NCFGroup\Protos\Duotou\RequestCommon();
        $projectRequest->setVars(array('project_id' =>  $orderInfo['deal_id']));
        $response = $rpc->go('NCFGroup\Duotou\Services\Project','getProjectInfoById',$projectRequest);

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
        $isShowBankAlert = $this->rpc->local('SupervisionDealService\setQuickBidAuthCount',array($loan_info['userId']));
        $this->tpl->assign('isShowBankAlert',$isShowBankAlert);
        $this->tpl->assign('deal', $project);
        $this->tpl->assign('loan_info', $orderInfo);
        return true;
    }
}
