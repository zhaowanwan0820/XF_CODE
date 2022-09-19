<?php
namespace api\controllers\account;

use api\conf\Error;
use core\dao\FundMoneyLogModel;
use libs\utils\Aes;
use api\controllers\FundBaseAction;
use libs\web\Form;
use core\service\UserCarryService;

/**
 * LockMoney
 * 用户余额冻结
 */
class LockMoney extends FundBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'out_order_id' => array('filter' => 'required'),
            'user_id' => array('filter' => 'int'),
            'money' => array('filter' => 'int'),
            'fund_name' => array('filter' => 'required'),
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

        $user = $this->rpc->local('UserService\getUser', array($data['user_id']));
        if (!$user['id'] || $user['is_effect'] == 0 || $user['is_delete'] == 1 || $user['is_enterprise_user']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '无效的用户id');
            return false;
        }

        //私募或者基金投资检查是否超过用户账号设置的限制金额
        $userCarryService = new UserCarryService();
        $canWithdraw = $userCarryService->canWithdrawAmount($data['user_id'], $data['money']);
        if (!$canWithdraw) {
            $this->setErr('ERR_MONEY_LIMIT');
            return false;
        }

        $data['event'] = FundMoneyLogModel::EVENT_LOCK;
        $data['money'] = number_format($data['money'] / 100, 2, '.', ''); // 转换成元

        $resData = $this->rpc->local('FundMoneyLogService\getLogByConditions', array($data['out_order_id']));
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
