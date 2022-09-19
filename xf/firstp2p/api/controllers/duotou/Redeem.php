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
use libs\utils\Rpc;
use core\service\UserService;

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
        if (!$this->dtInvoke())
            return false;

        if (!$this->form->data['deal_loan_id']) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;


//         $dcs = new DealCompoundService();
//         if($dcs->checkIsHoliday(date('Y-m-d'))) {
//             $this->setErr('ERR_MANUAL_REASON','节假日不可赎回');
//             return false;
//         }

        $userService = new UserService($user['id']);
        $request = array(
                'id' => intval($data['deal_loan_id']),
                'userid' => $user['id'],
                'isEnterprise' => $userService->isEnterpriseUser(),
        );

        $response = $this->rpc->local("DtRedeemService\\redeem", $request);

        if(!$response) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        if ($response['errCode'] != 0) {
            if($response['errCode'] == self::REDEEM_MONEY_BEYOND){
                $errMsg = "每日最多可转让/退出本金".number_format($response['maxDayRedemption'])."元\n如有问题请联系客服";
                $this->setErr('ERR_BEYOND_REDEEM_LIMITS',$errMsg);
            }else{
                $this->setErr('ERR_SYSTEM',$response['errMsg']);
            }
            return false;
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
