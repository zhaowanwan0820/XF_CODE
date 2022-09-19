<?php

namespace api\controllers\duotou;

use libs\utils\Logger;
use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\UserService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\DiscountService;
use core\service\duotou\DtP2pDealBidService;

/**
 * 多投验密投资接口
 **/
class DuotouBidReturn extends DuotouBaseAction
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
            'orderId' => array(
                'filter' => 'required',
                'message' => 'orderId缺失',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $formData = $this->form->data;
        $orderId = trim($formData['orderId']);
        if (empty($orderId)) {
            $this->setErr('ERR_SYSTEM', '缺少OrderId参数');
            return false;
        }
        (new \core\service\ncfph\Proxy())->execute();// 代理请求普惠接口
        try {
            $orderRes = $this->rpc->local('SupervisionFinanceService\orderSearch', array($orderId));
            Logger::info('DuotouBidOrderRes:'.json_encode($orderRes));

            if($orderRes['status'] == 'F' && $orderRes['respCode'] == '1035'){
                $this->setErr('ERR_SYSTEM', '投资进行中,请稍后查看资金记录');
                return false;
            }

            $status = 0;
            if ($orderRes['status'] == 'S' && isset($orderRes['data'])) {
                $status = $orderRes['data']['status'];
            }
            $result = (new DtP2pDealBidService())->dealBidForSecret($orderId, $userInfo['id'], $status);
        } catch (\Exception $e) {
            $this->setErr('ERR_SYSTEM', '投资失败:'.$e->getMessage());
            return false;
        }

        if ($result['errCode'] != 0) {
            $this->setErr('ERR_DEAL_FORBID_BID', $result['errMsg']);
            return false;
        }

        // 投资记录id
        $load_id = $result['data']['loadId'];//投资记录id
        $isFirstInvest = $result['data']['isFirst'];//是否首次投资

        // 获取o2o的触发结果
        $action = $isFirstInvest ? CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID : CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID;

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
                $token = $formData['token'];
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
            $repayStartTime = '按日计算收益';
        }
        $vipInfo = $this->rpc->local("VipService\getVipInfo", array($userInfo['id']), 'vip');
        $raiseInterest = 0;
        if ($vipInfo) {
            $raiseInterest = $this->rpc->local("VipService\getVipInterest", array($vipInfo['service_grade']), "vip");
        }

        $res = array(
            'money' => number_format($result['data']['money'], 2),
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
            'isFirstInvest' => intval($isFirstInvest),
        );
        $res['res'] = $isFirstInvest ? '恭喜您已完成首次加入' : '加入成功';
        $this->json_data = $res;

    }

}
