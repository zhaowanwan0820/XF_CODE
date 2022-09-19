<?php
/**
 * 转账Service
 *
 * $this->rpc->local('TransferService\transferById',
 *   array(1, 2, 10000, '转出', '转出一些钱', '转入', '转入一些钱', 'COUPON|2000112'));
 */
namespace core\service;

use libs\utils\PaymentApi;
use core\dao\UserModel;
use core\dao\FinanceQueueModel;
use core\dao\DealModel;
use NCFGroup\Common\Library\Idworker;
use core\dao\FinanceAuditModel;

class TransferService extends BaseService
{

    /**
     * 付款人资金变动类型(默认扣余额)
     */
    public $payerMoneyType = UserModel::TYPE_MONEY;

    /**
     * 付款人是否允许为负
     */
    public $payerNegative = true;

    /**
     * 收款人是否允许为负
     */
    public $receiverNegative = true;

    /**
     * 付款人资金变动是否异步
     */
    public $payerChangeMoneyAsyn = false;

    /**
     * 收款人资金变动是否异步
     */
    public $receiverChangeMoneyAsyn = false;

    /**
     * 按用户Id转账
     */
    public function transferById($payerId, $receiverId, $amount, $payerType, $payerNote, $receiverType, $receiverNote, $outOrderId = '', $payerBizToken = [], $receiverBizToken = [])
    {
        $payerUser = UserModel::instance()->find($payerId);
        if (empty($payerUser)) {
            throw new \Exception('付款用户不存在');
        }
        $receiverUser = UserModel::instance()->find($receiverId);
        if (empty($receiverUser)) {
            throw new \Exception('收款用户不存在');
        }

        return $this->transferByUser($payerUser, $receiverUser, $amount, $payerType, $payerNote, $receiverType, $receiverNote, $outOrderId, $payerBizToken, $receiverBizToken);
    }

    /**
     * 按User对象转账
     */
    public function transferByUser($payerUser, $receiverUser, $amount, $payerType, $payerNote, $receiverType, $receiverNote, $outOrderId = '', $payerBizToken = [], $receiverBizToken = [])
    {
        if ($amount <= 0) {
            throw new \Exception('转账金额必须大于0');
        }

        try {
            $GLOBALS['db']->startTrans();
            $payerAmount = $this->payerMoneyType === UserModel::TYPE_DEDUCT_LOCK_MONEY ? $amount : -$amount;

            $payerUser->changeMoneyAsyn = $this->payerChangeMoneyAsyn;
            $receiverUser->changeMoneyAsyn = $this->receiverChangeMoneyAsyn;

            $payerUser->changeMoney($payerAmount, $payerType, $payerNote, $this->_getAdminId(), 0, $this->payerMoneyType, $this->payerNegative, $payerBizToken);
            $receiverUser->changeMoney($amount, $receiverType, $receiverNote, $this->_getAdminId(), 0, UserModel::TYPE_MONEY, $this->receiverNegative, $receiverBizToken);

            if ($payerUser->changeMoneyDealType !== DealModel::DEAL_TYPE_SUPERVISION) {
                $this->_sync($payerUser->id, $receiverUser->id, $amount, $outOrderId);
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception('转账失败:'.$e->getMessage());
        }

        return true;
    }

    /**
     * 多投使用红包仅进行出资方转账
     */
    public function transferByUserOnlyPayer($payerUser, $receiverUserId, $amount, $payerType, $payerNote)
    {
        if ($amount <= 0) {
            throw new \Exception('转账金额必须大于0');
        }

        try {
            $GLOBALS['db']->startTrans();
            $payerAmount = $this->payerMoneyType === UserModel::TYPE_DEDUCT_LOCK_MONEY ? $amount : -$amount;

            $payerUser->changeMoneyAsyn = $this->payerChangeMoneyAsyn;
            $payerUser->changeMoney($payerAmount, $payerType, $payerNote, $this->_getAdminId(), 0, $this->payerMoneyType, $this->payerNegative);

            $this->_sync($payerUser->id, $receiverUserId, $amount);

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception('转账失败:'.$e->getMessage());
        }

        return true;
    }

    /**
     * 同步到支付 (插入队列)
     */
    private function _sync($payerId, $receiverId, $amount, $outOrderId)
    {
        $data = array(
            'outOrderId' => $outOrderId,
            'payerId' => $payerId,
            'receiverId' => $receiverId,
            'repaymentAmount' => bcmul($amount, 100),
            'curType' => 'CNY',
            'bizType' => 5,
            'batchId' => '',
        );

        if (!FinanceQueueModel::instance()->push(array('orders' => array($data)), 'transfer')) {
            throw new \Exception('插入转账队列失败');
        }
    }

    /**
     * 获取Admin Id
     */
    private function _getAdminId()
    {
        if (!defined('ADMIN_ROOT')) {
            return 0;
        }

        $session = \es_session::get(md5(conf('AUTH_KEY')));
        return isset($session['adm_id']) ? $session['adm_id'] : 0;
    }

    /**
     * transferAndFreeze转账和冻结操作[抽象成方法]
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2015-10-27
     * @param mixed $totalAmount 总金额，有冻结金额时>0，否则为0
     * @param mixed $originAmount 直接转账金额
     * @param mixed $freezeAmount 需冻结金额
     * @param mixed $fromUserId 转出账户id
     * @param mixed $toUserId 转入id
     * @param mixed $fromType 转出类型
     * @param mixed $fromNote 转出备注
     * @param mixed $toType 转入类型
     * @param mixed $toNote 转入备注
     * @param mixed $freezeType 冻结类型
     * @param mixed $freezeNote 冻结备注
     * @static
     * @access private
     * @return void
     */
    public function transferAndFreeze($totalAmount, $originAmount,$freezeAmount, $fromUserId, $toUserId, $fromType, $fromNote, $toType, $toNote, $freezeType, $freezeNote, $outOrderId = '' ) {
        if(isset($totalAmount) && $totalAmount) {
            $this->transferById($fromUserId, $toUserId, $totalAmount, $fromType,
                    $fromNote, $toType, $toNote, $outOrderId);
        } else {
            $this->transferById($fromUserId, $toUserId, $originAmount, $fromType,
                    $fromNote, $toType, $toNote, $outOrderId);
        }
        if (isset($freezeAmount) && $freezeAmount) {
            //冻结操作
            $userObj = UserModel::instance()->find($toUserId);
            $userObj->changeMoneyAsyn = false;
            $userObj->changeMoney($freezeAmount, $freezeType, $freezeNote, 0, 0, UserModel::TYPE_LOCK_MONEY);
        }

    }


    /**
     * 企业用户转账保存逻辑
     * @param array $data
     *      receiverUserId integer 收款方用户ID
     *      receiverUserName string 收款方用户名称
     *      payerUserId integer 付款方用户ID
     *      payerUserName string 付款方用户名称
     *      money float 付款金额
     *      needAudit boolean 是否需要人工审核
     * @return bool
     */
    public function executeEnterpriseTransfer($data) {
        $fm = new FinanceAuditModel();
        $db = $fm->db;
        $db->startTrans();
        try {
            //入记录
            $fm['out_name'] = $data['payerUserName'];
            $fm['type'] = FinanceAuditModel::TYPE_ENTERPRISE_TRANSFER;
            $fm['into_name'] = $data['receiverUserName'];
            $fm['money'] = $data['money'];
            $fm['info'] = $data['memo'];
            $fm['create_time'] = get_gmtime();
            $fm['apply_user'] = $data['payerUserName'];
            $fm['status'] = FinanceAuditModel::STATUS_PASS;
            $fm['log'] = date('Y-m-d H:i:s').' A角色自动审批';

            // 执行A->B转账, 补充资金记录
            $orderId = Idworker::instance()->getId();
            $payerBizToken = [
                'orderId' => $orderId,
            ];
            $receiverBizToken = [
                'orderId' => $orderId,
            ];
            // 转出用户不允许扣负
            $this->payerNegative = false;

            // 是否需要人工审核
            if ($data['needAudit']) {
                $fm['status'] = FinanceAuditModel::STATUS_NEED_B;
                $rs = $fm->insert();
                // 人工审核只生成转账申请记录,状态为B角色待审核或者拒绝
                // 转账申请
                $payerUser = UserModel::instance()->find($data['payerUserId']);
                $payerUser->changeMoney($data['money'], '转账申请', "转账申请 ({$data['receiverUserId']})", $this->_getAdminId(), 0,UserModel::TYPE_LOCK_MONEY , $this->payerNegative, $payerBizToken);
            } else {
                $fm['log'] .=  '<br/>' . date('Y-m-d H:i:s').' B角色自动审批';
                $rs = $fm->insert();
                // 自动审核全套
                // 转账申请
                $payerUser = UserModel::instance()->find($data['payerUserId']);
                $payerUser->changeMoney($data['money'], '转账申请', "转账申请 ({$data['receiverUserId']})", $this->_getAdminId(), 0,UserModel::TYPE_LOCK_MONEY , $this->payerNegative, $payerBizToken);
                // 转出资金
                $payerUser->changeMoney($data['money'], '转出资金', "转出资金 ({$data['receiverUserId']})" , $this->_getAdminId(), 0, UserModel::TYPE_DEDUCT_LOCK_MONEY, $this->payerNegative, $payerBizToken);
                // 转入资金
                $receiverUser = UserModel::instance()->find($data['receiverUserId']);
                $receiverUser->changeMoney($data['money'], '转入资金', "转入资金 ({$data['payerUserId']})" , $this->_getAdminId(), 0, UserModel::TYPE_MONEY, $this->receiverNegative, $receiverBizToken);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

}
