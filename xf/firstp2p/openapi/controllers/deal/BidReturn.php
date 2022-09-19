<?php
/**
 * Bid Return 投资存管回调
 **/

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\DealModel;
use core\service\O2OService;
use core\service\DiscountService;
use core\service\oto\O2OUtils;
use libs\utils\Aes;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\utils\Risk;


class BidReturn extends BaseAction
{
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array(
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

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $user_info = $this->getUserByAccessToken();
        $user['id'] = $user_info->userId;
        if (!$user) {
            $this->setErr('ERR_TOKEN_ERROR');
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

        $apiLog = array(
            'time' => date('Y-m-d H:i:s'),
            'userId' => $user['id'],
            'orderId' => $orderId,
            'ip' => get_real_ip(),
            'loadId' => $res['load_id'],
            'money' => $bidMoney,
        );
        logger::wLog("OPENAPI_BID:".json_encode($apiLog));
        PaymentApi::log("OPENAPI_BID:".json_encode($apiLog), Logger::INFO);

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
        $prizeType = 'bonus';
        $prizeList = array();
        $prizeTitle = '';
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
            if (!empty($prizeList)) {
                if (count($prizeList) > 1) {
                    //多个券组
                    $prizeType = 'o2o';
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


        $this->json_data = array(
            //临时增加折扣券id的传入和返回
            'goodPrice' => $goodPrice ? $goodPrice : '',
            'goodTitle' => $goodTitle,
            // TODO O2OMock 投资选择礼品
            'prize_type' => $prizeType,
            'prize_title' => $prizeTitle,
            'prize_count' => count($prizeList),
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
            'reportStatus' => $deal['report_status'],
        );
    }

} // END class Bid extends AppBaseAction
