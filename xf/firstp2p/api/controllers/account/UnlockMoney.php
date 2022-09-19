<?php
namespace api\controllers\account;

use api\conf\Error;
use api\controllers\FundBaseAction;
use core\dao\FundMoneyLogModel;
use core\dao\UserModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\web\Form;

/**
 * UnlockMoney
 * 用户余额解冻
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author luzhengshuai@ucfgroup.com
 */
class UnlockMoney extends FundBaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'out_order_id' => array('filter' => 'required'),
            'user_id' => array('filter' => 'int'),
            'money' => array('filter' => 'int'),
            'fund_name' => array('filter' => 'required'),
            'deal_success' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        if ($data['money'] <= 0 || !$data['user_id'] || !$data['deal_success']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数校验失败');
            return false;
        }

        // 验证状态
        if ($data['deal_success'] == 1) {
            $data['event_info'] = FundMoneyLogModel::INFO_DEAL_SUCCESS;
        } else if ($data['deal_success'] == 2) {
            $data['event_info'] = FundMoneyLogModel::INFO_DEAL_FAILED;
        } else {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数校验失败');
            return false;
        }

        // 验证用户
        $user = $this->rpc->local('UserService\getUser', array($data['user_id']));
        if (!$user['id'] || $user['is_effect'] == 0 || $user['is_delete'] == 1) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '无效的用户id');
            return false;
        }

        $data['money'] = number_format($data['money'] / 100, 2, '.', ''); // 转换成元
        if ($user['lock_money'] < $data['money']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '解冻金额超过当前冻结金额');
            return false;
        }

        unset($data['deal_success']);
        $data['event'] = FundMoneyLogModel::EVENT_UNLOCK;

        $resData = $this->rpc->local('FundMoneyLogService\getLogByConditions', array($data['out_order_id'], array('event' => $data['event'])));
        if ($resData && $resData['status'] == FundMoneyLogModel::STATUS_SUCCESS) {
            return true;
        }

        // 和lock记录关联
        $conditions = array(
            'event' => FundMoneyLogModel::EVENT_LOCK,
            'user_id' => $data['user_id'],
            'status' => FundMoneyLogModel::STATUS_SUCCESS
        );
        $lockData = $this->rpc->local('FundMoneyLogService\getLogByConditions', array($data['out_order_id'], $conditions));
        if (!$lockData) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '无相关申购记录');
            return false;
        }

        if (bccomp($lockData['money'], $data['money']) != 0) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '金额和申购金额不一致');
            return false;
        }

        // 插入记录表，失败直接返回
        if (!$resData) {
            $data['status'] = FundMoneyLogModel::STATUS_UNTREATED;
            $data['create_time'] = get_gmtime();
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
