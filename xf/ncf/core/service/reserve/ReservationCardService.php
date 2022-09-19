<?php
/**
 * 预约标卡片配置表
 * 废弃
 *
 * @date 2017-01-03
 * @author guofeng@ucfgroup.com
 */

namespace core\service\reserve;

use core\service\BaseService;
use core\dao\reserve\ReservationCardModel;
use core\dao\reserve\UserReservationModel;
use core\service\reserve\ReservationConfService;
use core\service\reserve\UserReservationService;
use core\enum\ReserveConfEnum;
use core\enum\ReserveEnum;
use core\enum\ReserveCardEnum;

class ReservationCardService extends BaseService
{
    /**
     * 根据自增ID，获取预约卡片信息
     * @param int $id
     * @return \libs\db\model
     */
    public function getReserveInfoById($id)
    {
        return ReservationCardModel::instance()->getReserveCardById($id);
    }

    /**
     * 获取预约卡片列表
     * @return \libs\db\model
     */
    public function getReserveCardList($limit = 10, $status = ReserveCardEnum::STATUS_VALID,$offset=0, $dealTypeList = [], $userInfo = [])
    {
        $cards = ReservationCardModel::instance()->getReserveCardList($status, intval($limit), $offset, $dealTypeList);
        $data = array();
        if ($cards) {
            $reserveConf = new ReservationConfService();
            $userReservationObj = new UserReservationService();
            $config = $reserveConf->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
            $userGroupId = isset($userInfo['group_id']) ? $userInfo['group_id'] : 0;
            if (isset($config['invest_conf'])) {
                $investConf = array();
                foreach ($config['invest_conf'] as $item) {
                    // 组白名单可见
                    if (!empty($item['visiableGroupIds'])) {
                        $groupIds = explode(',', $item['visiableGroupIds']);
                        if (!in_array($userGroupId, $groupIds)) {
                            continue;
                        }
                    }
                    $investConf[$item['deal_type'].'_'.$item['deadline'].'_'.$item['deadline_unit']] = $item;
                }
            }
            foreach ($cards as $card) {
                //没配置期限不显示卡片
                if (!isset($investConf[$card['deal_type'].'_'.$card['invest_line'].'_'.$card['invest_unit']])) {
                    continue;
                }
                $investConfItem = $investConf[$card['deal_type'].'_'.$card['invest_line'].'_'.$card['invest_unit']];
                $item = array();
                $item['investLine'] = strval($card['invest_line']);
                $item['unitType'] = strval($card['invest_unit']);

                $item['investUnit'] = ReserveEnum::$investDeadLineUnitConfig[intval($card['invest_unit'])];
                if( 2 ==$card['invest_unit']) {
                    $item['investUnit'] = '个月';
                }
                $item['buttonName'] = trim($card['button_name']);
                $item['tagBefore'] = trim($card['label_before']);
                $item['tagAfter'] = trim($card['label_after']);
                $item['displayMoney'] = intval($card['display_money']);
                $item['dealType'] = intval($card['deal_type']);
                $resStat = $userReservationObj->getReservationStatisticsForCard($card['invest_line'], $card['invest_unit']);
                $minAmount = $reserveConf->getReserveMinAmountByDealType($item['dealType']);
                if (intval($card['display_people']) && $resStat['reserveUserCountToday']) {
                    $item['countDisplay'] = 1;
                    $item['count'] = $this->numFormat($resStat['reserveUserCountToday'], true).'人次';
                } else {
                    $item['countDisplay'] = 0;
                    $item['count'] = $this->numFormat($minAmount)."元起";
                }
                $item['amount'] = intval($card['display_money']) && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';
                $item['rate'] = number_format($investConfItem['rate'], 2).'%';
                $item['minAmount'] = $this->numFormat($minAmount);
                $item['amountCount'] = $card['display_money'] && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';//预约金额
                $item['userCount'] = $card['display_people'] && $resStat['reserveUserCountToday'] ? $this->numFormat($resStat['reserveUserCountToday'], true).'人次' : ''; //预约人数
                $data[] = $item;
            }
        }
        return array('list' => $data);
    }
    /**
     * 获取有效预约卡片的数量
     * @return \libs\db\model
     */
    public function getReserveCardCount($status = ReserveCardEnum::STATUS_VALID){
        return ReservationCardModel::instance()->getReserveCardCount($status);
    }
    /**
     * 获取预约卡片列表
     * @return \libs\db\model
     */
    public function getReserveCardListByAdmin( $status = ReserveCardEnum::STATUS_VALID)
    {
        return ReservationCardModel::instance()->getReserveCardList($status);
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
     * 获取预约卡片明细
     * @return \libs\db\model
     */
    public function getReserveCardDetail($line_unit, $status = ReserveCardEnum::STATUS_VALID, $dealType = null)
    {
        $line =explode('_',$line_unit);
        $cards = ReservationCardModel::instance()->getReserveCardByInvestLine($line[0],$line[1], $dealType);
        $data = array();
        if ($cards) {
            $reserveConf = new ReservationConfService();
            $userReservationObj = new UserReservationService();
            $config = $reserveConf->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
            if (isset($config['invest_conf'])) {
                $investConf = array();
                foreach ($config['invest_conf'] as $v) {
                    $investConf[$v['deal_type'].'_'.$v['deadline'].'_'.$v['deadline_unit']] = $v;
                }
            }
            $card = $cards[0];
                $item = array();
                $item['investLine'] = strval($card['invest_line']);
                $item['unitType'] = strval($card['invest_unit']);
                $item['investUnit'] = intval($card['invest_unit']);
                $item['buttonName'] = trim($card['button_name']);
                $item['tagBefore'] = trim($card['label_before']);
                $item['tagAfter'] = trim($card['label_after']);
                $item['dealType'] = intval($card['deal_type']);
                $item['description'] = trim($card['description']);
                $resStat = $userReservationObj->getReservationStatisticsForCard($card['invest_line'], $card['invest_unit']);
                $minAmount = $reserveConf->getReserveMinAmountByDealType($item['dealType']);
                if (intval($card['display_people']) && $resStat['reserveUserCountToday']) {
                    $item['countDisplay'] = 1;
                    $item['count'] = $this->numFormat($resStat['reserveUserCountToday'], true).'人次';
                } else {
                    $item['countDisplay'] = 0;
                    $item['count'] = $this->numFormat($minAmount)."元起";
                }
                $item['amount'] = intval($card['display_money']) && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';
                $item['rate'] = number_format($investConf[$card['deal_type'].'_'.$card['invest_line'].'_'.$card['invest_unit']]['rate'], 2);
                $item['minAmount'] = $this->numFormat($minAmount);
                $item['amountCount'] = $card['display_money'] && $resStat['reserveSumInvestMoney'] ? $this->numFormat($resStat['reserveSumInvestMoney']).'元' : '';//预约金额
                $item['userCount'] = $card['display_people'] && $resStat['reserveUserCountToday'] ? $this->numFormat($resStat['reserveUserCountToday'], true).'人次' : ''; //预约人数

            }
        return $item;
    }

    /**
     * 编辑预约卡片
     * @param array $params 参数数组
     * @return array
     */
    public function editReserveCard($params = array())
    {
        if(empty($params) || empty($params['investLine']) || empty($params['buttonName'])) {
            return array('errorCode'=>'01', 'errorMsg'=>'investLine or buttonName is empty');
        }
        $id = isset($params['id']) ? intval($params['id']) : 0;
            // 按投资期限查询
        $sampleCardInfo = ReservationCardModel::instance()->getReserveCardByInvestLine($params['investLine'], $params['investUnit'],$params['dealType'],$id);
        if (!empty($sampleCardInfo)) {
                return array('errorCode'=>'02', 'errorMsg'=>'已有相同期限创建了预约卡片，请检查');
            }

        // 根据自增ID，获取预约卡片信息
        $reserveCardInfo = $this->getReserveInfoById($id);
        if (empty($reserveCardInfo) || $id <= 0) {
            $ret = ReservationCardModel::instance()->createReserveCard($params['investLine'], $params['investUnit'], $params['buttonName'], $params['labelBefore'], $params['labelAfter'], $params['displayPeople'], $params['displayMoney'], $params['status'], $params['dealType'],$params['description']);
        }else{
            $ret = ReservationCardModel::instance()->updateReserveCard($id, $params['investLine'], $params['investUnit'], $params['buttonName'], $params['labelBefore'], $params['labelAfter'], $params['displayPeople'], $params['displayMoney'], $params['status'], $params['dealType'],$params['description']);
        }
        return array('errorCode'=>($ret ? '00' : '03'), 'errorMsg'=>($ret ? 'SUCCESS' : '编辑预约卡片失败'));
    }

}
