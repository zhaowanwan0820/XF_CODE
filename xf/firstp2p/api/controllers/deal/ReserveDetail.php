<?php
/**
 * ReserveDetail.php
 *
 * @author gengkuan <gengkuan@ucfgroup.com>
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\service\ReservationEntraService;
use core\dao\ReservationConfModel;
use core\dao\UserReservationModel;
use libs\utils\Logger;

class ReserveDetail extends ReserveBaseAction{

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'line_unit' => array('filter' => 'required', 'message' => 'line_unit is required'),
            'deal_type' => array('filter' => 'int'),
            'loantype' => array('filter' => 'int'),
            'rate' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }
        $data = $this->form->data;
        // 根据token获取用户信息
        $userInfo = $this->getUserByTokenForH5($data['token']);
        // 用户ID
        $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;

        $productType = UserReservationModel::PRODUCT_TYPE_EXCLUSIVE;
        $dealType = !empty($data['deal_type']) ? (int) $data['deal_type'] : 0;

        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($productType, $userId));
        $dealTypeList = array_intersect($dealTypeList, [$dealType]);
        if (empty($dealTypeList)) {
            $this->setErr('ERR_MANUAL_REASON', '服务暂不可用，请稍后再试！');
            return false;
        }

        if (false === strpos($data['line_unit'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
        }
        list($investLine, $investUnit) = explode('_', $data['line_unit']);
        $loantype = isset($data['loantype']) ? (int) $data['loantype'] : 0;
        $investRate = isset($data['rate']) ? $data['rate'] : 0;

        // 获取后台配置预约入口
        $entraService = new ReservationEntraService();
        $entraDetail = $entraService->getReserveEntraDetail($investLine, $investUnit, $dealType, $investRate, $loantype);
        if (empty($entraDetail)) {
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约入口');
        }

        $isReserve = 0;
        $siteId = \libs\utils\Site::getId();
        if (!empty($userInfo)) {
            // 检查用户是否还可以再预约
            $userReservationService = new UserReservationService();
            $isReserve = $userReservationService->checkUserIsReserve($userId);
            $userClientKey = parent::genUserClientKey($data['token'], $userId);
            // 立即预约按钮跳转地址
            $this->tpl->assign('reserve_button', sprintf("/deal/reserve?userClientKey=%s&investLine=%s&investUnit=%s&site_id=%s&deal_type=%s&loantype=%s&rate=%s", $userClientKey,$entraDetail['investLine'], $entraDetail['investUnit'], $siteId, $entraDetail['dealType'], $entraDetail['loantype'], $entraDetail['investRate']));
            // 我的预约记录按钮跳转地址
            $this->tpl->assign('reserve_list_button', '/deal/reserveMy?userClientKey=' . $userClientKey . '&site_id=' . $siteId);
        } else {
            // 登录token失效，跳到App登录页
            $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
            // 立即预约按钮跳转地址
            $this->tpl->assign('reserve_button', $appLoginUrl);
            // 我的预约记录按钮跳转地址
            $this->tpl->assign('reserve_list_button', $appLoginUrl);
        }

        $this->tpl->assign('product_type', $productType);

        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveDetail', APP, $data["line_unit"], $data['token'])));
        $this->tpl->assign('is_login', (!empty($userInfo['id']) ? 1 : 0));
        $this->tpl->assign('is_reserve', intval($isReserve));
        $this->tpl->assign('is_identify', ((!empty($userInfo['idcardpassed']) && $userInfo['idcardpassed'] == 1) ? 1 : 0)); // 是否已实名认证
        $this->tpl->assign('min_amount', number_format($entraDetail['minValue'],2));//起投金额
        $this->tpl->assign('invest_line', $entraDetail['investLine']);//投资期限
        $this->tpl->assign('invest_unit', $entraDetail['investUnit']);//时间单位 个月or天
        $this->tpl->assign('rate', $entraDetail['rate']);//预期年化
        $this->tpl->assign('tagBefore', $entraDetail['tagBefore']);//
        $this->tpl->assign('tagAfter', $entraDetail['tagAfter']);
        $this->tpl->assign('amount', $entraDetail['amount']);//已预约投资金额
        $this->tpl->assign('countDisplay', $entraDetail['countDisplay']);//是否启用 显示预约人次
        $this->tpl->assign('count', $entraDetail['count']);//已投统计
        $this->tpl->assign('minAmount', $entraDetail['minAmount']);//起投金额
        $this->tpl->assign('amountCount', $entraDetail['amountCount']);//预约金额
        $this->tpl->assign('userCount', $entraDetail['userCount']);//预约人次
        $this->tpl->assign('loantype', $entraDetail['loantype']);//还款方式 枚举值
        $this->tpl->assign('loantypeName', $entraDetail['loantypeName']);//还款方式
        $this->tpl->assign('userCount', $entraDetail['userCount']);//预约人次
        $this->tpl->assign('deal_type', $entraDetail['dealType']);//deal_type
        $this->tpl->assign('description', (!empty($entraDetail['description']) ? htmlspecialchars_decode($entraDetail['description']) : ''));
        $this->tpl->assign('investInterest', $entraDetail['investInterest']);//每万元投资利息
        $this->tpl->assign('is_firstp2p', $this->is_firstp2p);
        return  true;

    }
}
