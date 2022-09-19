<?php
/**
 * 资产端协议支付签约申请
 * Index.php
 */

namespace web\controllers\banksign;

use libs\utils\DBDes;
use libs\web\Form;
use libs\utils\Logger;
use web\controllers\banksign\BkBaseAction;
use core\service\banksign\BankSignService;
use core\service\thirdparty\ThirdpartyDkService;



class Index extends BkBaseAction {

    public function invoke() {
        Logger::info(implode(" | ", array('BankSign', __FUNCTION__, __LINE__, "协议支付-签约页面", 'token:'.$this->token,'orderId:'.$this->orderInfo['order_id'])));
        $ops = json_decode($this->orderInfo['params'],true);
        $mobile = DBDes::decryptOneValue($ops['mobile']);
        $mobile = substr_replace($mobile,'****',3,4);
        $this->tpl->assign('token',$this->token);
        $this->tpl->assign('returnUrl',$ops['return_url']);
        $this->tpl->assign('mobile',$mobile);
        $this->tpl->assign('outOrderId',$this->orderInfo['outer_order_id']);
        $this->tpl->assign('bankType',BankSignService::getBankShortName(DBDes::decryptOneValue($ops['bank_no'])));
        $this->template = 'web/views/banksign/sign_page.html';
    }
}
