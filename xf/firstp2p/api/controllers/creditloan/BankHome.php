<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\payment\supervision\Supervision;

/**
 * BankHome
 * 提交申请，跳转银行页面
 *
 * @uses BaseAction
 * @package default
 */
class BankHome extends AppBaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'totalmoney' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'duration' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'deal_id' => array(
                'filter' => 'required',
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
        try {
            $data = $this->form->data;
            $user_info = $this->getUserByToken();
            // 跳转到银行
            $params = array(
                'userId'    => $user_info['id'],
                'WJnlNo'    => $user_info['id'] . "_" . $data['deal_id'],  // 网信借款申请流水
                'LTime'     => $data['duration'],   // 借款期限/day
                'LAmount'   => $data['totalmoney'], // 贷款申请金额
            );

            $deal = $this->rpc->local('DealService\getDeal', array($data['deal_id'], true));
            if ($deal['report_status'] == 1) {
                //存管服务降级
                if (Supervision::isServiceDown()) {
                    throw new \Exception(Supervision::maintainMessage());
                }
                $isFreePaymentYxt = $this->rpc->local('SupervisionService\isFreePayment', array($user_info['id'], 2));
            }


            // 是否通过四要素验证
            $payVerify = $this->rpc->local('UniteBankPaymentService\isFastPayVerify', array($user_info['id']));
            if($payVerify) {
                $result = $this->rpc->local('UniteBankPaymentService\loanApplyGather', array($params, $user_info));
            }else{
                $returnUrl = 'https://' . APP_HOST.'/creditloan/apply?token='.$data['token'].'&deal_id='.$data['deal_id'];
                $result = $this->rpc->local('PaymentService\getH5BindCardForm',array(array('userId'=>$user_info['id'],'returnUrl'=>$returnUrl)));
            }
            if ($result['respCode'] != "00") {
                throw new \Exception($result['respMsg']);
            }
            $res = $result['data'];
            $res['errcode'] = (isset($isFreePaymentYxt) && !$isFreePaymentYxt) ? 1 : 0;
            $this->json_data = $res;
        } catch (\Exception $e) {
            $this->setErr('0', $e->getMessage());
            return false;
        }
        return true;
    }
}
