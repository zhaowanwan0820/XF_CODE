<?php
/**
 * CouponJobsService.php.
 *
 * @date 2015-02-04
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\CouponDealModel;
use core\dao\JobsModel;
use libs\utils\Logger;
use core\dao\CouponDealNcfphModel;
use core\dao\CouponDealThirdModel;

class CouponJobsService
{
    /**
     * 添加更新叠加通知贷的返利天数任务
     *
     * @return bool
     */
    public function addTaskUpdateRebateDaysAll()
    {
        $function = '\core\service\CouponLogService::updateRebateDaysForDeal';
        $list = CouponDealModel::instance()->getCompoundPayList();
        return $this->addDealTask($function, $list);
    }

    /**
     * 添加更新叠加多投宝的返利天数任务
     *
     * @return bool
     */
    public function addTaskUpdateRebateDaysDuotou()
    {
        $function = '\core\service\CouponLogService::updateRebateDaysForDeal';
        $list = CouponDealModel::instance()->getAutoPayDuotouList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_DUOTOU);
    }

    /**
     * 添加通知贷的周期结算任务
     *
     * @return bool
     */
    public function addTaskForCompoundPay()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealModel::instance()->getCompoundPayList();
        return $this->addDealTask($function, $list);
    }

    /**
     * 添加普通标的自动结算任务
     *
     * @return bool
     */
    public function addTaskForAutoPay()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealModel::instance()->getAutoPayList();
        return $this->addDealTask($function, $list);
    }

    /**
     * 添加多投宝的自动结算任务
     *
     * @return bool
     */
    public function addTaskForAutoPayDuotou()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealModel::instance()->getAutoPayDuotouList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_DUOTOU);
    }

    /**
     * 添加黄金定期的自动结算任务
     *
     * @return bool
     */
    public function addTaskForAutoPayGold()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealModel::instance()->getAutoPayGoldList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_GOLD);
    }

    /**
     * 添加黄金活期的自动结算任务
     *
     * @return bool
     */
    public function addTaskForAutoPayGoldc()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealModel::instance()->getAutoPayGoldcList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_GOLDC);
    }

    /**
     * 添加普惠的自动结算任务
     *
     * @return bool
     */
    public function addTaskForAutoPayNcfph()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealNcfphModel::instance()->getAutoPayList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_NCFPH);
    }

    /**
     * 添加第三方标的的自动结算任务
     *
     * @return bool
     */
    public function addTaskForAutoPayThird()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealThirdModel::instance()->getAutoPayList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_THIRD);
    }

    /**
     * 添加第三方标的项目自动结算
     *
     * @return bool
     */
    public function addTaskForAutoPayThirdp()
    {
        $function = '\core\service\CouponLogService::payForDeal';
        $list = CouponDealThirdModel::instance()->getAutoPayThirdpList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_THIRD);
    }

    /**
     * 更新所有标的结清状态
     */
    public function updatePaidDeals()
    {
        $function = '\core\service\CouponDealService::updatePaidDeal';
        $list = CouponDealModel::instance()->getUnPaidList();
        return $this->addDealTask($function, $list);
    }

    /**
     * 更新所有标的结清状态
     */
    public function updatePaidDealsNcfph()
    {
        $function = '\core\service\CouponDealService::updatePaidDeal';
        $list = CouponDealNcfphModel::instance()->getAutoPayList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_NCFPH);
    }

    /**
     * 更新所有第三方标的结清状态
     */
    public function updatePaidDealsThird()
    {
        $function = '\core\service\CouponDealService::updatePaidDeal';
        $list = CouponDealThirdModel::instance()->getAutoPayList();
        return $this->addDealTask($function, $list, CouponLogService::MODULE_TYPE_THIRD);
    }

    /**
     * 修复通知贷返点金额.
     *
     * @return bool
     */
    public function addTaskForUpdateCompoundRebateRatioAmount($type)
    {
        $function = '\core\service\CouponLogService::updateCompoundRebateRatioAmount';
        $list = CouponDealModel::instance()->getCompoundPayList();
        return $this->addDealTask($function, $list, $type);
    }

    /**
     * 添加通知贷的任务
     *
     * @return bool
     */
    private function addDealTask($function, $list, $type = CouponLogService::MODULE_TYPE_P2P)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $function);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($list)) {
            Logger::info(implode(' | ', array_merge($log_info, array('empty deal list'))));
            return true;
        }

        $log_info[] = count($list);
        foreach ($list as $item) {
            $param = array('deal_id' => $item['deal_id'], 'type' => $type);
            $jobsModel = new JobsModel();
            $jobsModel->priority = 30;
            $res = $jobsModel->addJob($function, $param);
            if (empty($res)) {
                Logger::info(implode(' | ', array_merge($log_info, array(json_encode($param), 'add task error'))));
            }
        }
        Logger::info(implode(' | ', array_merge($log_info, array('done'))));
        return true;
    }
}
