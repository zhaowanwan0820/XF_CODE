<?php
/**
 * Redeem controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-03-03
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\DealCompoundService;
use core\service\user\UserService;
use core\service\duotou\DtRedeemService;


/**
 * 多投-申请转让
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class Redeem extends DuotouBaseAction
{
    const REDEEM_MONEY_BEYOND = '1907';//转让超限错误码
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'deal_loan_id' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if (!$this->form->data['deal_loan_id']) {
            $this->setErr('ERR_PARAMS_ERROR');
        }

        $user = $this->user;
        $data = $this->form->data;
        $oDtRedeemService = new DtRedeemService();
        $response = $oDtRedeemService->redeem(intval($data['deal_loan_id']), $user['id']);
        if(!$response) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
        }

        if ($response['errCode'] != 0) {
            if($response['errCode'] == self::REDEEM_MONEY_BEYOND){
                $errMsg = "每日最多可转让/退出本金".number_format($response['maxDayRedemption'])."元\n如有问题请联系客服";
                $this->setErr('ERR_BEYOND_REDEEM_LIMITS',$errMsg);
            }

            $this->setErr('ERR_SYSTEM',$response['errMsg']);
        }

        $expiryInterestArray = explode(',',$response['expiryInterest']);
        $expiryInterest = implode('、', $expiryInterestArray);

        $res = array(
            'res' => '申请提交成功',
            'projectName' => $response['name'],
            'expiryInterest'=> '加入资产还款日',
            'predictRedeemFinishTime' => '三个工作日内',
            'time' => date("Y-m-d H:i:s",time())
        );

        $this->json_data = $res;
    }
}
