<?php
namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * Redeem
 * 赎回操作
 *
 * @uses BaseAction
 * @package default
 */
class Redeem extends AppBaseAction
{
    protected $useSession = true;
    public function init()
    {
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'id' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
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
        $loadId = $data['id'];
        $siteId = isset($data['site_id']) ? $data['site_id'] : 1;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $dealLoad = $this->rpc->local('DealLoadService\getDealLoadDetail', array($loadId, false));
        if (empty($dealLoad)) {
            $this->setErr('ERR_MANUAL_REASON', '投资标不存在');
            return false;
        }
        $ret = $this->rpc->local('DealCompoundService\redeem', array($loadId, $user['id']));
        if(!$ret){
            $this->setErr('ERR_MANUAL_REASON', '正在放款中，请稍候重试');
            return false;
        }
        $result = '';
        $digObject = new \core\service\DigService('redeem', array(
            'id' => $user['id'],
            'cn' => $dealLoad['short_alias'],
            'loadId' => $loadId,
            'money' => $dealLoad['money'],
        ));
        $result = $digObject->getResult();
        // 生成红包，如果bonusSn !== false则为加密串，可以生成链接
        $bonusSn = '';
        $bonusTtl = 0; // app端根据这个数字大小来做分享链接展现判断
        $bonusBidFinished = '';
        $periodDay = $this->rpc->local('DealCompoundService\getPeriodDay', array($loadId));
        // TODO O2O 读取礼物列表 BEGIN {
        $prizeType = 'bonus';
        $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
        $userid = $user['id'];
        $prizeUrl = '';
        $title = urlencode('领取礼券');
        if (!empty($result)) {
            \es_session::set('o2oViewAccess','pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
            if($this->app_version <= 345) {
                //3.4.5版本及以下的通知贷款赎回没有弹窗，需要兼容下
                $prizeType = 'o2o';
                $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $event, $loadId, CouponGroupEnum::CONSUME_TYPE_P2P));
                $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
            } else {
                if (count($result) > 1) {
                    //HACK 当前版本的通知贷赎回不进行流程优化，还走老流程；下版本优化时只需要
                    //多个券组
                    $prizeType = 'o2o';
                    $url = urlencode(sprintf(app_conf('O2O_DEAL_OPEN_URL'), $event, $loadId, CouponGroupEnum::CONSUME_TYPE_P2P));
                    $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                } else {
                    //单个礼券,需要获取礼券详情，根据使用规则封装url
                    $prizeType = 'acquire';
                    $token = $data['token'];
                    foreach ($result as $groupInfo) {
                        $prizeTitle = $groupInfo['productName'];
                        $groupId = $groupInfo['id'];
                        $useRules = $groupInfo['useRules'];
                    }

                    // 只有收货，收券, 游戏活动类需要跳转到acquireDetail，其他类型跳转到acquireExchange;大转盘游戏也跳转到acquireDetail保持逻辑一致
                    if (in_array($useRules, CouponGroupEnum::$ONLINE_FORM_USE_RULES)) {
                        $url = urlencode(sprintf(app_conf('O2O_DEAL_DETAIL_URL'), $event, $loadId, $groupId, $token,
                            CouponGroupEnum::CONSUME_TYPE_P2P));

                        $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                    } else {
                        //直接兑换的，不显示返回按钮，增加关闭按钮
                        $url = urlencode(sprintf(app_conf('O2O_DEAL_EXCHANGE_URL'), $event, $loadId, $groupId,
                            $useRules, 0,$token, CouponGroupEnum::CONSUME_TYPE_P2P));

                        $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=false&needrefresh=true&needcloseall=true&title=%s&url=%s', $title, $url);
                    }
                }
            }
        }
        else {
            // 没有O2O兑换券， 发红包
            if($periodDay !== false && $periodDay >= intval(app_conf('BONUS_LGL_SEND_LIMIT_DAYS'))){
                $bonusSn = $this->rpc->local("DealService\makeBonus", array($dealLoad['deal_id'], $loadId, $user['id'], $dealLoad['money'], $dealLoad['site_id']));
                $groupInfo = $this->rpc->local('BonusService\get_bonus_group', array($loadId));
                if (!empty($groupInfo)) {
                    $bonusTtl = $groupInfo['count'];
                    $bonusBidFinished = app_conf('API_BONUS_SHARE_BID_FINISHED');
                }
            }
        }
        // } END
        // 分享红包链接扩展信息
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($user['id'])), 10);
        $bonusFace = get_config_db('API_BONUS_SHARE_FACE', $siteId);
        $bonusTitle = get_config_db('API_BONUS_SHARE_TITLE', $siteId);
        $bonusTitle = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], get_config_db('API_BONUS_SHARE_TITLE', $siteId));
        $bonusContent = str_replace('{$BONUS_TTL}', $bonusTtl, get_config_db('API_BONUS_SHARE_CONTENT', $siteId));
        $bonusContent = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $bonusContent);
        $host = get_config_db('API_BONUS_SHARE_HOST', $siteId);
        $bonusUrl = $host.'/hongbao/GetHongbao?sn='.$bonusSn; // web端提供

        $this->json_data = array(
            'prize_type' => $prizeType,
            'prize_url' => $prizeUrl,
            'prize_title' => $prizeTitle,
            'bonus_ttl' => $bonusTtl,
            'bonus_url' => $bonusUrl,
            'bonus_face' => $bonusFace,
            'bonus_title' => $bonusTitle,
            'bonus_content' => $bonusContent,
            'bonus_bid_finished' => $bonusBidFinished,
            'msg' => '赎回成功',
        );
        \libs\utils\PaymentApi::log('通知贷赎回结果'.var_export($this->json_data,true));
    }
}
