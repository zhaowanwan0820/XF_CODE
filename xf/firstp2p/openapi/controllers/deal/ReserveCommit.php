<?php
/**
 * 短期标预约-提交预约的按钮
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\service\DealProjectRiskAssessmentService;
use core\dao\ReservationConfModel;
use core\dao\AccountAuthorizationModel;
use core\dao\DealModel;
use libs\utils\Logger;
use libs\payment\supervision\Supervision;

class ReserveCommit extends ReserveBaseAction
{
    private $_ReserveReferer = 2;   //预约来源(wap站)

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'asgn'=>array('filter'=>'required', 'message'=>'缺少签名参数'),
            'amount'=>array(
                'filter'=>'reg',
                'message'=>'ERR_MONEY_FORMAT',
                'option'=>array(
                    'regexp'=>'/^\d+(\.\d{1,2})?$/',
                ),
            ),
            'invest'=>array('filter'=>'required', 'message'=>'投资期限为空'),
            'expire'=>array('filter'=>'required', 'message'=>'预约有效期为空'),
            'deal_type' => array('filter' => 'required', 'message' => '贷款类型为空'),
            "site_id" => array("filter" => 'string', "option" => array("optional" => true)),
            'discount_id' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if(!$this->form->validate()){
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if(!$this->isOpenReserve() || !$this->canReserve()){
            return false;
        }

        if(!$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR', 'Token不正确');
            return false;
        }

        $userId = $userInfo->userId;

        //强制风险评测
        if($isEnterpriseUser = $this->checkEnterpriseUser($userInfo->mobile, $userInfo->userType) != 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array($userId));
            if($riskData['needForceAssess'] == 1){
                $this->setErr('ERR_UNFINISHED_RISK_ASSESSMENT');
                return false;
            }
        }

        //未实名认证不能预约
        if($userInfo->idcardPassed != 1){
            $this->setErr('ERR_MANUAL_REASON', '您还未进行身份验证，暂无法预约');
            return false;
        }

        // 检查是否开启存管预约
        $isSupervisionReserve = $this->isOpenSupervisionReserve();
        if ($isSupervisionReserve) {
            // 检查用户是否开通存管账户
            $isSupervisionData = $this->rpc->local('SupervisionAccountService\isSupervision', array($userId));
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? $isSupervisionData['isSvUser'] : 0;
            if (intval($isOpenAccount) === 0) {
                $this->setErr('ERR_RESERVE_SUPERVISION_NOACCOUNT');
                return false;
            }
            // 检查用户是否开通快捷投资服务
            // 存管降级不校验
            if (!Supervision::isServiceDown()) {
                $isQuickBidAuth = $this->rpc->local('SupervisionAccountService\isQuickBidAuthorization', array($userId));
                if (intval($isQuickBidAuth) === 0) {
                    $this->setErr('ERR_RESERVE_QUICK_BID');
                    return false;
                }
            }
        }

        $data = $this->form->data;
        $dealType = !empty($data['deal_type']) ? (int) $data['deal_type'] : DealModel::DEAL_TYPE_GENERAL;

        Logger::info(implode(' | ', array(__CLASS__, 'ReserveCommitStart', WAP, $userId, json_encode($data))));
        if(empty($data['amount']) || !is_numeric($data['amount'])){
            $this->setErr('ERR_MANUAL_REASON', '授权金额参数不合法');
            return false;
        }

        if(false === strpos($data['invest'], '_')){
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
            return false;
        }

        if(false === strpos($data['expire'], '_')){
            $this->setErr('ERR_MANUAL_REASON', '预约有效期参数不合法');
            return false;
        }

        //获取后台配置的预约标配置信息
        $reservationConfService = new ReservationConfService();
        $reserveConf = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_CONF);
        if(empty($reserveConf) || empty($reserveConf['min_amount'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约信息');
            return false;
        }

        if(empty($reserveConf['invest_conf'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置投资期限');
            return false;
        }

        if(empty($reserveConf['reserve_conf'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约有效期');
            return false;
        }

        list($investDeadline, $investDeadlineUnit) = explode('_', $data['invest']); //投资期限判断
        list($expire, $expireUnit) = explode('_', $data['expire']); //预约有效期判断

        $reserveLimitAmount = $reservationConfService->getReserveLimitAmountByDealType($dealType); //获取限制金额
        $minAmount = !empty($reserveLimitAmount['min_amount']) ? bcmul($reserveLimitAmount['min_amount'], 100) : 1; //最低预约金额,单位分
        $maxAmount = !empty($reserveLimitAmount['max_amount']) ? bcmul($reserveLimitAmount['max_amount'], 100) : 9999999900; //最高预约金额,单位分
        $reserveAmountCent = intval(bcmul($data['amount'], 100)); //用户的预约金额,单位分
        if($reserveAmountCent < $minAmount){
            $this->setErr('ERR_MANUAL_REASON', sprintf('最低预约授权金额%s元', number_format(bcdiv($minAmount, 100), 2)));
            return false;
        }

        if(!empty($maxAmount) && $reserveAmountCent > $maxAmount){
            $this->setErr('ERR_MANUAL_REASON', sprintf('最高预约金额%s元', number_format(bcdiv($maxAmount, 100), 2)));
            return false;
        }

        //检查项目风险承受和个人评估
        if($isEnterpriseUser != 1){
            $projectScoure = $reservationConfService->getScoreByDeadLine($investDeadline, $investDeadlineUnit);
            if($projectScoure == false){
                $this->setErr('ERR_MANUAL_REASON', '当前您的风险承受能力为“'.$riskData['last_level_name'].'” 暂不能预约此项目');
                return false;
            }

            $dealProjectRiskRet = (new DealProjectRiskAssessmentService())->checkReservationRisk($userId, $projectScoure, false, $riskData);
            if($dealProjectRiskRet['result'] == false){
                $this->setErr('ERR_MANUAL_REASON', '当前您的风险承受能力为“'.$riskData['last_level_name'].'” 暂不能预约此项目');
                return false;
            }
        }

        //设置redis锁
        $lockKey = sprintf(UserReservationService::CACHEKEY_YYB_OPENAPI_LOCK, $userId);
        $lockValue = mt_rand(1, 999999);
        $redisLock = \SiteApp::init()->dataCache;
        $lockRet = $redisLock->setNx($lockKey, $lockValue, 3);
        if(!is_object($lockRet) || $lockRet->getPayload() !== 'OK'){
            $this->setErr('ERR_MANUAL_REASON', '请求过于频繁，请稍后再试');
            return false;
        }

        //存管免密授权
        $grantInfo = $this->rpc->local('SupervisionService\checkAuth', [$userId]);
        if(!empty($grantInfo)){
            $this->setErr("ERR_MIANMI_SET", "未开通免密授权");
            return false;
        }
        //校验是否可投资
        if(isset($userInfo->userPurpose)){
            $allowReserve = $this->rpc->local('UserService\allowAccountLoan', [$userInfo->userPurpose]);
            if(!$allowReserve){
                $this->setErr("ERR_USER_PURPOSE");
                return false;
            }
        }

        //分站id
        $siteId = isset($data['site_id']) ? intval(trim($data['site_id'])) : 1;

        //投资券
        $discountId = !empty($data['discount_id']) ? (int) $data['discount_id'] : 0;

        //创建用户预约投标记录
        $createRet = (new UserReservationService())->createUserReserve($userId, $reserveAmountCent, $investDeadline, $expire, '', $investDeadlineUnit, $expireUnit, $reserveConf, $this->_ReserveReferer, $siteId, [], $discountId, $dealType);
        if(false == $createRet['ret']){
            $this->setErr('ERR_MANUAL_REASON', $createRet['errorMsg']);
            return false;
        }

        //解除redis锁
        if($redisLock->getRedisInstance()->get($lockKey) == $lockValue) {
            $redisLock->remove($lockKey);
        }

        //添加监控，预约成功
        \libs\utils\Monitor::add('RESERVE_CREATE_SUCCESS');
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveCommitEnd', WAP, $userId, json_encode($createRet))));

        return true;
    }
}
