<?php
/**
 * Bid Return 投资存管回调
 **/

namespace api\controllers\deal;

use libs\web\Form;
use libs\web\Url;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\DealModel;
use core\service\O2OService;
use core\service\DiscountService;
use core\service\oto\O2OUtils;
use libs\utils\Aes;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\service\ncfph\Proxy;

class BidReturn extends AppBaseAction
{
    protected $useSession = true;
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'orderId' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
           'status' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        // 走代理请求普惠
        $ncfphProxy = new Proxy();
        $ncfphProxy->execute();
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $formData = $this->form->data;
        $orderId = trim($formData['orderId']);
        $site_id = trim($formData['site_id']);
        if (empty($orderId)) {
            $this->setErr('ERR_SYSTEM', '缺少OrderId参数');
            return false;
        }

        try {
            $orderRes = $this->rpc->local('SupervisionFinanceService\orderSearch', array($orderId));
            Logger::info('BidOrderRes:'.json_encode($orderRes));
            if ($orderRes['status'] == 'S' && isset($orderRes['data'])) {
                $status = $orderRes['data']['status'];
            }
            $res = $this->rpc->local('DealLoadService\bidForBankSecret', array($orderId, $user['id'], $status));
        } catch (\Exception $e) {
            $this->setErr('ERR_SYSTEM', '出借失败:'.$e->getMessage());
            return false;
        }

        if($res['error']){
            $this->setErr('ERR_SYSTEM', '出借失败:'.$res['msg']);
            return;
        }
        $bidMoney = isset($res['money']) ? $res['money'] : '0.00';

        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
        $apiLog = array(
            'time' => date('Y-m-d H:i:s'),
            'userId' => $user['id'],
            'orderId' => $orderId,
            'ip' => get_real_ip(),
            'loadId' => $res['load_id'],
            'money' => $bidMoney,
            'os' => $os,
            'channel' => $channel,
        );
        logger::wLog("API_BID:".json_encode($apiLog));
        PaymentApi::log("API_BID:".json_encode($apiLog), Logger::INFO);
        $deal = $this->rpc->local("DealService\getDeal", array($res['deal_id']));

        // 分享红包链接扩展信息
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($user['id'])), 180);
        $bonusTemplete = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getBonusTempleteBySiteId', array($site_id)), 10);
        if (!empty($bonusTemplete)) {
            $share_icon    = $bonusTemplete['share_icon'];
            $share_title   = $bonusTemplete['share_title'];
            $share_content = $bonusTemplete['share_content'];
        } else {
            $share_icon    = get_config_db('API_BONUS_SHARE_FACE',$site_id);
            $share_title   = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
            $share_content = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);
        }

        $bonusFace = $share_icon;
        $bonusTitle = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $share_title);
        $bonusContent = str_replace('{$BONUS_TTL}', $bonusTtl, $share_content);
        $bonusContent = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $bonusContent);
        $host = get_config_db('API_BONUS_SHARE_HOST', $site_id);

        // 投资弹窗默认的领取链接
        $prizeType = '';
        $prizeList = array();
        $prizeTitle = '';
        $prizeUrl = '';
        // O2O 读取礼物列表 BEGIN {
        if ($deal['deal_type'] != 1) {
            $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            $loadId = $res['load_id'];
            $digObject = new \core\service\DigService('makeLoan', array(
                'id' => $user['id'],
                'loadid' => $loadId,
                'cn' => '',
            ));
            $prizeList = $digObject->getResult();
            $title = urlencode('领取礼券');
            if (!empty($prizeList)) {
                \es_session::set('o2oViewAccess','pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
                if($this->app_version < 345) {
                    //3.4.5版本以下的弹窗逻辑需要兼容下
                    $prizeType = 'o2o';
                    $prizeTitle = '';
                    $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $event, $loadId, CouponGroupEnum::CONSUME_TYPE_P2P));
                    $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                } else {
                    if (count($prizeList) > 1) {
                        //多个券组
                        $prizeType = 'o2o';
                        $prizeTitle = '';
                        $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $event, $loadId, CouponGroupEnum::CONSUME_TYPE_P2P));
                        $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                    } else {
                        //单个礼券,根据使用规则封装url
                        $prizeType = 'acquire';
                        $token = $this->form->data['token'];
                        foreach ($prizeList as $groupInfo) {
                            $prizeTitle = $groupInfo['productName'];
                            $groupId = $groupInfo['id'];
                            $useRules = $groupInfo['useRules'];
                            $storeId = $groupInfo['storeId'];
                        }
                        //只有收货，收券类需要跳转到acquireDetail，其他类型跳转到acquireExchange
                        if (in_array($useRules, CouponGroupEnum::$ONLINE_FORM_USE_RULES)) {
                            $url = urlencode(sprintf(app_conf('O2O_DEAL_DETAIL_URL'), $event, $loadId, $groupId,
                                $token, CouponGroupEnum::CONSUME_TYPE_P2P));

                            $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                        } else {
                            //直接兑换的，不显示返回按钮，增加关闭按钮
                            $url = urlencode(sprintf(app_conf('O2O_DEAL_EXCHANGE_URL'), $event, $loadId, $groupId,
                                $useRules, $storeId, $token, CouponGroupEnum::CONSUME_TYPE_P2P));

                            $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=false&needrefresh=true&needcloseall=true&title=%s&url=%s', $title, $url);
                        }
                    }
                }
            }

        }

        // 通知贷标的不生成红包
        // 生成红包，如果bonusSn !== false则为加密串，可以生成链接
        $bonusSn = '';
        $bonusTtl = 0; // app端根据这个数字大小来做分享链接展现判断
        $bonusBidFinished = '';
        if (empty($prizeList) && $deal['deal_type'] != 1) {
            $bonusSn = $this->rpc->local("DealService\makeBonus", array($res['deal_id'], $res['load_id'], $user['id'], $money, $site_id));
            $groupInfo = $this->rpc->local('BonusService\get_bonus_group', array($res['load_id']));
            if (!empty($groupInfo)) {
                $bonusTtl = $groupInfo['count'];
                $bonusBidFinished = app_conf('API_BONUS_SHARE_BID_FINISHED');
            }
        }
        $bonusUrl = $host.'/hongbao/GetHongbao?sn='.$bonusSn; // web端提供

        $goodPrice = $res['discountGoodsPrice'];
        $goodPrice = base64_decode(str_pad(strtr($goodPrice, '-_', '+/'), strlen($goodPrice) % 4, '=', STR_PAD_RIGHT));
        $goodTitle = '';
        if ($res['discountId'] > 0) {
            $goodTitle = ($res['discountType'] == 1) ? '返现劵' : '加息劵';
        }

        //存管相关
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($user['id']));
        $freePaymentUrl = '';
        if (!empty($svInfo['status']) && $svInfo['isSvUser'] == 1 && empty($svInfo['isFreePayment'])) {
            if ($isShowBankAlert = $this->rpc->local('SupervisionDealService\setQuickBidAuthCount', array($user['id']))) {
                $freePaymentUrl = sprintf(
                    $this->getHost()."/payment/Transit?params=%s",
                    urlencode(json_encode(['srv' => 'freePaymentQuickBid', 'return_url' => 'firstp2p://api?type=closeallpage']))
                );
            }
        }

        //会员信息
        $isShowVip = 0;
        if($this->rpc->local("VipService\isShowVip", array($user['id']), VipEnum::VIP_SERVICE_DIR) && ($this->app_version >= 472)){
            $isShowVip = 1;
            $expectVipRebate = $this->rpc->local("VipService\getExpectVipRebate",array($user['id'], $res['load_id']), VipEnum::VIP_SERVICE_DIR);
            $vipSourceType = ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) ? VipEnum::VIP_SOURCE_P2P : VipEnum::VIP_SOURCE_ZHUANXIANG;
            $sourceAmount = O2OUtils::getAnnualizedAmountByDealIdAndAmount($res['deal_id'], $bidMoney);
            $vipPoint = $this->rpc->local("VipService\computeVipPoint", array($vipSourceType, $sourceAmount), VipEnum::VIP_SERVICE_DIR);
        }

        $isFirstInvest = $this->rpc->local("DealLoadService\isFirstInvest", array($user['id']));
        $this->json_data = array(
            //临时增加折扣券id的传入和返回
            'goodPrice' => $goodPrice ? $goodPrice : '',
            'goodTitle' => $goodTitle,
            // TODO O2OMock 投资选择礼品
            'prize_type' => $prizeType,
            'prize_url' => $prizeUrl,
            'prize_title' => $prizeTitle,
            // O2OMock End
            'load_id' => strval($res['load_id']),
            'bonus_ttl' => $bonusTtl,
            'bonus_url' => $bonusUrl,
            'bonus_face' => $bonusFace,
            'bonus_title' => $bonusTitle,
            'bonus_content' => $bonusContent,
            'bonus_bid_finished' => $bonusBidFinished,
            'deal_name'     => $deal['name'],
            'deal_type'     => $deal['deal_type'],
            'type_info'     => $deal['type_info']['name'],
            'income_rate'   => $deal['rate'],
            'repay_time'    => $deal['repay_time'].($deal['loantype'] == 5 ? '天' : '个月'),
            'loantype_name' => $deal['loantype_name'],
            'borrow_amount' => $deal['borrow_amount'],
            'bid_money' => $bidMoney,
            'recommendation'=> "推荐一个投资项目：{$deal['name']}，年化收益{$deal['rate']}，投资时用我的优惠码{$senderUserCoupon['short_alias']} 还可以返利，挺靠谱的，可以看看。http://".app_conf('WXLC_DOMAIN')."/d/".Aes::encryptForDeal($deal['id'])."?cn={$senderUserCoupon['short_alias']}",
            'freePaymentUrl' => $freePaymentUrl,
            'reportStatus' => $deal['report_status'],
            'isShowVip' => $isShowVip,
            'expectVipRebate' => $isShowVip == 1 ? $expectVipRebate['rebateDesc'] : '',
            // vip经验值字段
            'vipPoint' => $isShowVip == 1 ? $vipPoint : '',
            //投资成功文案
            'succText' => $isFirstInvest ? '恭喜您已完成首次出借' : '出借成功',
        );
    }

} // END class Bid extends AppBaseAction
