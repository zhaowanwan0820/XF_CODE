<?php
/**
 * Bid Return 投资存管回调
 **/

namespace api\controllers\deal;

use libs\web\Form;
use libs\web\Url;
use libs\utils\Aes;
use libs\utils\Risk;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\deal\DealModel;
use api\controllers\AppBaseAction;
use core\enum\VipEnum;
use core\enum\CouponGroupEnum;
use core\service\risk\RiskServiceFactory;
use core\service\supervision\SupervisionFinanceService;
use core\service\dealload\DealLoadService;
use core\service\deal\DealService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionDealService;
use core\service\user\VipService;
use core\service\coupon\CouponService;
use core\service\o2o\CouponService as O2OCouponService;
use core\service\deal\P2pDealBidService;
use core\enum\DealEnum;

class BidReturn extends AppBaseAction {
    protected $useSession = true;

    public function init() {
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
        }
    }

    public function invoke() {
        $user = $this->user;
        $formData = $this->form->data;
        $orderId = trim($formData['orderId']);
        $site_id = trim($formData['site_id']);
        $isCanUseBonus = (isset($user['canUseBonus'])) ? $user['canUseBonus'] : DealEnum::CAN_USE_BONUS;
        if (empty($orderId)) {
            $this->setErr('ERR_SYSTEM', '缺少OrderId参数');
        }

        $biddealService = new P2pDealBidService();
        if ('0' === $biddealService->getBidLock($orderId)) {
            logger::error(__CLASS__. ' '. __FUNCTION__.' '.$orderId.' get lock fail');
            $this->setErr('ERR_SYSTEM', '正在出借，请稍后查看资金记录');
        }

        try {
            $supervisionFinanceService = new SupervisionFinanceService();
            $orderRes = $supervisionFinanceService->orderSearch($orderId);
            Logger::info('BidOrderRes:'.json_encode($orderRes));
            if ($orderRes['status'] == 'S' && isset($orderRes['data'])) {
                $status = $orderRes['data']['status'];
            }
            $dealloadService = new DealLoadService();
            $res = $dealloadService->bidForBankSecret($orderId,$user['id'],$status);
        } catch (\Exception $e) {
            $this->setErr('ERR_SYSTEM', '出借失败:'.$e->getMessage());
        }

        if ($res['error']) {
            $this->setErr('ERR_SYSTEM', '出借失败:'.$res['msg']);
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
        $dealService = new DealService();
        $deal = $dealService->getDeal($res['deal_id']);

        // TODO 加缓存
        $senderUserCoupon = CouponService::getOneUserCoupon($user['id']);
        if (empty($senderUserCoupon)){
            $senderUserCoupon['short_alias'] = '';
        }

        $prizeType = '';
        $prizeTitle = '';
        $prizeDesc = '';
        $prizeUrl = '';
        if ($user['isFromWxlc']) {
            // O2O 读取礼物列表 BEGIN {
            $o2oRes = O2OCouponService::getFormatInfoWithGroupList(
                $user['id'],
                CouponGroupEnum::TRIGGER_REPEAT_DOBID,
                $res['load_id'],
                $formData['token'],
                CouponGroupEnum::CONSUME_TYPE_P2P,
                $this->isWapCall()
            );

            $prizeType = $o2oRes['prizeType'];
            $prizeTitle = $o2oRes['prizeTitle'];
            $prizeDesc = $o2oRes['prizeDesc'];
            $prizeUrl = $o2oRes['prizeUrl'];
        }

        $goodPrice = $res['discountGoodsPrice'];
        $goodPrice = base64_decode(str_pad(strtr($goodPrice, '-_', '+/'), strlen($goodPrice) % 4, '=', STR_PAD_RIGHT));
        $goodTitle = '';
        if ($res['discountId'] > 0) {
            $goodTitle = ($res['discountType'] == 1) ? '返现劵' : '加息劵';
        }

        //存管相关
        $supervisionService = new SupervisionService();
        $svInfo = $supervisionService->svInfo($user['id']);
        $freePaymentUrl = '';
        if (!empty($svInfo['status']) && $svInfo['isSvUser'] == 1 && empty($svInfo['isFreePayment'])) {
            $supervisionDealService = new SupervisionDealService();
            $isShowBankAlert = $supervisionDealService->setQuickBidAuthCount($user['id']);
            if ($isShowBankAlert) {
                $freePaymentUrl = sprintf(
                    $this->getHost()."/payment/Transit?params=%s",
                    urlencode(json_encode(['srv' => 'freePaymentQuickBid', 'return_url' => 'firstp2p://api?type=closeallpage']))
                );
            }
        }

        // 会员信息
        $isShowVip = 0;
        $dealloadServcie = new DealLoadService();
        $isFirstInvest = $dealloadServcie->isFirstInvest($user['id']);
        $this->json_data = array(
            //临时增加折扣券id的传入和返回
            'goodPrice' => $goodPrice ? $goodPrice : '',
            'goodTitle' => $goodTitle,
            // TODO O2OMock 投资选择礼品
            'prize_type' => $prizeType,
            'prize_url' => $prizeUrl,
            'prize_title' => $prizeTitle,
            'prize_desc' => $prizeDesc,
            // O2OMock End
            'load_id' => strval($res['load_id']),
            'bonus_ttl' => '',
            'bonus_url' => '',
            'bonus_face' => '',
            'bonus_title' => '',
            'bonus_content' => '',
            'bonus_bid_finished' => '',
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
