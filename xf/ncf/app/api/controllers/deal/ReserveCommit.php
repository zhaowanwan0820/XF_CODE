<?php
/**
 * 短期标预约-提交预约的按钮
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;


use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\reserve\ReservationConfService;
use core\service\reserve\UserReservationService;
use core\service\reserve\ReservationEntraService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\account\AccountService;
use core\service\risk\RiskAssessmentService;
use core\enum\ReserveEnum;
use core\enum\ReserveConfEnum;
use core\enum\UserAccountEnum;
use core\enum\DealEnum;
use libs\utils\Logger;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionService;
use core\service\account\AccountAuthService;

class ReserveCommit extends ReserveBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'asgn' => array('filter' => 'string'),
            'amount' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            'invest' => array('filter' => 'required', 'message' => 'invest is required'),
            'expire' => array('filter' => 'required', 'message' => 'expire is required'),
            'deal_type' => array('filter' => 'required', 'message' => 'deal_type is required'),
            'discount_id' => array('filter' => 'int'),
            'loantype' => array('filter' => 'int'),
            'rate' => array('filter' => 'string'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve() || !$this->canReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        //获取投资账户
        $accountId = AccountService::getUserAccountId($userInfo['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
        if (empty($accountId)) {
            $this->setErr('ERR_RESERVE_SUPERVISION_NOACCOUNT');
            return false;
        }

        $data = $this->form->data;
        // 强制风险评测
        if($userInfo['is_enterprise_user'] != 1 && $userInfo['idcardpassed'] == 1){
            $riskAssessmentService = new RiskAssessmentService();
            $riskData = $riskAssessmentService->getUserRiskAssessmentData(intval($userInfo['id']), $data['amount']);
            if($riskData['needForceAssess'] == 1){
                $this->setErr('ERR_UNFINISHED_RISK_ASSESSMENT');
                return false;
            }

            //风险评估有效期
            if (!$riskData['isRiskValid']) {
                $this->setErr('ERR_MANUAL_REASON', '您的风险评估结果已超过有效期');
            }

            //出借限额
            if ($riskData['isTotalLimitInvest']) {
                $this->setErr('ERR_MANUAL_REASON', '您预约金额超出总出借金额');
            }
        }

        // 未实名认证不能预约
        if ($userInfo['idcardpassed'] != 1) {
            $this->setErr('ERR_MANUAL_REASON', '您还未进行身份验证，暂无法预约');
            return false;
        }

        // 检查是否开启存管预约
        $isSupervisionReserve = (int)$this->isOpenSupervisionReserve();
        if ($isSupervisionReserve) {
            // 检查用户是否开通存管账户
            $supervisionAccountObj = new SupervisionAccountService();
            $isSupervisionData = $supervisionAccountObj->isSupervision($userInfo['id']);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
            if ($isOpenAccount === 0) {
                $this->setErr('ERR_RESERVE_SUPERVISION_NOACCOUNT');
                return false;
            }
            // 检查用户是否开通快捷投资服务
            // 存管降级不校验
            if (!SupervisionService::isServiceDown()) {
                $isQuickBidAuth = (int)$supervisionAccountObj->isQuickBidAuthorization($userInfo['id']);
                if ($isQuickBidAuth === 0) {
                    $this->setErr('ERR_RESERVE_QUICK_BID');
                    return false;
                }
            }
        }

        $userReservationService = new UserReservationService();
        $dealType = DealEnum::DEAL_TYPE_GENERAL;

        Logger::info(implode(' | ', array(__CLASS__, 'ReserveCommitStart', APP, $userInfo['id'], json_encode($data))));
        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $this->setErr('ERR_MANUAL_REASON', '授权金额参数不合法');
            return false;
        }
        if (false === strpos($data['invest'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
            return false;
        }
        if (false === strpos($data['expire'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '预约有效期参数不合法');
            return false;
        }

        // 投资期限判断
        list($investDeadline, $investDeadlineUnit) = explode('_', $data['invest']);
        // 预约有效期判断
        list($expire, $expireUnit) = explode('_', $data['expire']);
        $loantype = isset($data['loantype']) ? (int) $data['loantype'] : 0;
        $investRate = isset($data['rate']) ? $data['rate'] : 0;

        // 获取后台配置预约入口
        $entraService = new ReservationEntraService();
        $reservationConfService = new ReservationConfService();
        $reserveEntra = $entraService->getReserveEntra($investDeadline, $investDeadlineUnit, $dealType, $investRate, $loantype);
        if (empty($reserveEntra)) {
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约入口');
        }

        // 最低预约金额,单位分
        $minAmount = !empty($reserveEntra['min_amount']) ? $reserveEntra['min_amount'] : 1;
        // 最高预约金额,单位分
        $maxAmount = !empty($reserveEntra['max_amount']) ? $reserveEntra['max_amount']: 9999999900;
        // 用户的预约金额,单位分
        $reserveAmountCent = intval(bcmul($data['amount'], 100));
        if ($reserveAmountCent < $minAmount) {
            $this->setErr('ERR_MANUAL_REASON', sprintf('最低预约授权金额%s元', number_format(bcdiv($minAmount, 100), 2)));
            return false;
        }
        if (!empty($maxAmount) && $reserveAmountCent > $maxAmount) {
            $this->setErr('ERR_MANUAL_REASON', sprintf('最高预约金额%s元', number_format(bcdiv($maxAmount, 100), 2)));
            return false;
        }

        // 检查项目风险承受和个人评估
        if ($userInfo['is_enterprise_user'] != 1){
            $dealProjectService = new DealProjectRiskAssessmentService();
            $projectScoure = $reservationConfService->getScoreByDeadLine($investDeadline, $investDeadlineUnit, $dealType, $investRate, $loantype);
            if ($projectScoure == false){
                $this->setErr('ERR_MANUAL_REASON', '当前您的风险承受能力为“'.$riskData['last_level_name'].'” 暂不能预约此项目');
                return false;
            }
            $dealProjectRiskRet = $dealProjectService->checkReservationRisk($userInfo['id'],$projectScoure,false,$riskData);

            if ($dealProjectRiskRet['result'] == false){
                $this->setErr('ERR_MANUAL_REASON', '当前您的风险承受能力为“'.$riskData['last_level_name'].'” 暂不能预约此项目');
                return false;
            }
        }

        //检查账户用途
        $allowReserve = AccountService::allowAccountLoan($userInfo['user_purpose']);
        if (!$allowReserve) {
            $this->setErr('ERR_MANUAL_REASON', '非投资账户不允许预约');
            return false;
        }

        // 检查授权
        $grantInfo = AccountAuthService::checkAccountAuth($userInfo['id']);
        if (!empty($grantInfo)) {
            $this->setErr('ERR_MANUAL_REASON', '您未授权免密投标，暂无法预约');
            return false;
        }

        // 设置redis锁
        $lockKey = sprintf(UserReservationService::CACHEKEY_YYB_API_LOCK, $userInfo['id']);
        $lockValue = mt_rand(1, 999999);
        $redisLock = \SiteApp::init()->dataCache;
        $lockRet = $redisLock->setNx($lockKey, $lockValue, 3);
        if (!is_object($lockRet) || $lockRet->getPayload() !== 'OK') {
            $this->setErr('ERR_MANUAL_REASON', '请求过于频繁，请稍后再试');
            return false;
        }
        //分站id
        $siteId = \libs\utils\Site::getId();

        //投资券
        $discountId = !empty($data['discount_id']) ? (int) $data['discount_id'] : 0;

        $reserveReferer = $this->isAppDevice() ? ReserveEnum::RESERVE_REFERER_APP : ReserveEnum::RESERVE_REFERER_WAP; //预约来源
        // 创建用户预约投标记录
        $createRet = $userReservationService->createUserReserve($accountId, $reserveAmountCent, $investDeadline, $expire, '', $investDeadlineUnit, $expireUnit, [], $reserveReferer, $siteId, [], $discountId, $dealType, $loantype, $investRate);
        if (false == $createRet['ret']) {
            $this->setErr('ERR_MANUAL_REASON', $createRet['errorMsg']);
            return false;
        }
        // 解除redis锁
        if ($redisLock->getRedisInstance()->get($lockKey) == $lockValue) {
            $redisLock->remove($lockKey);
        }

        //添加监控，预约成功
        \libs\utils\Monitor::add('RESERVE_CREATE_SUCCESS');
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveCommitEnd', APP, $userInfo['id'], json_encode($createRet))));

        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 充值成功后设置的Token
        $pid = md5(uniqid());
        $this->json_data = array('code'=>0, 'url'=>sprintf('/deal/reserveSuccess?pid=%s&userClientKey=%s', $pid, $userClientKey));
        return true;
    }
}
