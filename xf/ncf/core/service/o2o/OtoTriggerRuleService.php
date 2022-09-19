<?php
/**
 * 随鑫约o2o触发规则服务
 *
 * @date 2017-01-04
 * @author guofeng@ucfgroup.com
 */

namespace core\service\o2o;

use core\service\BaseService;
use core\dao\o2o\OtoTriggerRuleModel;
use core\dao\reserve\UserReservationModel;
use core\dao\deal\DealLoadModel;
use core\dao\reserve\ReservationDealLoadModel;
use core\service\deal\DealService;
use core\service\o2o\CouponService;
use core\service\account\AccountService;
use core\service\reserve\ReservationEntraService;
use core\enum\ReserveEnum;
use core\enum\O2oEnum;
use libs\utils\Logger;

class OtoTriggerRuleService extends BaseService
{
    /**
     * 调用O2O发礼品时的唯一token-礼券
     * @var string
     */
    const O2O_RESERVATION_COUPON = 'RESERVATION_COUPON_%d_%d';

    /**
     * 调用O2O发礼品时的唯一token-投资券
     * @var string
     */
    const O2O_RESERVATION_DISCOUNT = 'RESERVATION_DISCOUNT_%d_%d';

    /**
     * 根据预约类型，获取预约公告或配置信息
     * @param int $type 预约类型
     * @return \libs\db\model
     */
    public function getOtoTriggerRuleListByEntraId($entraId, $type=O2oEnum::TYPE_ACCUMULATE)
    {
        return OtoTriggerRuleModel::instance()->getOtoTriggerRuleListByEntraId($entraId, $type);
    }

    /**
     * 检查触发规则并发送礼券/投资券，赠送礼品
     * @param int 预约id
     */
    public function checkReservationRuleAndSendGift($reserveId, $dealId = 0) {
        try{
            $userReservationModel = new UserReservationModel();
            $userReserveInfo = $userReservationModel->find($reserveId);
            static $_reservationRuleResult = array();
            if (isset($_reservationRuleResult[$userReserveInfo['id']])) {
                return $_reservationRuleResult[$userReserveInfo['id']];
            }

            if (empty($userReserveInfo) || $userReserveInfo['reserve_status'] != ReserveEnum::RESERVE_STATUS_END) {
                throw new \Exception('该预约记录不存在或预约尚未结束', -1);
            }
            // 预约从未投资
            if ($userReserveInfo['invest_amount'] <= 0) {
                throw new \Exception('该预约记录并未匹配投资', -2);
            }
            //查找预约入口
            $entraService = new ReservationEntraService();
            $entra = $entraService->getReserveEntra($userReserveInfo['invest_deadline'], $userReserveInfo['invest_deadline_unit'], $userReserveInfo['deal_type'], $userReserveInfo['invest_rate'], $userReserveInfo['loantype'], -1);
            if (empty($entra)) {
                throw new \Exception('预约入口不存在', -2);
            }

            //查找触发规则
            $ruleList = $this->getOtoTriggerRuleListByEntraId($entra['id'], O2oEnum::TYPE_ACCUMULATE);
            if (empty($ruleList)) {
                throw new \Exception('未配置触发规则', -3);
            }

            foreach ($ruleList as $item) {
                if (empty($item['trigger_info'])) continue;
                // 根据预约ID、咨询机构ID，查询符合条件的累计投资总金额
                if (!empty($item['company'])) {
                    $reservationSumMoneyInfo = UserReservationModel::instance()->getReservationSumMoneyById($userReserveInfo, $item['company']);
                    $investSumMoney = $reservationSumMoneyInfo['investSumMoney'];
                    $dealId <= 0 && $dealId = intval($reservationSumMoneyInfo['dealId']);
                } else {
                    $investSumMoney = bcdiv($userReserveInfo['invest_amount'], 100, 2);
                    // 根据预约ID，获取任意一个标的ID
                    if ($dealId <= 0) {
                        $reservationDealLoadInfo = ReservationDealLoadModel::instance()->getOneLoadByReserveId($userReserveInfo['id']);
                        if (empty($reservationDealLoadInfo)) {
                            throw new \Exception('该笔预约在reservation_deal_load里没有投资记录', -6);
                        }
                        // 根据deal_load_id获取deal_id
                        $dealLoadInfo = DealLoadModel::instance()->findViaSlave($reservationDealLoadInfo['load_id'], 'deal_id');
                        $dealId = $dealLoadInfo['deal_id'];
                    }
                }
                $triggerInfo = json_decode($item['trigger_info'], true);
                foreach ($triggerInfo as $triggerItem) {
                    // 累计投资总金额，在该配置的区间内
                    if (bccomp($investSumMoney, $triggerItem['down_amount'], 2) >= 0 && bccomp($investSumMoney, $triggerItem['up_amount'], 2) < 0) {
                        $ret = $this->_sendGift($userReserveInfo['user_id'], $userReserveInfo['id'], $triggerItem, $investSumMoney, $dealId);
                        if (!$ret) {
                            throw new \Exception('调用O2O发送礼券失败', -4);
                        }
                        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userReserveInfo['user_id'], $userReserveInfo['id'], sprintf('ReservationOtoRule is success, investDeadLine:%d, investDeadLineUnit:%d, dealType:%d, investRate:%s, loantype:%d, dealId:%d, reservationSumMoneyInfo:%s, ruleInfo:%s, sendGiftRet:%s', $userReserveInfo['invest_deadline'], $userReserveInfo['invest_deadline_unit'], $userReserveInfo['deal_type'], $userReserveInfo['invest_rate'], $userReserveInfo['loantype'], $dealId, json_encode($reservationSumMoneyInfo), json_encode($triggerItem), json_encode($ret)))));
                        $_reservationRuleResult[$userReserveInfo['id']] = array('respCode'=>0, 'respMsg'=>'SUCCESS');
                        return $_reservationRuleResult[$userReserveInfo['id']];
                    }
                }
            }
            throw new \Exception('该预约没有符合的触发规则', -5);
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userReserveInfo['user_id'], $userReserveInfo['id'], sprintf('investDeadLine:%d, investDeadLineUnit:%d, dealType:%d, investRate:%s, loantype:%d, dealId:%d, ruleList:%s, Exception:%s', $userReserveInfo['invest_deadline'], $userReserveInfo['invest_deadline_unit'], $userReserveInfo['deal_type'], $userReserveInfo['invest_rate'], $userReserveInfo['loantype'], $dealId, (!empty($ruleList) ? json_encode($ruleList) : ''), $e->getMessage()))));
            $_reservationRuleResult[$userReserveInfo['id']] = array('respCode'=>$e->getCode(), 'respMsg'=>$e->getMessage());
            return $_reservationRuleResult[$userReserveInfo['id']];
        }
    }

    /**
     * O2O赠送礼品
     * @param int $accountId 账户ID
     * @param int $reserveId 预约ID
     * @param array $ruleInfo 触发规则
     * @param int $investSumMoney 该预约的累计投资金额
     * @param int $dealId 标的ID
     */
    private function _sendGift($accountId, $reserveId, $ruleInfo, $investSumMoney, $dealId) {
        try{
            if (empty($ruleInfo)) {
                throw new \Exception('触发规则为空', -1);
            }
            $sendGiftRet = false;
            // 礼品ID（多个以逗号隔开）
            $awardIdTmp = explode(',', trim($ruleInfo['award_id']));
            $awardIdArray = array_filter(array_unique(array_map('intval', $awardIdTmp)), 'strlen');
            $awardIdString = join(',', $awardIdArray);

            $rebateAmount = 0;
            $investSumMoneyYear = '';
            $userId = AccountService::getUserId($accountId);
            switch ($ruleInfo['award_type']) {
                case 1: // 礼券
                    // 计算年化投资额
                    if (!empty($ruleInfo['rate']) && is_numeric($ruleInfo['rate'])) {
                        $investSumMoneyYear = \core\service\deal\DealService::getAnnualizedAmountByDealIdAndAmount($dealId, $investSumMoney);
                        $investSumMoneyYearCent = bcmul($investSumMoneyYear, 100, 2);
                        // 舍去取整
                        $rebateAmountCent = floor(($investSumMoneyYearCent * $ruleInfo['rate']) / 100);
                        // 把返利金额转换成元
                        $rebateAmount = bcdiv($rebateAmountCent, 100, 2);
                    }
                    if (empty($rebateAmount) || bccomp($rebateAmount, '0.00', 2) <= 0) {
                        throw new \Exception('返利金额小于0，无法发送礼券红包', -2);
                    }
                    // 生成唯一token
                    $token = sprintf(self::O2O_RESERVATION_COUPON, $userId, $reserveId);
                    $sendGiftRet = CouponService::acquireCoupons($userId, $awardIdString, $token, '', 0, false, $rebateAmount);
                    break;
                case 2: // 投资券
                    // 生成唯一token
                    $token = sprintf(self::O2O_RESERVATION_DISCOUNT, $userId, $reserveId);
                    $sendGiftRet = CouponService::acquireDiscounts($userId, $awardIdString, $token);
                    break;
            }
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, $reserveId, sprintf('token:%s, dealId:%d, investSumMoney:%s, awardIdString:%s, investSumMoneyYear:%s, rebateAmount:%s, ruleInfo:%s, sendGiftRet:%s', $token, $dealId, $investSumMoney, $awardIdString, $investSumMoneyYear, $rebateAmount, json_encode($ruleInfo), json_encode($sendGiftRet)))));
            return $sendGiftRet;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, $reserveId, sprintf('dealId:%d, investSumMoney:%s, awardIdString:%s, investSumMoneyYear:%s, rebateAmount:%s, ruleInfo:%s, ExceptionCode:%d, ExceptionMsg:%s', $dealId, $investSumMoney, $awardIdString, $investSumMoneyYear, $rebateAmount, json_encode($ruleList), $e->getCode(), $e->getMessage()))));
            return false;
        }
    }
}
