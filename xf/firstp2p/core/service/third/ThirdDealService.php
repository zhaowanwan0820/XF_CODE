<?php

namespace core\service\third;

use core\dao\third\ThirdDealModel;
use core\dao\DealModel;
use core\service\BaseService;
use core\service\CouponLogService;

class ThirdDealService extends BaseService
{
    const DAY_UNIT = 1;
    const MONTH_UNIT = 2;
    const SEASON_UNIT = 3;
    const YEAR_UNIT = 4;

    public static $unitDays = array(
        self::DAY_UNIT => 1,
        self::MONTH_UNIT=> 30,
        self::SEASON_UNIT=> 90,
        self::YEAR_UNIT => 360
        );

    //第三方标的状态和p2p定义的标状态流标和已放款是相反
    public static function toP2pDealStatus($status)
    {
        switch ($status) {
            case ThirdDealModel::$DEAL_STATUS['waiting']:
                return DealModel::$DEAL_STATUS['waiting'];
                break;
            case ThirdDealModel::$DEAL_STATUS['progressing']:
                return DealModel::$DEAL_STATUS['progressing'];
                break;
            case ThirdDealModel::$DEAL_STATUS['full']:
                return DealModel::$DEAL_STATUS['full'];
                break;
            case ThirdDealModel::$DEAL_STATUS['repaying']:
                return DealModel::$DEAL_STATUS['repaying'];
                break;
            case ThirdDealModel::$DEAL_STATUS['failed']:
                return DealModel::$DEAL_STATUS['failed'];
                break;
            case ThirdDealModel::$DEAL_STATUS['repaid']:
                return DealModel::$DEAL_STATUS['repaid'];
                break;
            default:
                return -1;
                break;
        }
    }

    //获取标信息
    public function getDeal($id)
    {
        $dealInfo = array();
        $thirdDealModel = new ThirdDealModel();
        $result = $thirdDealModel->find($id);
        if (!empty($result)) {
            $dealInfo = $this->formatDeal($result);
        }
        return $dealInfo;
    }

    private function formatDeal($deal)
    {
        $dealInfo = array();
        $dealInfo['id'] = $deal['id'];
        $dealInfo['client_name'] = $deal['client_name'];
        $dealInfo['deal_status'] = self::toP2pDealStatus($deal['status']);
        $dealInfo['name'] = $deal['deal_name'];
        //还款方式枚举值为5时判断term_unit 是否为1，不为1为错误的值，loantype赋值-1 并且不信任第三方所传值，所以对于不在0-10之间的异常数据也进行处理
        $dealInfo['loantype'] = (intval($deal['repayment']) != 5 && intval($deal['term_unit']) == 1)
                                || intval($deal['repayment']) >10
                                || intval($deal['repayment']) <=0 ? -1 : intval($deal['repayment']);
        //根据还款方式重新计算repay_time,5:到期支付本金收益,单位为天，其他为月，保持现有系统逻辑
        $dealInfo['repay_time'] = intval($deal['repayment'])==5? intval($deal['term']) * self::$unitDays[intval($deal['term_unit'])]:intval($deal['term']) * self::$unitDays[intval($deal['term_unit'])] / 30;
        //进行中的状态为投资工具项目所有，用来区分是工具投资还是标准投资
        $dealInfo['deal_type'] = $dealInfo['deal_status']==DealModel::$DEAL_STATUS['waiting']? 2:0;
        $dealInfo['repay_start_time'] = ThirdDealModel::$DEAL_STATUS['repaying'] == $deal['status'] ? to_timespan($deal['loan_time']) : 0; //起息时间
        $dealInfo['third_deal_Id'] = $deal['deal_id'];
        return $dealInfo;
    }

    //根据标更新时间获取标信息
    public function getDealsByUpdateTime($time)
    {
        $dealInfos = array();
        $time = $time ? $time : time();
        $result = ThirdDealModel::instance()->findAll('update_time>='.$time, true);
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $dealInfos[$key] = $this->formatDeal($value);
            }
        }

        return $dealInfos;
    }

    //couponLog存的标id不是第三方资产的实际标ID，第三方标的标的主键id
    public function getIdByDealId($dealId){
        $dealIds = array();
        $result = ThirdDealModel::instance()->findAll("deal_id='{$dealId}'", true,'id');
        if (!empty($result)) {
            foreach ($result as  $value) {
                $dealIds[] = $value['id'];
            }
        }
        return $dealIds;
    }
}
