<?php
namespace api\controllers\account;

use api\conf\Error;
use core\dao\FundMoneyLogModel;
use core\dao\UserModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Aes;
use api\controllers\FundBaseAction;
use libs\web\Form;

/**
 * Refund
 * 回款
 */
class Refund extends FundBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'out_order_id' => array('filter' => 'required'),
            'user_id' => array('filter' => 'int'),
            'money' => array('filter' => 'int'),
            'fund_name' => array('filter' => 'required'),
            'refund_type' => array('filter' => 'int')
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        if ($data['money'] <= 0 || !$data['user_id']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数校验失败');
            return false;
        }

        $refundTypeMap = array(
            1 => FundMoneyLogModel::INFO_FUND_REFUND,
            2 => FundMoneyLogModel::INFO_FUND_FAILED,
            3 => FundMoneyLogModel::INFO_FUND_BONUS,
            4 => FundMoneyLogModel::INFO_FUND_REPAYMENT_BONUS,
        );
        // 验证状态
        if (isset($refundTypeMap[$data['refund_type']])) {
            $data['event_info'] = $refundTypeMap[$data['refund_type']];
        } else {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数校验失败');
            return false;
        }

        $user = $this->rpc->local('UserService\getUser', array($data['user_id']));
        if (!$user['id'] || $user['is_effect'] == 0 || $user['is_delete'] == 1) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '无效的用户id');
            return false;
        }

        $data['event'] = FundMoneyLogModel::EVENT_REFUND;
        $data['money'] = number_format($data['money'] / 100, 2, '.', ''); // 转换成元

        $resData = $this->rpc->local('FundMoneyLogService\getLogByConditions', array($data['out_order_id'], array('event' => $data['event'])));
        if ($resData && $resData['status'] == FundMoneyLogModel::STATUS_SUCCESS) {
            return true;
        }

        // 插入记录表，失败直接返回
        if (!$resData) {
            $data['create_time'] = get_gmtime();
            $data['status'] = FundMoneyLogModel::STATUS_UNTREATED;
            $result = $this->rpc->local('FundMoneyLogService\insertLog', array($data));
            if (!$result) {
                $this->setErr('0');
                return false;
            }
        }

        try {
            return $this->rpc->local('FundMoneyLogService\fundChangeMoney', array($data));
        } catch (\Exception $e) {
            $this->setErr('0', $e->getMessage());
            return false;
        }
    }
}
