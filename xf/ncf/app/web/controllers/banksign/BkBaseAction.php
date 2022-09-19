<?php
/**
 * 资产端协议支付签约申请
 * Index.php
 */

namespace web\controllers\banksign;

use core\service\banksign\BankSignService;
use core\service\thirdparty\ThirdpartyDkService;
use libs\logging\NullLogger;
use web\controllers\BaseAction;
use libs\web\Form;



class BkBaseAction extends BaseAction {

    public $token;

    public $orderId;

    public $orderInfo;

    public function init() {
        $this->token = trim($_GET['token']);
        if(empty($this->token)){
            return $this->show_error('参数错误', "", 1);
        }

        $this->orderId = BankSignService::decToken($this->token);
        $this->tokenCheck();
    }

    private function tokenCheck(){
        $outOrderInfo = ThirdpartyDkService::getThirdPartyByOrderId($this->orderId);

        if(empty($outOrderInfo)){
            $this->show_error('权限错误','',1);exit;
        }

        $now = time();
        $reqTime = $outOrderInfo['create_time'];

        if($now-$reqTime > 86400){
            $this->show_error('timestamp error','',1);exit;
        }
        $this->orderInfo = $outOrderInfo;
    }
}