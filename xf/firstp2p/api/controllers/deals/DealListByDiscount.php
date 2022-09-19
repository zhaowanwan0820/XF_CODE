<?php

/**
 * 优惠券筛选投资列表
 * @date 2017-07-19
 * @author yanjun <yanjun5@ucfgroup.com>
 * */
namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\GoldService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;


class DealListByDiscount extends AppBaseAction {


    public function init() {
        parent::init ();
        $this->form = new Form ();
        $this->form->rules = array (
                'token' => array('filter' => 'required', 'message' => 'token is required'),
                "discountId" => array ("filter" => "required", "message" => "discountId is required"),
                "pageNum" => array ("filter" => "int"),
                "pageSize" => array ("filter" => "int"),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty ( $userInfo )) {
            $this->setErr ('ERR_GET_USER_FAIL' );
            return false;
        }
        //获取优惠券信息
        $discount = $this->rpc->local('DealListService\getDiscountInfo', array(intval($data['discountId'])));
        if(empty($discount) || $discount['ownerUserId'] != $userInfo['id']){
            $this->setErr ('ERR_PARAMS_ERROR', '优惠券不存在');
            return false;
        }
        if($discount['status'] != 1 || $discount['useStartTime'] > time() || $discount['useEndTime'] < time()){
            $this->setErr ('ERR_PARAMS_ERROR', '优惠券已过期或者已使用');
            return false;
        }

        $pageNum = isset($data['pageNum']) ? $data['pageNum'] : 1;
        $pageSize = $data['pageSize'] ? $data['pageSize'] : 20;

        $bidAmount = $discount['bidAmount'];//优惠券价格
        $bidDayLimit = $discount['bidDayLimit'];//优惠券期限
        $discountType = $discount['discountType'];
        $discountGroupId = $discount['discountGroupId'];


        //账户余额
        $bonus = $this->rpc->local('BonusService\get_useable_money', array($userInfo['id']));//红包金额
        $userMoney = bcadd($userInfo['money'], $bonus['money'],2);
        //存管账户
        $supervisionService = new \core\service\SupervisionService();
        $svInfo = $supervisionService->svInfo($userInfo['id']);
        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            $userMoney  = bcadd($userInfo['money'], $svInfo['svBalance'],2);
        }
        // 1:ios 2:android
        $platform = $this->getOs();

        $dealsList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealListService\getDealListByDiscount', array($userInfo, $bidAmount, $bidDayLimit,$discountGroupId,$discountType, $this->app_version, $platform, $pageNum, $pageSize)), 60);

        // format data for app
        $dealsListFormat = array();
        foreach ($dealsList as $deal) {
            if ($deal['consumeType'] == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
                $dealsListFormat[] = $this->handleDuotou($deal);
            } else {
                $dealsListFormat[] = $this->handleDeal($deal);
            }
        }

        if ($discountType == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
            $goldService = new GoldService();
            $goldPrice = $goldService->getGoldPrice();
            $rechargeMoney = ($bidAmount * $goldPrice['data']['gold_price']) + ($bidAmount * $buyerFee) - $userMoney; //需要充值的金额
        } else {
            $rechargeMoney = $bidAmount - $userMoney;//需要充值的金额
        }

        $result = array(
            'list' => $dealsListFormat,
            'discount' => $discount,
            'rechargeMoney' => number_format($rechargeMoney, 2),
        );

        $this->json_data = $result;
    }

    // TODO 数据格式化移到控制器 service中供o2o使用的数据不使用格式化函数封装
    private function handleDeal($dealInfo) {

        $ret['productID'] = $dealInfo['id'];
        $ret['type'] = $dealInfo['type_match_row'];

        // JIRA#3271 JIRA#3844 平台名称变更 by fanjingwen
        // 规则：  1、新逻辑：有product_class，显示标的全名；
        //        2、旧逻辑：[1.无标名前缀，显示标名全程；][2.有标名前缀，显示'前缀+项目名']
        if (!empty($dealInfo['product_class']) || empty($dealInfo['deal_name_prefix'])) {
            $ret['title'] = $dealInfo['old_name'];
        } else {
            $ret['title'] = $dealInfo['deal_name_prefix'] . $dealInfo['project_name'];
        }

        $ret['timelimit'] = ($dealInfo['deal_type'] == 1 ? ($dealInfo['lock_period'] + $dealInfo['redemption_period']) . '~' : '') . $dealInfo['repay_time'] . ($dealInfo['loantype'] == 5 ? "天" : "个月");
        $ret['total'] = $dealInfo['borrow_amount_wan_int'];
        $ret['avaliable'] = $dealInfo['need_money_detail'];
        $ret['mini'] = $dealInfo['min_loan_money_format'];
        $ret['repayment'] = $dealInfo['deal_type'] == 1 ? '提前' . $dealInfo['redemption_period'] . '天申赎' : $dealInfo['loantype_name'];
        $ret['loantype'] = $dealInfo['loantype'];
        $ret['stats'] = $dealInfo['deal_status'];
        $ret['crowd_str'] = $dealInfo['crowd_str'];
        $ret['deal_crowd'] = $dealInfo['deal_crowd'];
        $ret['start_loan_time'] = isset($dealInfo['start_loan_time_format']) ? $dealInfo['start_loan_time_format'] : "";
        $ret['income_base_rate'] = $dealInfo['income_base_rate'];
        $ret['income_ext_rate'] = $dealInfo['income_ext_rate'];

        $ret['rate'] = $dealInfo['income_total_show_rate'];
        // jira:4080 显示的时候只显示基本利率app 时间base_rate + ext_rate 加起来了 app端改的话需要发版，所以在api这里进行修改
        $ret['income_ext_rate'] = 0;

        if (in_array($dealInfo['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
            $ret['money_loan'] = number_format($dealInfo['min_loan_money'], 2, ".", "");
        } else {
            $ret['money_loan'] = number_format($dealInfo['need_money_decimal'], 2, ".", "");
        }
        $ret['daren'] = ($dealInfo['min_loan_total_count'] > 0 || $dealInfo['min_loan_total_amount'] > 0) ? 1 : 0;

        $dealTag = explode(',', $dealInfo['deal_tag_name']);
        $i_tag = 0;
        foreach($dealTag as $tag){
            if($i_tag){
                $ret['deal_tag_name'.$i_tag] = $tag;
            }else{
                $ret['deal_tag_name'] = $tag;
            }
            $i_tag++;
        }


        $ret['deal_type'] = $dealInfo['deal_type'];
        $ret['product_name'] = $dealInfo['product_name'];
        return $ret;
    }

    /**
     * 适配智多鑫
     * @param $dealInfo array
     * @return mixed
     * @date 2017-11-13
     * @author sunxuefeng@ucfgroup.com
     */
    private function handleDuotou($dealInfo) {
        $dealInfo['productID'] = $dealInfo['id'];
        $dealInfo['rate_year_tag'] = "预期年化";

        $duration['t1'] = "期限";
        // 锁定期为1的特殊处理
        if ($dealInfo['lock_day'] == 1) {
            $duration['t2'] = "灵活转让";
        } else {
            $duration['t2'] = $dealInfo['lock_day'];
            $duration['t3'] = "天可转让";
        }
        $dealInfo['duration'] = $duration;
        $dealInfo['consumeType'] = CouponGroupEnum::CONSUME_TYPE_DUOTOU;
        $dealInfo['consume_type'] = 'duotou';
        return $dealInfo;
    }
}
