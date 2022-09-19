<?php
/**
 * CouponDealService.php.
 *
 * @date 2015-03-16
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealLoanTypeModel;
use core\dao\CouponDealModel;
use core\dao\CouponLogModel;
use core\dao\DealModel;
use libs\utils\Logger;

class CouponDealService
{
    public $coupon_deal_dao;

    public $module;

    public function __construct($module = CouponLogService::MODULE_TYPE_P2P)
    {
        $this->module = $module;
        if (empty($this->module) || !in_array($this->module, CouponLogService::$module_map)) {
            throw new \Exception('module['.$module.'] is not exist!');
        }
        $this->coupon_deal_dao = CouponDealModel::getInstance($module);
        $this->coupon_log_dao = CouponLogModel::getInstance($module);
    }

    /**
     * 更新标的结清状态为已结算.
     */
    public function updatePaidDeal($deal_id, $module)
    {
        $this->module = $module;
        $this->coupon_log_dao = CouponLogModel::getInstance($module);
        $this->coupon_deal_dao = CouponDealModel::getInstance($module);
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));

        if (CouponLogService::MODULE_TYPE_P2P == $this->module) {
            $deal_model = new DealModel();
            $deal = $deal_model->find($deal_id, 'deal_status');
            if (empty($deal)) {
                Logger::info(implode(' | ', array_merge($log_info, array('error deal_id'))));

                return false;
            }
            if (!in_array($deal['deal_status'], array('4', '5'))) {
                Logger::info(implode(' | ', array_merge($log_info, array('没满标放款，略过不更新'))));

                return true;
            }

            $list = $this->coupon_log_dao->findNotExistsByDealId($deal_id);
            if (!empty($list)) {
                Logger::info(implode(' | ', array_merge($log_info, array('存在异步处理未完成的优惠码记录，不更新'))));

                return true;
            }
        }

        $list = $this->coupon_log_dao->findByDealId($deal_id, array(CouponService::PAY_STATUS_NOT_PAY, CouponService::PAY_STATUS_FINANCE_AUDIT,
                                                                CouponService::PAY_STATUS_FINANCE_REJECTED, CouponService::PAY_STATUS_PAYING, ), 'id,pay_status');
        if (!empty($list)) {
            Logger::info(implode(' | ', array_merge($log_info, array('存在未结算的优惠码记录，不更新'))));

            return true;
        }

        $rs = $this->coupon_deal_dao->updatePaidDeal($deal_id);
        Logger::info(implode(' | ', array_merge($log_info, array('done', $rs))));

        return $rs;
    }

    /**
     * 补充旧数据的优惠码结算配置信息.
     */
    public function initCouponDealData()
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $deal_coupon_model = new CouponDealModel();
        $deal_list = $deal_coupon_model->getDealListNotExists();
        $fail_list = array();
        foreach ($deal_list as $deal) {
            $deal_coupon = new CouponDealModel();
            $deal_coupon->deal_id = $deal['id'];
            $deal_coupon->pay_type = $deal['coupon_pay_type'];
            $deal_coupon->pay_auto = (CouponLogService::DEAL_TYPE_COMPOUND == $deal['deal_type']) ? CouponDealModel::PAY_AUTO_YES : CouponDealModel::PAY_AUTO_NO;
            $deal_coupon->is_paid = CouponDealModel::IS_PAID_NO;
            $deal_coupon->rebate_days = ('5' == $deal['loantype']) ? $deal['repay_time'] : $deal['repay_time'] * CouponLogService::DAYS_OF_MONTH;
            $deal_coupon->create_time = get_gmtime();
            $deal_coupon->update_time = get_gmtime();
            $rs = $deal_coupon->insert();
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($deal_coupon->getRow()), 'done', $rs))));
            if (empty($rs)) {
                $fail_list[] = $deal['id'];
            }
        }
        Logger::info(implode(' | ', array_merge($log_info, array('done', 'count:'.count($deal_list), 'fail list:', json_encode($fail_list)))));

        return true;
    }

    /**
     * 增加单条couponDeal记录.
     *
     * @param array $data
     *
     * @return bool
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }
        $coupon_deal_model = new CouponDealModel();
        $coupon_deal_model->deal_id = $data['deal_id'];
        $pay_type = $coupon_deal_model::PAY_TYPE_FANGKUAN;
        $pay_auto = $coupon_deal_model::PAY_AUTO_YES;
        $rebate_days = (5 == $data['loantype']) ? trim($data['repay_time']) : trim($data['repay_time']) * 30;

        if (in_array($data['deal_type'], CouponLogService::$deal_type_group1)) {
            // 编辑标和复制标两种情况
            if (isset($data['rebate_days'])) {
                $rebate_days = $data['rebate_days'];
            }
            if (isset($data['pay_type'])) {
                $pay_type = $data['pay_type'];
            }
            // 默认值是2 手工结算没有0
            $pay_auto = empty($data['pay_auto']) ? $coupon_deal_model::PAY_AUTO_NO : $data['pay_auto'];
        }

        $coupon_deal_model->pay_type = $pay_type;
        $coupon_deal_model->pay_auto = $pay_auto;
        $coupon_deal_model->rebate_days = $rebate_days;
        $coupon_deal_model->is_paid = $coupon_deal_model::IS_PAID_NO;
        $coupon_deal_model->create_time = get_gmtime();
        $coupon_deal_model->update_time = get_gmtime();
        $coupon_deal_model->start_pay_time = $data['start_pay_time'];
        $coupon_deal_model->is_rebate = $data['is_rebate'];

        return  $coupon_deal_model->insert();
    }

    /**
     * 更新标优惠码返利天数.
     *
     * @param int $deal_id
     * @param int $rebate_days
     *
     * @return bool
     */
    public function updateRebateDaysByDealId($deal_id, $rebate_days, $pay_type = false, $pay_auto = false,$is_rebate = false)
    {
        $GLOBALS['db']->startTrans();

        try {
            $deal_id = intval($deal_id);
            if ($deal_id <= 0) {
                throw new \Exception('标id不能小于等于0');
            }

            $rebate_days = intval($rebate_days);
            if ($rebate_days <= 0) {
                throw new \Exception('返利天数不能小于等于0');
            }

            $deal_coupon_data = array(
                    'deal_id' => $deal_id,
                    'rebate_days' => $rebate_days,
                    'update_time' => get_gmtime(),
            );

            if (false !== $pay_type) {
                $deal_coupon_data['pay_type'] = intval($pay_type);
            }

            if (false !== $pay_auto) {
                $deal_coupon_data['pay_auto'] = intval($pay_auto);
            }

            if(false != $is_rebate){
                $deal_coupon_data['is_rebate'] = intval($is_rebate);
            }

            //获取标类型
            $deal_model = new DealModel();
            $deal_info = $deal_model->find($deal_id, 'deal_type,repay_time,loantype,repay_start_time,type_id');
            if (empty($deal_info)) {
                throw new \Exception('标信息不存在');
            }
            $deal_type = $deal_info['deal_type'];

            //结算锁定天数
            $delay_days = 0;
            $dealLoanTypeModel = new DealLoanTypeModel();
            $loan_tag = $dealLoanTypeModel->getLoanTagByTypeId($deal_info['type_id']);
            if (DealLoanTypeModel::TYPE_XFFQ == $loan_tag) {
                $delay_days = 7;
            }
            $deal_coupon_data['start_pay_time'] = $delay_days * 86400 + $deal_info['repay_start_time'];

            //更新标优惠码返利天数
            $coupon_deal_model = new CouponDealModel();
            $deal_coupon_info = $coupon_deal_model->findBy('deal_id='.$deal_id, 'rebate_days');
            if (empty($deal_coupon_info)) {
                $deal_coupon_data['repay_time'] = $deal_info['repay_time'];
                $deal_coupon_data['loantype'] = $deal_info['loantype'];
                $res = $this->add($deal_coupon_data);
            } else {
                $res = $coupon_deal_model->updateBy($deal_coupon_data, ' deal_id='.$deal_id);
            }
            if (!$res) {
                throw new \Exception('更新标优惠码返利天数失败');
            }

            // 更新返利天数更新返点比例金额
            if (in_array($deal_type, CouponLogService::$deal_type_group1)) {
                if (empty($deal_coupon_info) || $deal_coupon_info['rebate_days'] != $deal_coupon_data['rebate_days']) {
                    $coupon_service_obj = new CouponLogService();
                    $res = $coupon_service_obj->updateRebateDaysAndAmount($deal_id, $deal_coupon_data['rebate_days']);

                    if (!$res) {
                        throw new \Exception('根据返利天数更新返点比例金额失败');
                    }
                }
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__,  json_encode($deal_coupon_data), 'error:'.$e->getMessage())));

            return false;
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__,  json_encode($deal_coupon_data), '更新成功')));

        return true;
    }

    private function save($data,$dealInfo)
    {
        $GLOBALS['db']->startTrans();
        $deal_coupon_data = array();
        try {
            $dealId = intval($data['dealId']);
            if ($dealId <= 0) {
                throw new \Exception('标id不能小于等于0');
            }

            //计算返利天数
            if(isset($data['rebateDays']) && !empty($data['rebateDays'])){
                $rebateDays = intval($data['rebateDays']);
            }else{
                $rebateDays = (5 == $dealInfo['loantype']) ? $dealInfo['repay_time'] : $dealInfo['repay_time'] * 30;
            }

            if ($rebateDays <= 0) {
                throw new \Exception('返利天数不能小于等于0');
            }

            $dealStatus = intval($dealInfo['deal_status']);
            $loantype = intval($dealInfo['loantype']);
            $repayTime = intval($dealInfo['repay_time']);
            $dealType = intval($dealInfo['deal_type']);

            $deal_coupon_data = array(
                    'deal_id' => $dealId,
                    'rebate_days' => $rebateDays,
                    'deal_status' => $dealStatus,
                    'deal_type' => $dealType,
                    'loantype' => $loantype,
                    'repay_time' => $repayTime,
                    'update_time' => get_gmtime(),
            );

            $payType = intval($data['payType']);
            if (!empty($payType)) {
                $deal_coupon_data['pay_type'] = $payType;
            } else {
                $deal_coupon_data['pay_type'] = CouponDealModel::PAY_TYPE_FANGKUAN;
            }

            $payAuto = intval($data['payType']);
            if (!empty($payAuto)) {
                $deal_coupon_data['pay_auto'] = $payAuto;
            } else {
                $deal_coupon_data['pay_auto'] = CouponDealModel::PAY_AUTO_YES;
            }

            if(!empty($data['isRebate'])){
                $deal_coupon_data['is_rebate'] = $data['isRebate'];
            }

            //邀请码返利开始结算时间
            $deal_coupon_data['start_pay_time'] = intval($dealInfo['repay_start_time']);

            //更新标优惠码返利天数
            $coupon_deal_model = $this->coupon_deal_dao;
            $deal_coupon_info = $coupon_deal_model->findBy('deal_id='.$dealId, 'rebate_days,start_pay_time,is_rebate');
            if (empty($deal_coupon_info)) {
                $coupon_deal_model->deal_id = $deal_coupon_data['deal_id'];
                $coupon_deal_model->pay_type = $deal_coupon_data['pay_type'];
                $coupon_deal_model->pay_auto = $deal_coupon_data['pay_auto'];
                $coupon_deal_model->rebate_days = $deal_coupon_data['rebate_days'];
                $coupon_deal_model->deal_status = $deal_coupon_data['deal_status'];
                $coupon_deal_model->start_pay_time = $deal_coupon_data['start_pay_time'];
                $coupon_deal_model->loantype = $deal_coupon_data['loantype'];
                $coupon_deal_model->repay_time = $deal_coupon_data['repay_time'];
                $coupon_deal_model->deal_type = $deal_coupon_data['deal_type'];
                $coupon_deal_model->is_rebate = isset($deal_coupon_data['is_rebate'])?$deal_coupon_data['is_rebate']:$deal_coupon_info['is_rebate'];
                $coupon_deal_model->is_paid = $coupon_deal_model::IS_PAID_NO;
                $coupon_deal_model->create_time = get_gmtime();
                $coupon_deal_model->update_time = get_gmtime();
                $res = $coupon_deal_model->insert();
            } else {
                $deal_coupon_data['update_time'] = get_gmtime();
                $res = $coupon_deal_model->updateBy($deal_coupon_data, ' deal_id='.$dealId);
            }

            if (!$res) {
                throw new \Exception('保存标优惠码返利天数失败');
            }

            // 更新返利天数更新返点比例金额
            if (!empty($deal_coupon_info) && $deal_coupon_info['rebate_days'] != $deal_coupon_data['rebate_days']) {
                $this->coupon_log_dao->updateRebateDaysAndAmount($dealId, $deal_coupon_data['rebate_days']);
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__,  'deal_coupon_data:'.json_encode($deal_coupon_data),'data:'.json_encode($data),'error:'.$e->getMessage())));
            throw new \Exception($e->getMessage());
            return false;
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__,  'deal_coupon_data:'.json_encode($deal_coupon_data),'data:'.json_encode($data),'更新成功')));

        return true;
    }

    public function getCouponDealByDealId($dealId)
    {
        $deal_coupon_info = $this->coupon_deal_dao->findBy('deal_id='.$dealId);
        if (!empty($deal_coupon_info)) {
            return $deal_coupon_info->getRow();
        }

        return false;
    }

    /**
    *
    */
    public function handleCoupon($dealId,$payType = false,$payAuto = false,$rebateDays = false,$isRebate = false)
    {
        try {
            $log_info = array(__CLASS__, __FUNCTION__, $this->module, 'dealId:'.$dealId);
            Logger::info(implode(' | ', array_merge($log_info, array('start'))));

            $GLOBALS['db']->startTrans();

            $dealInfo = (new CouponService($this->module))->getDealInfoByDealId($dealId);
            if (empty($dealInfo)) {
                throw new \Exception('标信息不存在');
            }

            switch ($dealInfo['deal_status']) {
                case DealModel::$DEAL_STATUS['waiting']:
                    $this->waitingDeal($dealInfo,$payType,$payAuto,$rebateDays,$isRebate);
                    break;
                case DealModel::$DEAL_STATUS['progressing']:
                    $this->waitingDeal($dealInfo,$payType,$payAuto,$rebateDays);
                    break;
                case DealModel::$DEAL_STATUS['full']:
                    $this->fullDeal($dealInfo);
                    break;
                case DealModel::$DEAL_STATUS['failed']:
                    $this->failDeal( $dealInfo);
                    break;
                case DealModel::$DEAL_STATUS['repaying']:
                    $this->loansDeal($dealInfo);
                    break;
                case DealModel::$DEAL_STATUS['repaid']:
                    $this->repayDeal($dealInfo);
                break;
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__,'dealId:'.$dealId, 'deal_status:'.$dealInfo['deal_status'], 'error:'.$e->getMessage(), '更新失败')));
            $GLOBALS['db']->rollback();
            return false;
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__,'dealId:'.$dealId, 'deal_status:'.$dealInfo['deal_status'],'更新成功')));
        return true;
    }

    /*
    *上标,标进行中
    */
    public function waitingDeal($dealInfo,$payType=false,$payAuto=false,$rebateDays=false,$isRebate = false){
        $data = array('dealId'=>$dealInfo['id'],'payType'=>$payType,'payAuto'=>$payAuto,'rebateDays'=>$rebateDays,'isRebate'=>$isRebate);
        return $this->save($data,$dealInfo);
    }

    /*
    *还款
    */
    private function repayDeal($dealInfo)
    {
        $data = array('update_time' => get_gmtime());
        $couponDealInfo = $this->getCouponDealByDealId($dealInfo['id']);
        if (empty($couponDealInfo)) {
            throw new \Exception('邀请码标信息不存在');
        }
        //还清是结算
        if (1 == $couponDealInfo['pay_type']) {
            $rebate_days = floor((get_gmtime() - $dealInfo['repay_start_time']) / 86400); // 优惠码返利天数=操作日期-放款日期
            if ($rebate_days <= 0) {
                throw new \Exception('优惠码返利天数不能为负值:rebate_days:'.$rebate_days);
            }
            $data['rebate_days'] = $rebate_days;
            // 更新返利天数更新返点比例金额
            $this->coupon_log_dao->updateRebateDaysAndAmount($couponDealInfo['deal_id'], $data['rebate_days']);
        }

        $data['deal_status'] = DealModel::$DEAL_STATUS['repaid'];
        $res = $this->coupon_deal_dao->updateBy($data, 'deal_id='.$couponDealInfo['deal_id']);
        if (empty($res)) {
            throw new \Exception('还款更新标状态失败');
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__,'data:'.json_encode($data), 'done')));
    }

    //流标
    private function failDeal($dealInfo)
    {
        $data = array('update_time' => get_gmtime());
        $couponDealInfo = $this->getCouponDealByDealId($dealInfo['id']);
        if (empty($couponDealInfo)) {
            throw new \Exception('邀请码标信息不存在');
        }
        $data['deal_status'] = DealModel::$DEAL_STATUS['failed'];
        $res = $this->coupon_deal_dao->updateBy($data, ' deal_id='.$couponDealInfo['deal_id']);
        if (empty($res)) {
            throw new \Exception('流标更新标状态失败');
        }
        $couponService = new CouponService($this->module);
        $res = $couponService->updateLogStatusByDealId($couponDealInfo['deal_id'], 2);
        if (!$res) {
            throw new \Exception('流标更新couponLogDealStatus失败');
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'data:'.json_encode($data), 'done')));
    }

    //放款
    private function loansDeal($dealInfo)
    {
        $data = array('update_time' => get_gmtime());
        $couponDealInfo = $this->getCouponDealByDealId($dealInfo['id']);
        if (empty($couponDealInfo)) {
            throw new \Exception('邀请码标信息不存在');
        }
        $data['deal_status'] = DealModel::$DEAL_STATUS['repaying'];
        $data['start_pay_time'] = $dealInfo['repay_start_time'];
        $res = $this->coupon_deal_dao->updateBy($data, ' deal_id='.$couponDealInfo['deal_id']);
        if (empty($res)) {
            throw new \Exception('放款更新标状态失败');
        }

        $deal_repay_time = 86400 * $couponDealInfo['rebate_days'] + $dealInfo['repay_start_time'];
        $res = $this->coupon_log_dao->updateLogByDealId($couponDealInfo['deal_id'], $deal_repay_time, $dealInfo['repay_start_time']);
        if (!$res) {
            throw new \Exception('放款更新起息时间和回款时间失败');
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'data:'.json_encode($data), 'done')));
    }

    //满标
    private function fullDeal($dealInfo)
    {
        $data = array();
        $couponDealInfo = $this->getCouponDealByDealId($dealInfo['id']);
        if (empty($couponDealInfo)) {
            throw new \Exception('邀请码标信息不存在');
        }
        $data = array('update_time' => get_gmtime());
        $data['deal_status'] = DealModel::$DEAL_STATUS['full'];
        $res = $this->coupon_deal_dao->updateBy($data, ' deal_id='.$couponDealInfo['deal_id']);
        if (empty($res)) {
            throw new \Exception('满标更新标状态失败');
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'data:'.json_encode($data), 'done')));
    }

}
