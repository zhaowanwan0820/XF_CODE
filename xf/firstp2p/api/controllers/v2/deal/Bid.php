<?php
/**
 * Bid controller class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace api\controllers\deal;

use libs\web\Form;
use libs\web\Url;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\O2OService;
use core\service\DiscountService;
use libs\utils\Aes;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\service\oto\O2OUtils;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\dao\DealModel;
use core\dao\EnterpriseModel;

/**
 * 投标接口
 *
 * @packaged default
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 * @userlock
 **/
class Bid extends AppBaseAction
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
            'id' => array(
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
            'coupon' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'source_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
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
            'discount_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'euid' => array(
                'filter' => 'string',
            ),
            'query_coupon' => array(
                'filter' => 'int',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (bccomp($data['money'], 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if($user['idcardpassed'] == 3){
            $this->setErr('ERR_IDENTITY_NO_VERIFY', '认证信息提交成功，网信将在3个工作日内完成信息审核。审核结果将以短信、站内信或者电子邮件等方式通知您。');
            return false;
        }

        //如果未绑定手机
        if(intval($user['mobilepassed'])==0 || intval($user['idcardpassed'])!=1 || !$user['real_name'] ){
            $this->setErr('ERR_IDENTITY_NO_VERIFY', "投资前需要验证身份，请先登录".app_conf('WXLC_DOMAIN')."完成身份验证");
            return false;
        }

        $source_type = isset($data['source_type']) ? $data['source_type'] : \core\dao\DealLoadModel::$SOURCE_TYPE['ios'];
        $site_id = isset($data['site_id']) ? $data['site_id'] : 1;
        $deal_id = $data['id'];
        $money = $data['money'];
        $coupon = isset($data['coupon']) ? strtoupper($data['coupon']) : '';
        $dealColumnsStr = 'id, name, deal_type';
        //$dealInfo = $this->rpc->local('DealService\getManualColumnsVal', array($deal_id, $dealColumnsStr));

        if(deal_belong_current_site($deal_id)){
            $dealInfo = $this->rpc->local('DealService\getDeal', array($deal_id, true));
        }else{
            $dealInfo = null;
        }
        if (!$dealInfo) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        //p2p标仅仅允许投资户投资
        if($this->rpc->local('DealService\isP2pPath', array($dealInfo))){
            if(!$this->rpc->local('UserService\allowAccountLoan', array($user['user_purpose']))){
                $this->setErr('ERR_INVESTMENT_USER_CAN_BID', $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
                return false;
            }
        }

        //分站不走存管的划转逻辑
        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user['id']));
        if (!in_array($site_id, [1, 100])) {
            $remain = bcadd($user['money'], $bonus['money'], 2);
            if (bccomp($remain, $data['money'], 2) < 0) {
                $this->setErr('ERR_USER_MONEY_FAILED');
                return false;
            }
        }

        //强制风险评测
        if($user['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($user['id']), $money, $site_id));
            if($riskData['needForceAssess'] == 1){
                $this->setErr('ERR_UNFINISHED_RISK_ASSESSMENT');
                return false;
            }
            //单笔投资限额
            if($dealInfo['deal_type'] == 0 && $riskData['isLimitInvest'] == 1){
                return $this->setErr('ERR_BEYOND_INVEST_LIMITS');
            }
        }

        RiskServiceFactory::instance(Risk::BC_BID,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check(array('id'=>$user['id'],'user_name'=>$user['user_name'],'mobile'=>$user['mobile'],'money'=>$money),Risk::ASYNC,$dealInfo);
        if ($dealInfo['deal_type'] == 1) {
            $res = $this->rpc->local("DealCompoundService\bid", array($user['id'], $deal_id, $money, $coupon, $source_type, $site_id));
        } else {
            $discountId      = isset($data['discount_id']) ? $data['discount_id'] : '';
            $discountGroupId = isset($data['discount_group_id']) ? $data['discount_group_id'] : '';
            $discountSign    = isset($data['discount_sign']) ? $data['discount_sign'] : '';
            $discountType    = isset($data['discount_type']) ? $data['discount_type'] : '';
            if ($discountId > 0) {
                if ($dealInfo['loantype'] == 7) {
                    $this->setErr('ERR_LOAN_TYPE_ERROR', '投资劵不可用于公益标');
                    return false;
                }

                if ($this->rpc->local('DiscountService\checkConsume', array($discountId))) {
                    $this->setErr('ERR_DISCOUNT_USED', '此投资劵已经使用');
                    return false;
                }
                if ($discountType != 2) {
                    $discountType = 1;
                }
                $params = array('user_id'=> $user['id'], 'deal_id'=> $deal_id, 'discount_id' => $discountId, 'discount_group_id' => $discountGroupId);
                $signStr = $this->rpc->local('DiscountService\getSignature', array($params));
                if ($discountSign != $signStr) {
                    \libs\utils\Monitor::add(DiscountService::SIGN_FAILD);
                    logger::wLog("API_DISCOUNT:". http_build_query($params)."&sign=$discountSign&local=$signStr");
                    $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数错误');
                    return false;
                }
                $checkResult = $this->rpc->local('O2OService\checkDiscountUseRules', array($discountGroupId, $deal_id, $money));
                if (!$checkResult) {
                    \libs\utils\Monitor::add(DiscountService::USE_ERR);
                    $this->setErr('ERR_DISCOUNT_NOT_APPLICABLE', '使用投资劵错误');
                    return false;
                }
            }

            $option = !empty($data['euid']) ? ['euid' => $data['euid']] : [];
            if ($data['query_coupon'] && empty($coupon)) {
                $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($user['id']));
                if ($couponLatest['short_alias']) {
                    $coupon = $couponLatest['short_alias'];
                }
            }

            $res = $this->rpc->local("DealLoadService\bid", array($user['id'], $dealInfo, $money, $coupon, $source_type, $site_id, false, intval($discountId), $discountType, $option));
        }

        if($res['error']){
            $this->setErr('ERR_SYSTEM', $res['msg']);
            return;
        }
        RiskServiceFactory::instance(Risk::BC_BID,Risk::PF_API)->notify();
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
        $apiLog = array(
            'time' => date('Y-m-d H:i:s'),
            'userId' => $user['id'],
            'dealId' => $deal_id,
            'ip' => get_real_ip(),
            'loadId' => $res['load_id'],
            'money' => $data['money'],
            'os' => $os,
            'channel' => $channel,
        );
        logger::wLog("API_BID:".json_encode($apiLog));
        PaymentApi::log("API_BID:".json_encode($apiLog), Logger::INFO);
        $deal = $this->rpc->local("DealService\getDeal", array($deal_id));
        //$coupon_code = $this->rpc->local("CouponService\getOneUserCoupon", array($user['id']));

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
        $bonusTtl = 0; // app端根据这个数字大小来做分享链接展现判断
        $bonusFace = $share_icon;
        $bonusTitle = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $share_title);
        $bonusContent = str_replace('{$BONUS_TTL}', $bonusTtl, $share_content);
        //$bonusFace = get_config_db('API_BONUS_SHARE_FACE', $site_id);
        //$bonusTitle = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], get_config_db('API_BONUS_SHARE_TITLE', $site_id));
        //$bonusContent = str_replace('{$BONUS_TTL}', $bonusTtl, get_config_db('API_BONUS_SHARE_CONTENT', $site_id));
        $bonusContent = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $bonusContent);
        $host = get_config_db('API_BONUS_SHARE_HOST', $site_id);
        $prizeType = 'bonus';
        $prizeList = array();
        $prizeTitle = '';
        $prizeDesc = '';
        // TODO O2O 读取礼物列表 BEGIN {
        if ($deal['deal_type'] != 1) {
            $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            $loadId = $res['load_id'];
            $digObject = new \core\service\DigService('makeLoan', array(
                'id' => $user['id'],
                'loadid' => $loadId,
                'cn' => '',
            ));

            $triggerList = $digObject->getResult();
            $prizeList = $triggerList ? $triggerList['popup'] : array();

            if (empty($prizeList)) {
                if(!empty($triggerList['event'])) {
                    $evt = $triggerList['event'][0];
                    $prizeType = 'h5';
                    $prizeTitle = $evt['title'];
                    $prizeDesc = $evt['desc'];
                    $prizeUrl = $evt['url'];
                }
            }else {
                $prizeDesc = '您也可以在“礼券”中领取';
                $title = urlencode('领取礼券');
                \es_session::set('o2oViewAccess','pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
                if($this->app_version < 345) {
                    //3.4.5版本以下的弹窗逻辑需要兼容下
                    $prizeType = 'o2o';
                    $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $event, $loadId, CouponGroupEnum::CONSUME_TYPE_P2P));
                    $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                } else {
                    if (count($prizeList) > 1) {
                        //多个券组
                        $prizeType = 'o2o';
                        $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $event, $loadId, CouponGroupEnum::CONSUME_TYPE_P2P));
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
                            $group = $groupInfo;
                        }


                        $group['id'] = $groupId;
                        $group['useRules'] = $useRules;
                        $group['storeId'] = $storeId;
                        $group['productName'] = $prizeTitle;

                        // 只有收货，收券, 游戏活动类需要跳转到acquireDetail，其他类型跳转到acquireExchange;大转盘游戏也跳转到acquireDetail保持逻辑一致
                        if (in_array($useRules, CouponGroupEnum::$ONLINE_FORM_USE_RULES)) {
                            $url = urlencode(sprintf(app_conf('O2O_DEAL_DETAIL_URL'), $event, $loadId, $groupId, $token, CouponGroupEnum::CONSUME_TYPE_P2P));
                            $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                        } else {
                            // 直接兑换的，不显示返回按钮，增加关闭按钮
                            $url = urlencode(sprintf(app_conf('O2O_DEAL_EXCHANGE_URL'), $event, $loadId, $groupId, $useRules, $storeId, $token, CouponGroupEnum::CONSUME_TYPE_P2P));
                            $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=false&needrefresh=true&needcloseall=true&title=%s&url=%s', $title, $url);
                        }
                    }
                }
            }

        }
        //通知贷标的不生成红包
        // 生成红包，如果bonusSn !== false则为加密串，可以生成链接
        $bonusSn = '';
        $bonusBidFinished = '';
        if (empty($prizeList) && $deal['deal_type'] != 1) {
            $bonusSn = $this->rpc->local("DealService\makeBonus", array($deal_id, $res['load_id'], $user['id'], $money, $site_id));
            $groupInfo = $this->rpc->local('BonusService\get_bonus_group', array($res['load_id']));
            if (!empty($groupInfo)) {
                $bonusTtl = $groupInfo['count'];
                $bonusBidFinished = app_conf('API_BONUS_SHARE_BID_FINISHED');
            }
        }
        $bonusUrl = $host.'/hongbao/GetHongbao?sn='.$bonusSn; // web端提供

        // } END
        $goodPrice = isset($data['discount_goodprice']) ? $data['discount_goodprice'] : '';
        $goodPrice = base64_decode(str_pad(strtr($goodPrice, '-_', '+/'), strlen($goodPrice) % 4, '=', STR_PAD_RIGHT));
        $goodTitle = '';
        if ($discountId > 0) {
            $goodTitle = ($discountType == 1) ? '返现劵' : '加息劵';
        }
        if (isset($data['fromOptimize'])) {
            //如果是从投资券优化弹窗投资成功，增加监控统计
            \libs\utils\Monitor::add('BID_DISCOUNT_OPTIMIZE');
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
        if($this->rpc->local("VipService\isShowVip",array($user['id']), VipEnum::VIP_SERVICE_DIR) && ($this->app_version >= 472)){
            $isShowVip = 1;
            $expectVipRebate = $this->rpc->local("VipService\getExpectVipRebate",array($user['id'], $res['load_id']), VipEnum::VIP_SERVICE_DIR);
            $vipSourceType = ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) ? VipEnum::VIP_SOURCE_P2P : VipEnum::VIP_SOURCE_ZHUANXIANG;
            $sourceAmount = O2OUtils::getAnnualizedAmountByDealIdAndAmount($deal['id'], $money);
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
            'prize_desc' => $prizeDesc,
            'o2oCouponCount' => count($prizeList),
            'o2oCouponList' => $group,
            'o2oAction' => $event,
            'o2oCouponTitle' => '领取礼券',
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
            'bid_money' => $money,
            'recommendation'=> "推荐一个投资项目：{$deal['name']}，年化收益{$deal['rate']}，投资时用我的优惠码{$senderUserCoupon['short_alias']} 还可以返利，挺靠谱的，可以看看。http://".app_conf('WXLC_DOMAIN')."/d/".Aes::encryptForDeal($deal['id'])."?cn={$senderUserCoupon['short_alias']}",
            'freePaymentUrl' => $freePaymentUrl,
            'reportStatus' => $deal['report_status'],
            'isShowVip' => $isShowVip,
            'expectVipRebate' => $isShowVip == 1 ? $expectVipRebate['rebateDesc'] : '',
            // vip经验值字段
            'vipPoint' => $isShowVip == 1 ? $vipPoint : '',

            //是否首投
            'isFirstInvest' => intval($isFirstInvest),
            'succText' => $isFirstInvest ? '恭喜您已经完成首次投资!' : '投资成功!',
        );
    }

} // END class Bid extends AppBaseAction
