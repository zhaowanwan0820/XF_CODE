<?php
/**
 * 存管提现辅助函数
 */
namespace core\service\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use core\enum\SupervisionEnum;
use core\dao\supervision\SupervisionWithdrawModel;
use core\dao\deal\DealModel;
use core\service\supervision\SupervisionBaseService;
use core\service\deal\P2pDealGrantService;
use libs\utils\PaymentApi;
use libs\db\Db;

class SupervisionWithdrawService extends SupervisionBaseService {
    /**
     * 根据标的ID获取最新的借款人提现申请记录
     */
    public function getLatestByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if (empty($deal_id)) {
            return false;
        }
        $condition = " `bid` = '{$deal_id}' ORDER BY `id` DESC LIMIT 1";
        $item = SupervisionWithdrawModel::instance()->findBy($condition);
        return $item;
    }

    /**
     * 判断是否可以重新发起提现
     */
    public function canRedoWithdraw($withdraw, $userId = 0) {
        // 非本人订单拒绝重新提现
        if (!empty($userId) && $withdraw['user_id'] != $userId)
        {
            PaymentApi::log(__FUNCTION__.', userid not same.'.json_encode($withdraw));
            return false;
        }
        // 只有最近的提现失败的订单才可以提现
        if (!empty($withdraw['bid']) && $withdraw['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_FAILED) {
            $latest = $this->getLatestByDealId($withdraw['bid']);
            if (!empty($latest) && $latest['id'] == $withdraw['id']) {
                return true;
            }
        }
        PaymentApi::log(__FUNCTION__.',not fail status or not latest. '.json_encode($withdraw));
        return false;
    }

    public function getWithdrawByOrderId($outOrderId) {
        return SupervisionWithdrawModel::instance()->getWithdrawRecordByOutId($outOrderId);
    }


    /**
     * 重新提现
     * @param integer $outOrderId  提现外部订单号
     * @param integer $userId 校验订单是否本人提现
     * @return boolean
     */
    public function redoWithdraw($outOrderId, $userId = 0)
    {
        $withdraw = $this->getWithdrawByOrderId($outOrderId);
        if (empty($withdraw)) {
            PaymentApi::log(__FUNCTION__.' fail, no withdraw log for order '.$outOrderId);
            return false;
        }

        // 检查是否可重新申请提现
        $canRedo = $this->canRedoWithdraw($withdraw, $userId);
        if (empty($canRedo)) {
            PaymentApi::log(__FUNCTION__.' fail, check can redo fail.'.$outOrderId);
            return false;
        }

        try {
            $gtm = new GlobalTransactionManager();
            $gtm->setName('RedoGrantWithdraw');

            // {{{ 复制原申请记录
            $withdrawNewData = $withdraw;
            $withdrawNewData['out_order_id'] = Idworker::instance()->getId();
            unset($withdrawNewData['withdraw_status']);
            unset($withdrawNewData['update_time']);
            unset($withdrawNewData['id']);
            unset($withdrawNewData['create_time']);
            unset($withdrawNewData['user_id']);
            unset($withdrawNewData['amount']);

            // 如果是标放款提现
            if ($withdraw['bid'] != '') {
                // 审批状态
                $deal = DealModel::instance()->find($withdraw['bid']);
                if (empty($deal)) {
                    throw new \Exception('非法操作');
                }
            }
            // {{{ 请求存管行提现
            $params = [];
            // 放款提现处理
            $params['orderId'] =  $withdrawNewData['out_order_id'];
            $params['dealId'] = $withdraw['bid'];
            $params['grantMoney'] = bcdiv($withdraw['amount'], 100, 2);
            $withdrawEvent = new EventMaker([
                'commit' => [(new P2pDealGrantService()), 'afterGrantWithdraw', $params],
            ]);
            $gtm->addEvent($withdrawEvent);
            // }}}

            // 更新提现记录-新
            $withdrawUpdateEvent = new EventMaker([
                'commit' => [(new self()), 'updateRedoWithdraw', [$withdrawNewData, $withdraw['out_order_id']]],
            ]);
            $gtm->addEvent($withdrawUpdateEvent);

            $withdrawRet = $gtm->execute();
            if (true !== $withdrawRet) {
                throw new \Exception($gtm->getError());
            }

            PaymentApi::log(sprintf('%s, redoWithdraw success, oldWithdrawInfo:%s, newWithdrawOrderId:%d', __FUNCTION__, json_encode($withdraw), $withdrawNewData['out_order_id']));
            return true;
        } catch( \Exception $e) {
            PaymentApi::log('redoWithdraw fail, outOrderId:'.$withdraw['out_order_id'].',msg:'.$e->getMessage());
            return false;
        }
    }

    public function updateRedoWithdraw($newWithdraw, $oldOrderId)
    {
        try {
            $db = Db::getInstance('firstp2p', 'master');
            $db->startTrans();
            // 表名
            $tableName = SupervisionWithdrawModel::instance()->tableName();
            $db->autoExecute($tableName, $newWithdraw, 'UPDATE', 'out_order_id = '.$newWithdraw['out_order_id']);
            $db->autoExecute($tableName, ['update_time' => get_gmtime(),], 'UPDATE', ' out_order_id = '.$oldOrderId);
            $db->commit();
            return true;
        } catch (\Exception $e) {
            PaymentApi::log(__FUNCTION__.' execute failed, exceptionMsg:' . $e->getMessage());
            $db->rollback();
            return false;
        }
    }
}
