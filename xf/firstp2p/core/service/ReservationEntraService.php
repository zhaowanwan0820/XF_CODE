<?php
/**
 * 预约入口服务
 *
 * @date 2019-02-25
 * @author weiwei12@ucfgroup.com
 */

namespace core\service;
use core\dao\ReservationEntraModel;
use core\dao\UserReservationModel;
use core\service\UserReservationService;

class ReservationEntraService extends BaseService
{
    /**
     * 根据id获取预约入口
     * @param int $id
     * @return mix
     */
    public function getReserveEntraById($id)
    {
        return ReservationEntraModel::instance()->getReserveEntraById($id);
    }

    /**
     * 批量
     * 根据ids获取预约入口
     * @param array $ids
     * @return mix
     */
    public function getReserveEntraByIds($ids)
    {
        $result = [];
        $entraList = ReservationEntraModel::instance()->getReserveEntraByIds($ids);
        foreach ($entraList as $val) {
            $result[$val['id']] = $val;
        }
        return $result;
    }


    /**
     * 根据投资期限获取预约入口
     * @return mix
     */
    public function getReserveEntra($investLine, $investUnit, $dealType, $investRate, $loantype, $status = ReservationEntraModel::STATUS_VALID)
    {
        $investRate = sprintf("%.2f", $investRate);
        return ReservationEntraModel::instance()->getReserveEntra($investLine, $investUnit, $dealType, $investRate, $loantype, $status);
    }

    /**
     * 获取预约入口明细
     * @return mix
     */
    public function getReserveEntraDetail($investLine, $investUnit, $dealType, $investRate, $loantype, $status = ReservationEntraModel::STATUS_VALID)
    {
        $entra = ReservationEntraModel::instance()->getReserveEntra($investLine, $investUnit, $dealType, $investRate, $loantype, $status);
        $detail = [];
        if (empty($entra)) {
            return $detail;
        }
        $detail['investLine'] = intval($entra['invest_line']);
        $detail['investUnit'] = intval($entra['invest_unit']);
        $detail['buttonName'] = '去预约';
        $detail['tagBefore'] = trim($entra['label_before']);
        $detail['tagAfter'] = trim($entra['label_after']);
        $detail['dealType'] = intval($entra['deal_type']);
        $detail['description'] = trim($entra['description']);
        $userReservationObj = new UserReservationService();
        $resStat = $userReservationObj->getReserveStats($investLine, $investUnit, $dealType, $investRate, $loantype);
        $investInterest = bcdiv($entra['invest_interest'], 100, 2);
        $minAmount = bcdiv($entra['min_amount'], 100, 2);
        if (intval($entra['display_people']) && $resStat['reserveUserCountToday']) {
            $detail['countDisplay'] = 1;
            $detail['count'] = $this->numFormat($resStat['reserveUserCountToday'], true).'人次';
        } else {
            $detail['countDisplay'] = 0;
            $detail['count'] = $this->numFormat($minAmount)."元起";
        }
        $detail['amount'] = intval($entra['display_money']) && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';
        $detail['rate'] = number_format($entra['invest_rate'], 2);
        $detail['investRate'] = $entra['invest_rate'];
        $detail['minAmount'] = $this->numFormat($minAmount);
        $detail['minValue'] = bcdiv($entra['min_amount'], 100, 2);
        $detail['maxValue'] = bcdiv($entra['max_amount'], 100, 2);
        $detail['amountCount'] = $entra['display_money'] && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';//预约金额
        $detail['userCount'] = $entra['display_people'] && $resStat['reserveUserCountToday'] ? $this->numFormat($resStat['reserveUserCountToday'], true).'人次' : ''; //预约人数
        $detail['loantype'] = (int) $entra['loantype'];
        $detail['loantypeName'] = $this->getLoantypeName($entra['loantype']);
        $detail['investInterest'] = $this->numFormat($investInterest)."元"; //每万元投资利息
        return $detail;
    }

    /**
     * 获取全部预约入口列表
     * @param int $status 入口状态
     * @return array
     */
    public function getReserveEntraList($status = ReservationEntraModel::STATUS_VALID)
    {
        return ReservationEntraModel::instance()->getReserveEntraList($status);
    }

    /**
     * 获取预约入口明细列表
     * @param int $status 入口状态
     */
    public function getReserveEntraDetailList($status = ReservationEntraModel::STATUS_VALID, $limit = 10, $offset = 0, $userInfo = []) {
        $entraList = ReservationEntraModel::instance()->getReserveEntraList($status, $limit, $offset);
        $detailList = [];
        if (empty($entraList)) {
            return $detailList;
        }
        $userGroupId = isset($userInfo['group_id']) ? $userInfo['group_id'] : 0;
        $userReservationObj = new UserReservationService();
        foreach ($entraList as $entra) {
            //没配置期限不显示卡片
            if (!empty($entra['visiable_group_ids'])) {
                $groupIds = explode(',', $entra['visiable_group_ids']);
                if (!in_array($userGroupId, $groupIds)) {
                    continue;
                }
            }
            $item = array();
            $item['investLine'] = strval($entra['invest_line']);
            $item['unitType'] = strval($entra['invest_unit']);

            $item['investUnit'] = UserReservationModel::$investDeadLineUnitConfig[intval($entra['invest_unit'])];
            if( 2 ==$entra['invest_unit']) {
                $item['investUnit'] = '个月';
            }
            $item['buttonName'] = '去预约';
            $item['tagBefore'] = trim($entra['label_before']);
            $item['tagAfter'] = trim($entra['label_after']);
            $item['displayMoney'] = intval($entra['display_money']);
            $item['dealType'] = intval($entra['deal_type']);
            $resStat = $userReservationObj->getReserveStats($entra['invest_line'], $entra['invest_unit'], $entra['deal_type'], $entra['invest_rate'],  $entra['loantype']);
            $investInterest = bcdiv($entra['invest_interest'], 100, 2);
            $minAmount = bcdiv($entra['min_amount'], 100, 2);
            if (intval($entra['display_people']) && $resStat['reserveUserCountToday']) {
                $item['countDisplay'] = 1;
                $item['count'] = $this->numFormat($resStat['reserveUserCountToday'], true).'人次';
            } else {
                $item['countDisplay'] = 0;
                $item['count'] = $this->numFormat($minAmount)."元起";
            }
            $item['amount'] = intval($entra['display_money']) && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';
            $item['rate'] = number_format($entra['invest_rate'], 2).'%';
            $item['investRate'] = $entra['invest_rate'];
            $item['minAmount'] = $this->numFormat($minAmount);
            $item['minValue'] = bcdiv($entra['min_amount'], 100, 2);
            $item['maxValue'] = bcdiv($entra['max_amount'], 100, 2);
            $item['amountCount'] = $entra['display_money'] && $resStat['reserveSumInvestMoney'] > 0 ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';//预约金额
            $item['userCount'] = $entra['display_people'] && $resStat['reserveUserCountToday'] > 0 ? $this->numFormat($resStat['reserveUserCountToday'], true).'人次' : ''; //预约人数
            $item['loantype'] = (int) $entra['loantype'];
            $item['loantypeName'] = $this->getLoantypeName($entra['loantype']);
            $item['investInterest'] = $this->numFormat($investInterest)."元"; //每万元投资利息
            $detailList[] = $item;
        }
        return array('list' => $detailList);

    }

    private function numFormat($number, $isRound = false) {
        if (empty($number)) {
            return '';
        }
        if (intval($number) >= 10000) {
            $number = number_format($number/10000, 2).'万';
        } else {
            $number = $isRound ? number_format($number) : number_format($number, 2);
        }
        return $number;
    }

    /**
     * 保存预约入口
     * @param array $params 参数数组
     * @return array
     */
    public function saveReserveEntra($params = array())
    {
        if (empty($params) || !isset($params['dealType']) || !isset($params['status']) || !isset($params['loantype']) || empty($params['investRate'])
            || empty($params['investLine']) || empty($params['investUnit']) || empty($params['productGradeConf'])
            || empty($params['minAmount']) || empty($params['rateFactor']) || empty($params['description'])) {
            return array('errorCode'=>'01', 'errorMsg'=>'缺少必填参数');
        }
        $id = isset($params['id']) ? intval($params['id']) : 0;

        // 查询入口
        $entra = ReservationEntraModel::instance()->getReserveEntra($params['investLine'], $params['investUnit'], $params['dealType'], $params['investRate'], $params['loantype'], -1, $id);
        if (!empty($entra)) {
            return array('errorCode'=>'02', 'errorMsg'=>'预约入口已存在');
        }

        // 根据自增ID，获取预约卡片信息
        if ($id === 0) {
            $ret = ReservationEntraModel::instance()->createReserveEntra($params);
        }else{
            $ret = ReservationEntraModel::instance()->updateReserveEntra($params);
        }
        return array('errorCode'=>($ret ? '00' : '03'), 'errorMsg'=>($ret ? 'SUCCESS' : '保存预约入口失败'));
    }

    /**
     * 获取还款类型名称
     */
    public function getLoantypeName($loantype) {
        return !empty($GLOBALS['dict']['LOAN_TYPE'][$loantype]) ? $GLOBALS['dict']['LOAN_TYPE'][$loantype] : '';
    }

    /**
     * 获取加息返利年化折算系数映射表
     * @return array
     */
    public function getRebateRateMap() {
        return [
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'] => app_conf('COUPON_RABATE_RATIO_FACTOR_ANJI'),
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'] => app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUE'),
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'] => app_conf('COUPON_RABATE_RATIO_FACTOR_XFFQ'),
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH'] => app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUEBJ'),
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH'] => app_conf('COUPON_RABATE_RATIO_FACTOR_ANJIBJ'),
        ];
    }

}
