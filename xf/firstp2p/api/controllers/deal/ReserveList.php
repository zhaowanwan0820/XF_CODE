<?php
/**
 * 短期标预约-预约列表-分页
 *
 * @date 2016-11-16
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\dao\UserReservationModel;
use core\dao\ReservationConfModel;
use core\service\UserReservationService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class ReserveList extends ReserveBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'page' => array('filter' => 'int'),
            'count' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $appLoginUrl);
            return false;
        }

        // 获取用户所有的预约列表-分页
        $list = array('list'=>array(), 'count'=>0);
        $data = $this->form->data;
        $page = max(1, intval($data['page']));
        $count = isset($data['count']) ? max(1, intval($data['count'])) : 20;
        $productType = UserReservationModel::PRODUCT_TYPE_EXCLUSIVE;
        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($productType, $userInfo['id']));
        if (empty($dealTypeList)) {
            $this->setErr('ERR_MANUAL_REASON', '服务暂不可用，请稍后再试！');
            return false;
        }

        $userReservationService = new UserReservationService();
        $userAllReserveList = $userReservationService->getUserReserveListByPage($userInfo['id'], -1, $page, $count, $dealTypeList);
        if (!empty($userAllReserveList)) {
            $notExpiredTmp = $ExpiredTmp = array();
            // 用户身份标识
            $userClientKey = $data['userClientKey'];
            $current_time = time();
            foreach ($userAllReserveList as $item) {
                // 投资期限的单位
                if (!empty(UserReservationModel::$investDeadLineUnitConfig[$item['invest_deadline_unit']])) {
                    $investDeadLineUnit = $item['invest_deadline_unit'] == UserReservationModel::INVEST_DEADLINE_UNIT_MONTH ? '个' . UserReservationModel::$investDeadLineUnitConfig[$item['invest_deadline_unit']] : UserReservationModel::$investDeadLineUnitConfig[$item['invest_deadline_unit']];
                }else{
                    $investDeadLineUnit = UserReservationModel::$investDeadLineUnitConfig[UserReservationModel::INVEST_DEADLINE_UNIT_DAY];
                }
                $dealType = isset($item['deal_type']) ? $item['deal_type'] : 0;
                // 预期年化
                $investRate = bccomp($item['invest_rate'], '0.00', 2) > 0 ? '/' . $item['invest_rate'] . '%' : '';
                // 预约状态
                $reserveStatus = ($item['end_time'] - $current_time < 0 && $item['reserve_status'] == UserReservationModel::RESERVE_STATUS_ING) ? UserReservationModel::RESERVE_STATUS_END : $item['reserve_status'];
                // 扩展信息
                $extraInfo = !empty($item['extra_info']) ? json_decode($item['extra_info'], true) : [];

                // 券描述
                // 350元返现券
                $discountDesc = '';
                if ($item['discount_id'] > 0  && !empty($extraInfo)) {
                    if ($extraInfo['discountType'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
                        $discountDesc .= $extraInfo['discountGoodsPrice'] . '元';
                    } else if ($extraInfo['discountType'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
                        $discountDesc .= $extraInfo['discountGoodsPrice'] . '%';
                    }
                    $discountDesc .= CouponGroupEnum::$DISCOUNT_TYPES[$extraInfo['discountType']];
                }

                // 预约倒计时的秒数
                $expireAt = max(0, $item['end_time'] - $current_time);
                // 预约中
                if ($reserveStatus == UserReservationModel::RESERVE_STATUS_ING) {
                    $notExpiredTmp[] = array(
                        'id' => $item['id'],
                        'reserve_status' => $reserveStatus, // 预约状态
                        'actual_amount' => number_format(bcdiv($item['invest_amount'], 100, 2), 2), // 投资总金额，单位元
                        'actual_count' => $item['invest_count'], // 投资总笔数
                        'reserve_amount' => number_format(bcdiv($item['reserve_amount'], 100, 2), 2), // 预约金额，单位元
                        'invest_deadline_rate' => $item['invest_deadline'] . $investDeadLineUnit . $investRate, // 投资期限/投资预期年化
                        'start_time' => date('Y-m-d H:i:s', $item['start_time']), // 预约时间
                        'remain_time' => $this->_sec2time($expireAt, $item['expire_unit']), // 预约倒计时的秒数
                        'discount_id' => $item['discount_id'],
                        'discount_desc' => $discountDesc,
                        'discount_status' => $item['discount_status'],
                        'discount_status_desc' => UserReservationModel::$discountStatusMap[$item['discount_status']],
                        'deal_type' => $dealType,
                    );
                }else{
                    $ExpiredTmp[$item['id']] = array(
                        'id' => $item['id'],
                        'reserve_status' => $reserveStatus, // 预约状态
                        'actual_amount' => number_format(bcdiv($item['invest_amount'], 100, 2), 2), // 投资总金额，单位元
                        'actual_count' => $item['invest_count'], // 投资总笔数
                        'reserve_amount' => number_format(bcdiv($item['reserve_amount'], 100, 2), 2), // 预约金额，单位元
                        'invest_deadline_rate' => $item['invest_deadline'] . $investDeadLineUnit . $investRate, // 投资期限/投资预期年化
                        'start_time' => date('Y-m-d H:i:s', $item['start_time']), // 预约时间
                        'remain_time' => $this->_sec2time($expireAt, $item['expire_unit']), // 预约倒计时的秒数
                        'discount_id' => $item['discount_id'],
                        'discount_desc' => $discountDesc,
                        'discount_status' => $item['discount_status'],
                        'discount_status_desc' => UserReservationModel::$discountStatusMap[$item['discount_status']],
                        'deal_type' => $dealType,
                    );
                }
            }
            if (!empty($ExpiredTmp)) {
                krsort($ExpiredTmp);
            }
            $list['list'] = array_merge($notExpiredTmp, $ExpiredTmp);
            $list['count'] = count($list['list']);
            $list['product_type'] = $productType;
        }
        $this->json_data = $list;
        return true;
    }

    private function _sec2time($seconds, $expireUnit = 1) {
        $format_time = '';
        $seconds = (int)$seconds;
        $time = explode(' ', gmstrftime('%j %H %M %S', $seconds));
        $format_time .= ($time[0]-1) > 0 ? intval($time[0]-1) . '天' : '';
        $format_time .= intval($time[1]) ? intval($time[1]) . '小时' : '';
        $format_time .= intval($time[2]) ? intval($time[2]) . '分钟' : '';
        if (empty($format_time)) {
            $format_time = '1分钟';
        }
        return $format_time;
    }
}
