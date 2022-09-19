<?php
/**
 * 预约标的服务
 *
 * @date 2017-11-09
 * @author weiwei12@ucfgroup.com
 */

namespace core\service;

use core\dao\UserReservationModel;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\DealTagModel;
use core\dao\ReservationConfModel;
use core\service\ReservationConfService;
use core\service\ReservationMatchService;
use libs\payment\supervision\Supervision;
use libs\utils\Logger;


class ReservationDealService extends BaseService
{
    //随心约浮动起投借款类型列表
    public static $float_loan_deal_types = [DealModel::DEAL_TYPE_EXCLUSIVE, DealModel::DEAL_TYPE_EXCHANGE];

    /**
     * 根据预约期限获取起投金额
     */
    public function getReserveMinLoanMoney($dealType, $deadline, $deadlineUnit, $investRate, $loantype) {
        $reserveMinLoanMoney = 0;
        //专享交易所取预约标的最大的起投
        if (in_array($dealType, self::$float_loan_deal_types)) {
            $dealList = $this->getReservationDealList($dealType, $deadline, $deadlineUnit, $investRate, $loantype);
            foreach ($dealList as $deal) {
                if (bccomp($deal['min_loan_money'], $reserveMinLoanMoney, 2) === 1) {
                    $reserveMinLoanMoney = $deal['min_loan_money'];
                }
            }

            //如果当前没有预约标，获取系统配置默认值
            if (bccomp($reserveMinLoanMoney, 0, 2) === 0) {
                $reserveMinLoanMoney = (int) app_conf('RESERVE_EXCLUSIVE_DEFAULT_MIN_LOAN_MONEY');
            }
        } else {
            //其他类型，默认预约最低金额
            $reserveMinLoanMoney = ReservationConfModel::RESERVE_MIN_AMOUNT_DEFAULT;
        }

        return $reserveMinLoanMoney;
    }

    /**
     * 获取预约标列表
     * $param int $dealType 借款类型
     * $param int $deadline 预约期限
     * $param int $deadlineUnit 预约期限单位
     * @return array
     */
    public function getReservationDealList($dealType = null, $deadline = 0, $deadlineUnit = 0, $investRate = 0, $loantype = 0) {
        $where = '';

        //加上期限查询条件
        $deadlineSqlMap = [
            UserReservationModel::INVEST_DEADLINE_UNIT_DAY => 'loantype = 5',
            UserReservationModel::INVEST_DEADLINE_UNIT_MONTH => 'loantype != 5',
        ];
        if ($deadline > 0 && $deadlineUnit > 0 && isset($deadlineSqlMap[$deadlineUnit])) {
            $where .= ' AND ' . $deadlineSqlMap[$deadlineUnit] . ' AND ' . 'repay_time = ' . intval($deadline);
        }

        //借款类型
        if ($dealType !== null) {
            $where .= ' AND deal_type =' . intval($dealType);
        }

        //还款方式
        if (!empty($loantype)) {
            $where .= ' AND loantype =' . intval($loantype);
        }

        $list = array();
        $dealModel = DealModel::instance();
        $dealTagModel = DealTagModel::instance();
        // 获取deal_status[进行中]、[预约中]的标的列表
        $sql = sprintf('SELECT `id`, `name`, `deal_status`, `type_id`, `advisory_id`, `project_id`, `loantype`, `repay_time`, `borrow_amount`, `load_money`, `deal_type`, `create_time`, `min_loan_money`, `max_loan_money`, `report_status`, `loantype` FROM `%s` WHERE deal_status IN (1,6) AND publish_wait = 0 AND is_delete = 0 AND is_effect = 1 %s ORDER BY `id` ASC', $dealModel->tableName(), $where);
        $dealListDb = $dealModel->findAllBySqlViaSlave($sql, true);
        if (empty($dealListDb)) {
            return $list;
        }

        $dealList = $dealIds = array();
        foreach ($dealListDb as $item) {
            //存管降级状态下，报备标的不匹配
            if (Supervision::isServiceDown() && $item['report_status'] == 1) {
                continue;
            }
            $dealList[$item['id']] = $item;
        }
        if (empty($dealList)) {
            return $list;
        }

        $dealListNew = $dealList;
        //匹配利率
        if (bccomp($investRate, 0, 2) === 1) {
            $dealListNew = [];
            $sqlExt = sprintf('SELECT `deal_id` FROM `firstp2p_deal_ext` WHERE `deal_id` in (%s) AND `income_base_rate` = %s ORDER BY `deal_id` ASC', implode(',', array_keys($dealList)), $investRate);
            $dealExtList = DealExtModel::instance()->findAllBySqlViaSlave($sqlExt, true);
            foreach ($dealExtList as $val) {
                $dealListNew[$val['deal_id']] = $dealList[$val['deal_id']];
            }
            if (empty($dealListNew)) {
                return $list;
            }
        }


        // 根据TAG名称数组，批量获取TAGLIST
        $reservationMatch = new ReservationMatchService();
        $tagList = $reservationMatch->getTagListByReserveTag();
        if (empty($tagList)) {
            return $list;
        }

        // 获取预约标对应TAG的标的信息
        $sql = sprintf('SELECT deal_id FROM `%s` WHERE deal_id IN (%s) AND tag_id IN (%s) GROUP BY deal_id', $dealTagModel->tableName(), join(',', array_keys($dealListNew)), join(',', array_keys($tagList)));
        $dealTagList = $dealTagModel->findAllBySqlViaSlave($sql, true);
        if (empty($dealTagList)) {
            return $list;
        }

        // 整理标的列表
        foreach ($dealTagList as $dt) {
            $dealDataTmp = !empty($dealListNew[$dt['deal_id']]) ? $dealListNew[$dt['deal_id']] : array();
            $list[] = $dealDataTmp;
        }
        return $list;
    }

    /**
     * 根据id获取预约标的
     */
    public function getReservationDealById($dealId, $isSlave = true)
    {
        $result = [];
        $dealModel = DealModel::instance();
        $dealTagModel = DealTagModel::instance();
        $sql = sprintf('SELECT `id`, `name`, `deal_status`, `type_id`, `advisory_id`, `project_id`, `loantype`, `repay_time`, `borrow_amount`, `load_money`, `deal_type`, `create_time`, `min_loan_money`, `max_loan_money`, `report_status` FROM `%s` WHERE id = %d AND deal_status IN (1,6) AND publish_wait = 0 AND is_delete = 0 AND is_effect = 1 ORDER BY `id` ASC', $dealModel->tableName(), $dealId);
        $dealInfo = $dealModel->findBySql($sql, [], $isSlave);
        if (empty($dealInfo)) {
            return $result;
        }
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealId, $isSlave);
        $dealInfo['income_base_rate'] = $dealExt['income_base_rate'];

        // 根据TAG名称数组，批量获取TAGLIST
        $reservationMatch = new ReservationMatchService();
        $tagList = $reservationMatch->getTagListByReserveTag();
        if (empty($tagList)) {
            return $result;
        }

        // 获取预约标对应TAG的标的信息
        $sql = sprintf('SELECT deal_id,GROUP_CONCAT(tag_id) AS tag_id_group FROM `%s` WHERE deal_id = %d AND tag_id IN (%s) GROUP BY deal_id', $dealTagModel->tableName(), $dealId, join(',', array_keys($tagList)));
        $dealTag = $dealTagModel->findBySql($sql, [], $isSlave);
        if (empty($dealTag)) {
            return $result;
        }
        $result = $dealInfo->_row;

        return $result;
    }

    /**
     * 更新预约标为等待处理中
     * @param array $deal_ids
     * @return bool
     */
    public function updateReserveDealWaiting($dealId) {
        $dealModel = DealModel::instance();
        $deal = $dealModel->find($dealId);
        if ($deal['deal_status'] != DealModel::$DEAL_STATUS['reserving']) {
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'dealId: ' . $dealId)));
        //更新为等待确认无效
        $data = array(
            'deal_status' => DealModel::$DEAL_STATUS['waiting'],
            'is_effect'   => 0,
            'update_time' => get_gmtime(),
        );
        $conditon = " `id` = " . intval($dealId) . ' and `is_effect`=1 AND `is_delete` = 0 and `publish_wait` = 0 and `deal_status` = ' . DealModel::$DEAL_STATUS['reserving'];

        return $dealModel->updateBy($data, $conditon);
    }


}
