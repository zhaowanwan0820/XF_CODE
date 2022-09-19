<?php
/**
 * 投资确认页
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use libs\payment\supervision\Supervision;
use NCFGroup\Common\Library\Idworker;

class Confirm extends GoldBaseAction {

    const IS_H5 = true;

    //private $_forbid_deal_status;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'code' => array('filter' => 'string', 'option' => array('optional' => true)),
            'dealId' => array('filter' => 'int', 'option' => array('optional' => true)),
            'buyAmount' => array('filter' => 'string', 'option' => array('optional' => true)),
            'discount_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_type' => array(
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
            'discount_bidAmount' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'type' => array('filter' => 'string', 'option' => array('optional' => true)),//type=gold_current时为优金宝
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        //处理优金宝的逻辑
        if (isset($data['type']) && $data['type'] == 'gold_current') {
            $this->gold_current($data);
            return;
        }
        //下面是处理优长今的逻辑
        if (empty($data['dealId'])) {
            $this->setErr('ERR_MANUAL_REASON','dealId不能为空');
        }
        $commonRes = $this->common_handle($data);
        $user = $commonRes['user'];
        //获取标的信息
        $dealId = intval($data['dealId']);
        $res = $this->rpc->local('GoldService\getDealById', array($dealId));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }
        //判断是否满标
        if ($res['data']['dealStatus'] == 2) {
            $this->template = 'api/views/_v46/gold/full.html';
            return false;
        }
        $dealInfo = array();
        $dealInfo['name'] = $res['data']['name'];
        $dealInfo['annual_comp_rate'] = number_format(floorfix($res['data']['rate'],3,6),3);
        $dealInfo['period'] = $res['data']['repayTime'];
        $dealInfo['total_quality'] = number_format($res['data']['borrowAmount'],3);
        $dealInfo['usable_quality'] = number_format(floorfix($res['data']['borrowAmount'] - $res['data']['loadMoney'],3,6),3);
        $dealInfo['buyer_fee'] = number_format(floorfix($res['data']['buyerFee'],2),2);
        $dealInfo['min_loan_amount'] = number_format($res['data']['minLoanMoney'],3);
        $dealInfo['rate'] = $res['data']['rate'];//利率
        $dealInfo['loantype'] = $res['data']['loantype'];
        if ($res['data']['loantype'] == 5) {
            $dealInfo['days'] = $res['data']['repayTime'];
        } else {
            $dealInfo['days'] = $res['data']['repayTime'] * 30;
        }
        //获取优惠码
        $couponRes = $this->coupon_handle($user['id'], $dealId,$dealInfo);
        //获取实时金价
        $goldPrice = $this->rpc->local('GoldService\getGoldPrice', array());
        if (empty($goldPrice) || $goldPrice['errCode'] != 0 || $goldPrice['data']['gold_price'] == 0) {
            $this->template = 'api/views/_v46/gold/gold_price_error.html';
            return false;
        }

        $dealInfo['gold_price'] = floorfix($goldPrice['data']['gold_price'],2);
        $dealInfo['price_rate'] = number_format(app_conf('GOLD_PRICE_RATE')?app_conf('GOLD_PRICE_RATE'):0.5,2);//浮动利率
        $dealInfo['max_price'] = number_format(floorfix($dealInfo['price_rate'] + $dealInfo['gold_price'],2),2);

        //账户类型名称
        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');
        $dealInfo['wxAccountConfig'] = $accountInfo[0];
        $dealInfo['p2pAccountConfig'] = $accountInfo[1];
        //标的剩余可投金额
        $bonus = $commonRes['bonus'];
        $user = $commonRes['user'];
        $avaliable = $res['borrow_amount'] - $res['load_money'];
        $this->get_remain_src($user['money'], $avaliable, $bonus, $dealInfo);

        $dealInfo['buyAmount'] = $data['buyAmount'] ? floatval($data['buyAmount']) : 0;

        if(!empty($data['discount_id']) && empty($data['discount_sign'])){
            $params = array('user_id'=> $user['id'], 'deal_id'=> $dealId, 'discount_id' => $data['discount_id'], 'discount_group_id' => $data['discount_group_id']);
            $data['discount_sign'] = $this->rpc->local('DiscountService\getSignature', array($params));
        }
        $this->tpl->assign('appversion', isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '');
        $this->tpl->assign('dealId', $dealId);
        $this->tpl->assign('dealInfo', $dealInfo);
        $this->tpl->assign('data', $data);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

    /**
     * 优金宝投资确认信息
     * @param unknown $data
     * @return boolean
     */
    public function gold_current($data) {
        $dealInfo = array();
        //活期优金宝相关
        $commonRes = $this->common_handle($data);
        if (empty($commonRes)) {
        return false;
        }
        $user = $commonRes['user'];
        $this->tpl->assign('usertoken', $data['token']);
        //获取标的信息
        $dealInfoRes = $this->rpc->local('GoldService\getDealCurrent', array());
        if (empty($dealInfoRes)) {
        $this->setErr('ERR_MANUAL_REASON','获取标的信息失败，请稍后重试');
        return false;
        }
        //获取最小提金克重
        $minSize=$this->rpc->local('GoldService\getMinSize',array());
        $dealInfo['min_size']=number_format($minSize);

        $dealInfo['annual_comp_rate'] = floorfix($dealInfoRes['rate'],2);//年化收益克重;
        $dealInfo['buyer_fee'] = number_format(floorfix($dealInfoRes['buyerFee'],2),2);//手续费
        //获取实时金价
        $goldPrice = $this->rpc->local('GoldService\getGoldPrice', array());
        if (empty($goldPrice) || $goldPrice['errCode'] != 0 || $goldPrice['data']['gold_price'] == 0) {
            $this->template = 'api/views/_v46/gold/gold_price_error.html';
            return false;
        }
        $dealInfo['min_loan_amount'] = number_format($dealInfoRes['minBuyAmount'],3);//起购克重
        $dealInfo['gold_price'] = floorfix($goldPrice['data']['gold_price'],2);

        $dealInfo['price_rate'] = number_format(app_conf('GOLD_PRICE_RATE')?app_conf('GOLD_PRICE_RATE'):0.5,2);//浮动利率
        $dealInfo['max_price'] = number_format(floorfix($dealInfo['price_rate'] + $dealInfo['gold_price'],2),2);//成交瞬间系统价格不高于此值
        //获取总资产资产
        $borrowAmount = $this->rpc->local('GoldService\getGoldByUserId', array($dealInfoRes['userId']));
        $dealInfo['usable_quality'] = number_format(floorfix($borrowAmount,3,6),3);//标的可投金额
        //获取优惠码
        $couponRes = $this->coupon_handle($user['id'], 0 ,$dealInfo);
        $dealInfo['buyAmount'] = $data['buyAmount'] ? floatval($data['buyAmount']) : 0;
        $dealInfo['rate'] = $dealInfoRes['rate'];//利率

        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');
        $dealInfo['wxAccountConfig'] = $accountInfo[0];
        $dealInfo['p2pAccountConfig'] = $accountInfo[1];

        $this->tpl->assign('appversion', isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '');
        $this->tpl->assign('dealInfo', $dealInfo);
        $this->tpl->assign('data', $data);
    }
    /**
     * 优金宝和优长今公用方法
     * @return boolean|multitype:void
     */
    public function common_handle($data) {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }
        $this->tpl->assign('usertoken', $data['token']);

        //用户投资ticket
        $ticket = Idworker::instance()->getId();
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticketRes = $redis->setex($ticket,'300',$user['id']);
        $this->tpl->assign('ticket', $ticket);
        $result = array();
        $result['user'] = $user;
        //检查是否授权
        $isAuth = $this->rpc->local('GoldService\isAuth', array($user['id']));
        if ($isAuth['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON','获取用户授权信息失败');
            return false;
        }
        if ($isAuth['errCode'] == 0 && !$isAuth['data']) {
            $this->template = 'api/views/_v46/gold/user_authorize.html';
            return false;
        }
        //获取红包金额
        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user['id']));
        $result['bonus'] = $bonus;
        //可用余额
        $remainMoney = $user['money'] + $bonus['money'];
        //存管相关
        $siteId = \libs\utils\Site::getId();
        $cnMoneyTtl = $bonus['money'];
        $userMoneyTtl = $user['money'];
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($user['id']));
        // 黄金走免密投资
        $svInfo['isFreePayment'] = 1;
        $this->tpl->assign('wxMoney', number_format($user['money'], 2));
        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            $userMoneyTtl += $svInfo['svBalance'];
            $this->tpl->assign('totalMoney', number_format(bcadd($remainMoney, $svInfo['svBalance'], 2), 2));

            $cnMoneyTtl += $svInfo['svBalance'];//普惠余额只显示存管和红包余额

            $svInfo['svBalance'] = number_format($svInfo['svBalance'], 2);
        }
        $this->tpl->assign('bonusMoney', $bonus['money']);
        $this->tpl->assign('svInfo', $svInfo);
        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('userId', $user['id']);
        $this->tpl->assign('remainMoney', number_format(floorfix($remainMoney,2),2));
        // 存管服务降级
        $this->tpl->assign('isServiceDown', Supervision::isServiceDown() ? 1 : 0);
        // 投资券开关
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        $o2oGoldDiscountSwitch = intval(get_config_db('O2O_GOLD_DISCOUNT_SWITCH', $siteId));
        $this->tpl->assign('o2oDiscountSwitch', $o2oDiscountSwitch);
        $this->tpl->assign('o2oGoldDiscountSwitch', $o2oGoldDiscountSwitch);

        //会员信息
        $isShowVip = 0;
        if ($this->rpc->local("VipService\isShowVip", array($user['id']), "vip")) {
            $vipInfo = $this->rpc->local("VipService\getVipGrade",array($user['id']), "vip");
            $vip['vipGradeName'] = $vipInfo['name'];
            $vip['raiseInterest'] = $vipInfo['raiseInterest'];
            $isShowVip = $vipInfo['service_grade'] != 0 ? 1 : 0;
            $this->tpl->assign('vipInfo', $vip);
        }
        $this->tpl->assign('isShowVip', $isShowVip);
        return $result;
    }
    /**
     * 优惠吗相关的处理
     * @param unknown $uid
     * @param unknown $dealId
     * @param unknown $dealInfo
     */
    public function coupon_handle($uid,$dealId,& $dealInfo) {
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($uid));
        $dealInfo['couponStr'] = '';
        $dealInfo['couponRemark'] = '';
        $dealInfo['couponIsFixed'] = $couponLatest['is_fixed'] ? 1 : 0;
        if (isset($couponLatest['coupon'])) {
            $coupon = $couponLatest['coupon'];
            if (!empty($coupon)) {
                $tmp = array();
                if ($coupon['rebate_ratio_show'] > 0) {
                    $tmp[] = '+' . $coupon['rebate_ratio_show'] . '%';
                }
                if ($coupon['rebate_amount'] > 0) {
                    $tmp[] = '+' . number_format($coupon['rebate_amount'], 2) . '元';
                }
                $dealInfo['couponProfitStr'] = implode(',', $tmp);
                $dealInfo['rebateRatio'] = $coupon['rebate_ratio_show'];
                $dealInfo['couponStr'] = $coupon['short_alias'];
                $dealInfo['couponRemark'] = "<p>". str_replace(array("\r", "\n"), "", $coupon['remark']) . "</p>";
            }
        }
        $dealInfo['getCouponUrl'] = urlencode(get_http() . get_host() . '/help/coupon/'); // 如何获取优惠码
    }

    /**
     * 用户余额相关处理
     * @param unknown $remain
     * @param unknown $avaliable
     * @param unknown $bonus
     * @param unknown $dealInfo
     */
    public function get_remain_src ($remain,$avaliable,$bonus,&$dealInfo) {
        $remain += $bonus['money'];
        $dealInfo['remainSrc'] = min($remain, $avaliable);
        $dealInfo['isNew'] = 0;//非新手
        $dealInfo['remainSrc'] = number_format($dealInfo['remainSrc'], 2, '.', '');
    }
}
