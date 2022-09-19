<?php
/**
 * UserThirdBalanceService.php
 * 此类现已转换为资金账户表,建议 namespace 引入之后 AS UserAccountService做别名，更清晰
 * @date 2017-03-08
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */
namespace core\service;

use core\dao\UserThirdBalanceModel;
use core\dao\UserModel;
use core\dao\DealModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
class UserThirdBalanceService extends BaseService {

    /**
     * getUserSupervisionMoney
     * 获取存管用户余额
     *
     * @param integer $userId
     * @param boolean $slave
     * @access public
     * @return array
     */
    public function getUserSupervisionMoney($userId, $slave = false) {

        $thirdBalance = UserThirdBalanceModel::instance()->getUserThirdBalance($userId, $slave);
        return $thirdBalance[UserThirdBalanceModel::BALANCE_SUPERVISION];
    }

    /**
     * supervisionBonusTransfer
     * 存管红包转账记录
     *
     * @param integer $orderId 标的流水号
     * @access public
     * @return void
     */
    public function supervisionBonusTransfer($orderId) {
        // 永不启用
        return true;

        $idempotentService = new P2pIdempotentService();
        $orderInfo = $idempotentService->getInfoByOrderId($orderId);
        if (empty($orderInfo)) {
            throw new \Exception('订单信息不存在');
        }

        // 投标才有红包
        if ($orderInfo['type'] != P2pIdempotentService::TYPE_DEAL) {
            return true;
        }

        $orderDetail = json_decode($orderInfo['params'], true);
        // 没有红包使用信息
        if (!isset($orderDetail['bonusInfo'])) {
            return true;
        }

        // 必须查询report_status字段，不然isP2pPath判断失效  此处改为主库 线上主从延迟导致未获取到标的信息
        $deal = DealModel::instance()->find($orderInfo['deal_id'], 'name, report_status');
        if (empty($deal)) {
            throw new \Exception('标信息不存在');
        }

        $dealService = new DealService();
        // 不是网贷，不记录
        if (!$dealService->isP2pPath($deal)) {
            return true;
        }

        $receiverId = $orderInfo['loan_user_id'];
        $receiverAmount = $orderDetail['bonusInfo']['money'];
        $receiverInfo = UserModel::instance()->findViaSlave($receiverId);
        $receiverInfo->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;

        $transferService = new TransferService();
        foreach ($orderDetail['bonusInfo']['accountInfo'] as $payAccount) {
            $payerId = $payAccount['rpUserId'];
            $payerInfo = UserModel::instance()->findViaSlave($payerId);
            $payerInfo->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
            $payAmount = $payAccount['rpAmount'];

            $payerType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payerNote = $receiverId ."使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$deal['name']}";
            $payerBizToken = ['dealId' => $orderInfo['deal_id'], 'orderId' => $orderId];
            //$receiverType = '充值';
            $receiverType = '使用' . app_conf('NEW_BONUS_TITLE') . '充值';
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$deal['name']}";
            $receiverBizToken = ['dealId' => $orderInfo['deal_id'], 'orderId' => $orderId];

            $transferService->transferByUser($payerInfo, $receiverInfo, $payAmount, $payerType, $payerNote, $receiverType, $receiverNote, $orderId, $payerBizToken, $receiverBizToken);
        }
        return true;
    }

    /**
     * getUserAccountId
     * 获取用户操作资金所需账户ID
     *
     * @param int $userId
     * @param int $platform
     * @param int $accountType
     * @access public
     * @return mixed
     */
    public function getUserAccountId($userId, $platform, $accountType)
    {
        $accountInfo = UserThirdBalanceModel::instance()->getAccountInfo($userId, $platform, $accountType);
        if (empty($accountInfo)) {
            return false;
        }
        return $accountInfo['id'];
    }


    /**
     * getAccountDesc
     * 获取账户描述
     *
     * @param int $platform 平台类型
     * @param int $accountType 账户类型
     * @access public
     * @return string
     */
    public function getAccountDesc($platform, $accountType)
    {
        if (!empty(UserAccountEnum::$accountDesc[$platform][$accountType])){
            return UserAccountEnum::$accountDesc[$platform][$accountType];
        }
        return '';
    }

    public function getAccountList($userId, $platform)
    {
        $accountList = UserThirdBalanceModel::instance()->getAccountList($userId, $platform);
        if (empty($accountList)) {
            return false;
        }

        return $accountList;
    }

    public function getPlatformAccountDesc($userId, $platform)
    {
        $desc = '';
        $accountList = $this->getAccountList($userId, $platform);
        if (empty($accountList)) {
            return '';
        }

        foreach ($accountList as $key => $account) {
            // 做下刷数据之前的兼容，刷完可删
            if ($account['account_type'] == 0) {
                $user = UserModel::instance()->find($userId, 'user_purpose', true);
                return $this->getAccountDesc($platform, $user['user_purpose']);
            }

            if ($key != 0) {
                $desc .= '|';
            }
            $desc .= $this->getAccountDesc($platform, $account['account_type']);
        }

        return $desc;
    }
}
