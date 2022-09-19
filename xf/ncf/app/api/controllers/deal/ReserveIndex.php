<?php
/**
 * 短期标预约-预约首页
 * 废弃
 * @date 2016-11-16
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\reserve\ReservationConfService;
use core\service\reserve\UserReservationService;
use core\service\account\AccountService;
use core\enum\ReserveEnum;
use core\enum\ReserveConfEnum;
use core\enum\UserAccountEnum;
use libs\utils\Logger;

class ReserveIndex extends ReserveBaseAction {

    protected $needAuth = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
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

        // 获取后台配置的预约标通知
        $reservationConfService = new ReservationConfService();
        $reserveNotice = $reservationConfService->getReserveInfoByType(ReserveConfEnum::TYPE_NOTICE_P2P);

        // 检查用户是否符合预约条件(立即预约|预约已经约满)
        // 根据token获取用户信息
        $userInfo = $this->getUserByTokenForH5($data['token']);
        // 用户ID
        $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;

        $isReserve = 0;
        $siteId = \libs\utils\Site::getId();
        $userClientKey = '';
        if (!empty($userInfo)) {

            //获取投资账户
            $accountId = AccountService::getUserAccountId($userInfo['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
            $userReservationService = new UserReservationService();
            // 检查用户是否还可以再预约
            $isReserve = $userReservationService->checkUserIsReserve($accountId);

            $userClientKey = parent::genUserClientKey($data['token'], $userId);
            // 立即预约按钮跳转地址
            $result['reserve_button'] = '/deal/reserve?userClientKey=' . $userClientKey;
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
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveIndex', APP, $userId, $data['token'])));
        $result['is_login'] = !empty($userInfo['id']) ? 1 : 0;
        $result['is_reserve'] = intval($isReserve);
        $result['is_identify'] = (!empty($userInfo['idcardpassed']) && $userInfo['idcardpassed'] == 1) ? 1 : 0; // 是否已实名认证
        $result['banner_url'] = !empty($reserveNotice['banner_uri']) ? $reserveNotice['banner_uri'] : '';
        $detail_herf = sprintf('deal/ReserveDetail?token=%s&line_unit=', $data["token"]);
        $description = str_replace('$detailherf', $detail_herf, $reserveNotice['description']);
        $result['description'] = !empty($description) ? htmlspecialchars_decode($description) : '';
        $result['reserve_rule_url'] = sprintf('/deal/reserveRule?userClientKey=VISITOR_%s', $this->generateVisitorSignature());
        $result['is_firstp2p'] = $this->is_firstp2p;
        $result['userClientKey'] = $userClientKey;
        $this->json_data = $result;
    }
}
