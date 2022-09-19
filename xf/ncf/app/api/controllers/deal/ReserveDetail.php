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
use core\service\reserve\ReservationConfService;
use core\service\reserve\UserReservationService;
use core\service\reserve\ReservationEntraService;
use core\service\account\AccountService;
use core\dao\reserve\ReservationConfModel;
use core\dao\reserve\ReservationCardModel;
use core\dao\reserve\UserReservationModel;
use libs\utils\Logger;
use core\enum\ReserveEnum;
use core\enum\ReserveConfEnum;
use core\enum\ReserveCardEnum;
use core\enum\UserAccountEnum;
use libs\utils\Aes;

class ReserveDetail extends ReserveBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/deal/reserveDetail';

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

        $dealType = !empty($data['deal_type']) ? (int) $data['deal_type'] : 0;

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

        $userReservationService = new UserReservationService();
        $isReserve = 0;
        $siteId = \libs\utils\Site::getId();
        if (!empty($userInfo)) {
            //获取投资账户
            $accountId = AccountService::getUserAccountId($userInfo['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
            // 检查用户是否还可以再预约
            $isReserve = $userReservationService->checkUserIsReserve($accountId);
            $userClientKey = parent::genUserClientKey($data['token'], $userId);
            // 立即预约按钮跳转地址
            $result['reserve_button'] = sprintf("/deal/reserve?userClientKey=%s&investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $userClientKey,$entraDetail['investLine'], $entraDetail['investUnit'], $entraDetail['dealType'], $entraDetail['loantype'], $entraDetail['investRate']);
            // 我的预约记录按钮跳转地址
            $result['reserve_list_button'] = '/deal/reserveMy?userClientKey=' . $userClientKey;
        }else{
            // 登录token失效，跳到App登录页
            $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
            // 立即预约按钮跳转地址
            $result['reserve_button'] = $appLoginUrl;
            // 我的预约记录按钮跳转地址
            $result['reserve_list_button'] = $appLoginUrl;
        }

        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveDetail', APP, $data["line_unit"], $data['token'])));
        $result['is_login'] = !empty($userInfo['id']) ? 1 : 0;
        $result['is_reserve'] = intval($isReserve);
        $result['is_identify'] = (!empty($userInfo['idcardpassed']) && $userInfo['idcardpassed'] == 1) ? 1 : 0; // 是否已实名认证
        $result['min_amount'] = number_format($entraDetail['minValue'],2);//起投金额
        $result['invest_line'] = $entraDetail['investLine'];//投资期限
        $result['invest_unit'] = $entraDetail['investUnit'];//时间单位 个月or天
        $result['rate'] = $entraDetail['rate'];//预期年化
        $result['tagBefore'] = $entraDetail['tagBefore'];//
        $result['tagAfter'] = $entraDetail['tagAfter'];
        $result['amount'] = $entraDetail['amount'];//已预约投资金额
        $result['countDisplay'] = $entraDetail['countDisplay'];//是否启用 显示预约人次
        $result['count'] = $entraDetail['count'];//已投统计
        $result['minAmount'] = $entraDetail['minAmount'];//起投金额
        $result['amountCount'] = $entraDetail['amountCount'];//预约金额
        $result['userCount'] = $entraDetail['userCount'];//预约人次
        $result['loantype'] = $entraDetail['loantype'];//还款方式
        $result['loantypeName'] = $entraDetail['loantypeName'];//还款方式名称
        $result['deal_type'] = $entraDetail['dealType'];//deal_type
        $result['description'] = !empty($entraDetail['description']) ? htmlspecialchars_decode($entraDetail['description']) : '';
        $result['investInterest'] = $entraDetail['investInterest'];//每万元投资利息
        $result['is_firstp2p'] = $this->is_firstp2p;
        $this->json_data = $result;

    }
}
