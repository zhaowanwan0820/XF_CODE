<?php

use core\service\DealLoanRepayCalendarService;
use libs\utils\Logger;

/**
 * Created by PhpStorm.
 * User: jingxu
 * 小能客服相关
 * Date: 12/01/2018
 * Time: 16:46
 */
class CustomerServiceAction extends BaseAction
{
    private static $authKey = "Odgxa!Eu#uPX8g@X0DnJbNRxXrZxrL";

    public function __construct()
    {
        parent::__construct();
        \libs\utils\PhalconRPCInject::init();
    }

    public function userInfo()
    {

        $userId = $_REQUEST["userId"];
        $from = intval($_REQUEST["devicetype"]);
        if ($from != 1) $userId = intval($userId);
        //临时验证方案, 小能供应商调用时传入
        $sign = $_REQUEST["sign"];
        $env = get_cfg_var("phalcon.env");
        //测试环境与生产测试环境不验证auth-key
        if ($env != "dev" && $env != "test" && $env != "pdtest" && !$this->passed($userId, $sign)) {
            $this->assign('error', '验证失败');
            $this->display();
            return;
        }

        if ($from == 1) {
            $bindInfo = (new \core\service\WeiXinService)->getByOpenid($userId);
            $userId = $bindInfo['user_id'];
            if (empty($userId)) {
                $this->assign('error', '用户未绑定');
                $this->display();
                return;
            }
        }

        if (!empty($_REQUEST['searchUserId'])) {
            $userId = intval($_REQUEST['searchUserId']);
        }

        $user = \core\dao\UserModel::instance()->find($userId, 'id,user_name,real_name,idno,create_time,money,lock_money,is_effect,byear,bmonth,bday,group_id');
        if (empty($user)) {
            $this->display();
            return;
        }

        $response = array();
        $response['userId'] = $user->id;
        $response['userName'] = $user->user_name;
        //真实姓名
        $response['realName'] = $user->real_name;
        //出生日期
        $response['birthday'] = $user->byear . "-" . $user->bmonth . "-" . $user->bday;
        //注册日期
        $response['createTime'] = to_date($user->create_time);
        //客户状态
        $response['invest'] = (new \core\service\DealLoadService())->countByUserId($user->id) > 0 ? "已投资" : "未投资";
        //账户状态
        $response['is_effect'] = $user->is_effect ? '有效' : '无效';
        //存管余额
        $userSupervisionMoney = (new \core\service\UserThirdBalanceService())->getUserSupervisionMoney($userId);
        //账户余额, 网信+网贷余额
        $response['money'] = bcadd($user->money, $userSupervisionMoney['supervisionBalance'], 2);
        $account_data = (new \core\service\AccountService())->getUserSummary($userId, true);
        //待还本金
        $response['corpus'] = $account_data['corpus'];
        //红包
        $bonus = (new \core\service\BonusService())->getUserBonusInfo($userId);
        //资产总额 待还本金+待还利息+用户余额+冻结+存管余额+冻结+可用红包
        $totalMoney = \libs\utils\Finance::addition(array($account_data['corpus'], $account_data['income'], $user->money, $user->lock_money, $userSupervisionMoney['supervisionBalance'], $userSupervisionMoney['supervisionLockMoney'], $bonus['usableMoney']), 2);
        $response['totalMoney'] = $totalMoney;
        //实际等级
        $vipInfo = (new \core\service\vip\VipService())->getFormatVipInfo($userId);
        $response['actualGradeName'] = $vipInfo['actualGradeName'];
        //服务等级
        $response['gradeName'] = $vipInfo['gradeName'];
        //本人渠道
        $response['selfChannel'] = $this->getChannelStr($user);
        //邀请人渠道
        $userCouponData = (new \core\service\CouponBindService())->getByUserId($userId);
        if (!empty($userCouponData) && isset($userCouponData['refer_user_id'])) {
            $inviterUserId = $userCouponData['refer_user_id'];
            $inviterUser = \core\dao\UserModel::instance()->find($inviterUserId, 'id,group_id');
            $response['inviterChannel'] = $this->getChannelStr($inviterUser);
        } else {
            $response['inviterChannel'] = "";
        }
        //未来一周回款总额
        $beginYear = date('Y');
        $beginMonth = date('n');
        $beginDay = date('j');
        $repayData = (new DealLoanRepayCalendarService())->getUserNoRepayCalendar($userId, $beginYear, $beginMonth, $beginDay, 7);
        $response['repayMoney'] = bcadd($repayData['norepay_principal'], $response['norepay_interest'], 2);
        //最后一次充值时间
        $userLastChargeData = (new \core\service\ChargeService())->getUserLastCharge($user->id);
        if (!empty($userLastChargeData)) {
            $response['userLastChargeTime'] = $userLastChargeData['create_datetime'];
        } else {
            $response['userLastChargeTime'] = "";
        }
        //最后一次提现时间
        $userLastCarryData = (new \core\service\UserCarryService())->getLastWithdrawLog($user->id);
        if (!empty($userLastCarryData)) {
            $response['userLastCarryTime'] = $userLastCarryData['wthdraw_datetime'];
        } else {
            $response['userLastCarryTime'] = "";
        }

        //最后一次回款时间
        $userLastRepays = \core\dao\DealLoanRepayModel::instance()->getUserLastRepay($user->id, 1);
        if (!empty($userLastRepays)) {
            $response['lastRepayTime'] = to_date($userLastRepays[0]['real_time']);
        } else {
            $response['lastRepayTime'] = "";
        }
        //保级剩余时间
        $response['remainRelegatedTime'] = floor($vipInfo['remainRelegatedTime'] / 86400) . "天";
        //未笔投资日期
        $userLastDealLoads = \core\dao\DealLoadModel::instance()->getUserNewLoads($user->id, 1);
        if (!empty($userLastRepays)) {
            $response['lastInvestTime'] = to_date($userLastDealLoads[0]['create_time']);
        } else {
            $response['lastInvestTime'] = "";
        }
        $this->assign('response', $response);

        $this->display();
    }

    public function userInfoApi() {

        $userId = $_REQUEST['userId'];
        $from = intval($_REQUEST["from"]);
        $sign = $_REQUEST["sign"];
        $env = get_cfg_var("phalcon.env");
        //测试环境与生产测试环境不验证auth-key
        if ($env != "dev" && $env != "test" && $env != "pdtest" && !$this->passed($userId, $sign)) {
            return $this->response(-1, '验签失败');
        }

        // 如果字段是openid
        if ($from == 1) {
            $bindInfo = (new \core\service\WeiXinService)->getByOpenid($userId, 'user_id');
            if (empty($bindInfo['user_id'])) {
                return $this->response(-2, '用户未绑定');
            }
            $userId = $bindInfo['user_id'];
        }

        $user = \core\dao\UserModel::instance()->find($userId, 'id,user_name,real_name');
        if (empty($user)) {
            return $this->response(-2, '用户不存在');
        }

        //VIP等级
        $vipInfo = (new \core\service\vip\VipService())->getFormatVipInfo($userId);

        $responseData = [
            'userId' => $userId,
            'userName' => $user['user_name'],
            'realName' => empty($user['real_name']) ? '' : $user['real_name'],
            'vipLevel' => $vipInfo['gradeName'],
        ];
        return $this->response(0, '', $responseData);
    }

    /**
     * 推送接口
     */
    public function pushMessage() {

        $rules = [
            'userIds' => ['filter' => 'required'],
            'content' => ['filter' => 'required'],
            'params' => ['filter' => 'required'],
            'sign' => ['filter' => 'required']
        ];
        try {
            $params = $this->validateJsonData($rules);
        } catch (\Exception $e) {
            return $this->response(-1, $e->getMessage());
        }

        $pushService = new \core\service\PushService();
        $pushResult = [];

        foreach($params['userIds'] as $userId) {
            try {
                $result = $pushService->toSingle($userId, $params['content'], 1, $params['params']);
            } catch (\Exception $e) {
                $result = false;
            }
            $pushResult[$userId] = $result;
        }

        return $this->response(0, '', $pushResult);
    }

    private function passed($dataString, $signature) {
        return hash("sha256", $dataString . self::$authKey) == $signature;
    }

    private function getChannelStr($user)
    {
        $shortAlias = (new \core\service\CouponService())->getOneUserCoupon($user->id)['short_alias'];
        $groupInfo = core\dao\UserGroupModel::instance()->find($user->group_id);

        return "{$groupInfo->name}($shortAlias)";
    }

    private function response($code, $msg = '', $data = []) {

        $result = [
            'errCode' => $code,
            'errMsg' => $msg,
            'data' => $data
        ];
        $result = json_encode($result, JSON_UNESCAPED_UNICODE);
        Logger::info('Customer response data:' . $result);
        header('Content-type: application/json;charset=UTF-8');
        echo $result;
        return true;
    }

    private function validateJsonData($checkRules)
    {
        Logger::info('Customer request data:' . $GLOBALS['HTTP_RAW_POST_DATA']);
        if (empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
            throw new \Exception('Customer request no json data');
        }

        $params = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        foreach ($checkRules as $field => $filter) {
            if ($filter['filter'] == 'required' && !isset($params[$field])) {
                throw new \Exception('Customer request '. $field . ' is empty');
            }
        }

        $signature = $params['sign'];
        unset($params['sign']);
        $signData = json_encode($params, JSON_UNESCAPED_UNICODE);
        if (!$this->passed($signData, $signature)) {
            throw new \Exception('验签失败');
        }

        return $params;
    }
}
