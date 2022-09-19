<?php
/**
 * 理财首页显示标的
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2017.05.17
 */


namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use core\service\GoldBidService;
use core\service\GoldBidCurrentService;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\utils\Logger;

class DoBid extends GoldBaseAction {


    protected $useSession = true;
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'dealId' => array('filter' => 'required', 'message' => 'dealId is required'),
            'buyAmount' => array('filter' => 'required', 'message' => 'byAmount is required'),
            'buyPrice' => array('filter' => 'required', 'message' => 'buyPrice is required'),
            'coupon' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ticket' => array('filter' => 'required', 'message' => 'ticket is required'),
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
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_goodprice' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }

    }

    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        //验证ticket
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticketRes = $redis->get($data['ticket']);
        if ($ticketRes != $user['id'] || empty($ticketRes)) {
            $this->setErr('ERR_MANUAL_REASON','请不要重复提交订单');
            return false;
        }
        //检查是否授权
        $res = $this->rpc->local('GoldService\isAuth', array($user['id']));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON','获取用户授权信息失败');
            return false;
        }

        if ($res['errCode'] == 0 && !$res['data']) {
            $this->setErr('ERR_MANUAL_REASON','用户未授权，不能投资');
            return false;
        }

        $discountId = isset($data['discount_id']) ? intval($data['discount_id']) : 0;
        $discountGroupId = isset($data['discount_group_id']) ? intval($data['discount_group_id']) : 0;
        $discountSign = isset($data['discount_sign']) ? trim($data['discount_sign']) : '';
        $goodPrice = isset($data['discount_goodprice']) ? $data['discount_goodprice'] : '';
        $goodPrice = base64_decode(str_pad(strtr($goodPrice, '-_', '+/'), strlen($goodPrice) % 4, '=', STR_PAD_RIGHT));
        $discountSuccessDesc = $goodPrice ? $goodPrice : '';

        $prizeType = '';
        $prizeTitle = '';
        $prizeUrl = '';
        //dealId==1 为优金宝投资
        if ($data['dealId'] == CommonEnum::GOLD_CURRENT_DEALID) {//优金宝
            //判断是否非交易日
            $isTradDay = check_trading_day(time());
            if (!$isTradDay || !$this->check_trade_time()) {
                $this->setErr('ERR_MANUAL_REASON','当前为非交易时段');
                return false;
            }
           //优金宝在售开关，值为0时开关关闭显示售罄
            if((int)app_conf('GOLD_SALE_CURRENT_SWITCH')==0){
                $this->setErr('ERR_MANUAL_REASON','优金宝已售罄');
                return false;
            }
            //优金宝白名单用户开关，开关不配置无影响 有值时只有白名单用户可买 其余用户显示售罄
            $switch=app_conf('GOLD_SALE_CURRENT_USERID');
            $isWhiteList=$this->rpc->local('GoldService\isSellByUserId',array($user['id']));
            if(!empty($switch)&&!$isWhiteList){
                $this->setErr('ERR_MANUAL_REASON','优金宝已售罄');
                return false;
            }
            $goldBidCurrent = new GoldBidCurrentService(
                    $user['id'],
                    floatval($data['buyAmount']),
                    floatval($data['buyPrice']),
                    $data['coupon'],
                    $data['ticket'],
                    $discountId,
                    $discountGroupId,
                    $discountSign,
                    $discountSuccessDesc
            );
            $res = $goldBidCurrent->doBid();
            $res['data']['tip'] = '您购买的优金宝收益预计于'.date ( "Y-m-d",time()+86400).'日起算';//优金宝第二天开始计息
        } else {//优长金
            if (app_conf('GOLD_SALE_SWITCH') == 0) {
                $this->setErr('ERR_MANUAL_REASON','已售罄');
                return false;
            }
            $goldBidService = new GoldBidService(
                    intval($data['dealId']),
                    $user['id'],
                    floatval($data['buyAmount']),
                    floatval($data['buyPrice']),
                    $data['coupon'],
                    $data['ticket'],
                    $discountId,
                    $discountGroupId,
                    $discountSign,
                    $discountSuccessDesc
            );
            $res = $goldBidService->doBid();

            $bidRes = $goldBidService->isFirstBid($user['id'], $data['ticket']);
            if ($bidRes) {
                // 获取o2o的触发结果
                $action = CouponGroupEnum::TRIGGER_GOLD_REPEAT_DOBID;
                if (isset($bidRes['isFirst']) && $bidRes['isFirst']) {
                    $action = CouponGroupEnum::TRIGGER_GOLD_FIRST_DOBID;
                }

                $load_id = $bidRes['dealLoadId'];
                $rpcParams = array($user['id'], $action, $load_id, CouponGroupEnum::CONSUME_TYPE_GOLD);
                $prizeList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
                if (!empty($prizeList)) {
                    $title = urlencode('领取礼券');
                    // session中设置页面浏览的来源，方便前端控制关闭逻辑
                    \es_session::set('o2oViewAccess', 'pick');
                    if (count($prizeList) > 1) {
                        // 多个券组
                        $prizeType = 'o2o';
                        $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $action, $load_id, CouponGroupEnum::CONSUME_TYPE_GOLD));
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
                            $url = urlencode(sprintf(app_conf('O2O_DEAL_DETAIL_URL'), $action, $load_id, $groupId, $token, CouponGroupEnum::CONSUME_TYPE_GOLD));
                            $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                        } else {
                            // 直接兑换的，不显示返回按钮，增加关闭按钮
                            $url = urlencode(sprintf(app_conf('O2O_DEAL_EXCHANGE_URL'), $action, $load_id, $groupId, $useRules, $storeId, $token, CouponGroupEnum::CONSUME_TYPE_GOLD));
                            $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=false&needrefresh=true&needcloseall=true&title=%s&url=%s', $title, $url);
                        }
                    }
                }
            }
        }

        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['msg']);
            return false;
        }
        $redis->del($data['ticket']);

        $repayStartTime = "";
        if($this->app_version < 472) {
            $repayStartTime = '按日计算收益';
        }

        $result = $res['data'];
        $result['prize_type'] = $prizeType;
        $result['prize_url'] = $prizeUrl;
        $result['prize_title'] = $prizeTitle;
        Logger::debug('gold prize list: '.json_encode($prizeList).', result: '.json_encode($result));
        $this->json_data = $result;
    }

}

