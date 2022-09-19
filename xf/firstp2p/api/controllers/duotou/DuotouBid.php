<?php
/**
 * DuotouBid controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-02-29
 **/

namespace api\controllers\duotou;

use libs\utils\Logger;
use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\UserService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\DiscountService;

/**
 * 多投投资接口
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class DuotouBid extends DuotouBaseAction
{
    protected $useSession = true;
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'project_id' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            'activity_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
            'discount_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_group_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_sign' => array('filter' => 'string', 'optional' => true),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if (!$this->dtInvoke())
            return false;
        if (!$this->form->data['project_id']) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        if (bccomp($this->form->data['money'], 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        //仅允许投资户投资
        if(!$this->rpc->local('UserService\allowAccountLoan', array($userInfo['user_purpose']))){
            $this->setErr('ERR_INVESTMENT_USER_CAN_BID', $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
            return false;
        }

        //强制风险评测
        if($userInfo['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($userInfo['id'])));
            if($riskData['needForceAssess'] == 1){
                $this->setErr('ERR_UNFINISHED_RISK_ASSESSMENT');
                return false;
            }

            $riskData2 = $this->rpc->local('DealProjectRiskAssessmentService\checkUserProjectRisk', array($GLOBALS['user_info']['id'], 2, true, $riskData));
            if ($riskData2['result'] == false) {
                $this->setErr('ERR_UNFINISHED_RISK_ASSESSMENT');
                return false;
            }
        }

        $bankcardInfo = $this->rpc->local("UserBankcardService\getBankcard", array($userInfo['id']));
        if(!$bankcardInfo || $bankcardInfo['status'] != 1){
            $this->setErr('ERR_MANUAL_REASON','请完善银行卡信息');
            return false;
        }

        $data = $this->form->data;
        $dealId = intval($data['project_id']);
        $money = floatval($data['money']);
        $activityId = !empty($data['activity_id']) ? $data['activity_id']: 0; //参与活动Id

        $activityInfo = $this->rpc->local('DtEntranceService\getEntranceInfo', array($activityId,$userInfo['site_id']));
        if(empty($activityInfo)){
            return $this->assignError('ERR_MANUAL_REASON','活动信息不存在');
        }

        $coupon_id = "";
        $optionParams=array();
        $optionParams['activityId'] = $activityId ;

        // 投资券消券参数 透传
        if (isset($data['discount_id']) && intval($data['discount_id']) > 0) {
            $optionParams['discount_id'] = intval($data['discount_id']);
            $optionParams['discount_type'] = isset($data['discount_type']) ? $data['discount_type'] : 0;

            $discountGroupId = isset($data['discount_group_id']) ? $data['discount_group_id'] : '';
            $discountSign = isset($data['discount_sign']) ? $data['discount_sign'] : '';
            $checkParams = array($userInfo['id'], $activityId, $optionParams['discount_id'], $discountGroupId,
                $discountSign, $money, CouponGroupEnum::CONSUME_TYPE_DUOTOU);
            $checkResult = $this->rpc->local('O2OService\checkDiscountSignature', $checkParams);
            if ($checkResult == false) {
                $errorMsg = $this->rpc->local('O2OService\getErrorMsg', array());
                $this->setErr('ERR_MANUAL_REASON', $errorMsg);
                return false;
            }
        }

        $optionParams['isNewUser'] = $this->rpc->local('DtActivityRulesService\isMatchRule', array('loadGte3', array('userId'=>$userInfo['id'])),'duotou');

        $result = $this->rpc->local("DtBidService\bid", array($userInfo['id'], $dealId, $money, $coupon_id, $optionParams));
        if ($result['errCode'] != 0) {
            $this->setErr('ERR_DEAL_FORBID_BID', $result['errMsg']);
            return false;
        }

        // 投资记录id
        $load_id = $result['data']['loadId'];//投资记录id
        $isFirstBid = $result['data']['isFirst'];//是否首次投资
        // 获取o2o的触发结果
        $action = CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID;
        if ($isFirstBid) {
            $action = CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID;
        }

        $rpcParams = array($userInfo['id'], $action, $load_id, CouponGroupEnum::CONSUME_TYPE_DUOTOU);
        $prizeList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);

        $prizeType = '';
        $prizeTitle = '';
        $prizeUrl = '';
        if (!empty($prizeList)) {
            $title = urlencode('领取礼券');
            // session中设置页面浏览的来源，方便前端控制关闭逻辑
            \es_session::set('o2oViewAccess', 'pick');
            if (count($prizeList) > 1) {
                // 多个券组
                $prizeType = 'o2o';
                $prizeTitle = '';
                $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $action, $load_id, CouponGroupEnum::CONSUME_TYPE_DUOTOU));
                $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
            } else {
                //单个礼券,根据使用规则封装url
                $prizeType = 'acquire';
                $token = $data['token'];
                foreach ($prizeList as $groupInfo) {
                    $prizeTitle = $groupInfo['productName'];
                    $groupId = $groupInfo['id'];
                    $useRules = $groupInfo['useRules'];
                    $storeId = $groupInfo['storeId'];
                }

                // 只有收货，收券, 游戏活动类需要跳转到acquireDetail，其他类型跳转到acquireExchange;大转盘游戏也跳转到acquireDetail保持逻辑一致
                if (in_array($useRules, CouponGroupEnum::$ONLINE_FORM_USE_RULES)) {
                    $url = urlencode(sprintf(app_conf('O2O_DEAL_DETAIL_URL'), $action, $load_id, $groupId, $token, CouponGroupEnum::CONSUME_TYPE_DUOTOU));
                    $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                } else {
                    // 直接兑换的，不显示返回按钮，增加关闭按钮
                    $url = urlencode(sprintf(app_conf('O2O_DEAL_EXCHANGE_URL'), $action, $load_id, $groupId, $useRules, $storeId, $token, CouponGroupEnum::CONSUME_TYPE_DUOTOU));
                    $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=false&needrefresh=true&needcloseall=true&title=%s&url=%s', $title, $url);
                }
            }
        }

        $bonusTtl = 0;
        $bonusUrl = "";
        $bonusFace = "";
        $bonusTitle = "";
        $bonusContent = "";
        $bonusBidFinished = "";
        $repayStartTime = "";
        if($this->app_version < 472) {
            $repayStartTime = '按日计算利息/收益';
        }

        $vipInfo = $this->rpc->local("VipService\getVipInfo", array($userInfo['id']), 'vip');
        $raiseInterest = 0;
        if ($vipInfo) {
            $raiseInterest = $this->rpc->local("VipService\getVipInterest", array($vipInfo['service_grade']), "vip");
        }
        $res = array(
            'money' => number_format($money, 2),
            'projectName' => $result['data']['projectName'],
            'repayStartTime' => $repayStartTime,
            'prize_type' => $prizeType,
            'prize_url' => $prizeUrl,
            'prize_title' => $prizeTitle,
            'bonus_ttl' => $bonusTtl,
            'bonus_url' => $bonusUrl,
            'bonus_face' => $bonusFace,
            'bonus_title' => $bonusTitle,
            'bonus_content' => $bonusContent,
            'bonus_bid_finished' => $bonusBidFinished,
            'vipPoint' => '加入后每日计算并发放',//显示“起息后每日计算并发放”,提前增加字段供app定版用
            'vipInfo' => ($raiseInterest > 0) ? '转让/退出成功后计算并发放' : '',//转让成功后计算并发放”,提前增加字段供app定版用
            'isFirstInvest' => intval($isFirstBid),
        );
        $res['res'] = $isFirstBid ? '恭喜您已完成首次加入' : '加入成功';
        $this->json_data = $res;

    }

}
