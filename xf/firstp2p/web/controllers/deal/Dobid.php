<?php
/**
 * DoBid class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace web\controllers\deal;

use app\models\service\LoanType;
use core\service\DiscountService;
use core\service\O2OService;
use libs\utils\PaymentApi;
use libs\web\Form;
use libs\web\Url;
use NCFGroup\Common\Library\Idworker;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\dao\EnterpriseModel;
use core\service\DealAgencyService;

/**
 * 执行投资操作
 * @userlock
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class DoBid extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array(
                'filter' => 'string',
                'message' =>"借款不存在"
            ),
            'bid_money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            'coupon_id' => array(
                'filter' => 'string',
                'optional' => true,
            ),
            'discountId' => array('filter' => 'int', 'optional' => true),
            'discountType' => array('filter' => 'int', 'optional' => true),
            'discountGroupId' => array('filter' => 'int', 'optional' => true),
            'discountSign' => array('filter' => 'string', 'optional' => true),
            'discountGoodsPrice' => array('filter' => 'string', 'optional' => true),
            'discountGoodsType' => array('filter' => 'int', 'optional' => true),
         );
        if (!$this->form->validate()) {
            //$this->json_data = array('error'=>$this->form->getErrorMsg());
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }

    }

    public function invoke() {
        $ajax = 1;
        $user = $GLOBALS['user_info'];

        $source_type = 0;
        $site_id = app_conf("TEMPLATE_ID");

        $deal_id = $this->form->data['id'];
        $ec_id = $this->form->data['id'];
        $deal_id = Aes::decryptForDeal($ec_id);

        $money = $this->form->data['bid_money'];
        $coupon = $this->form->data['coupon_id'];
        //业务日志参数
        $this->businessLog['busi_name'] = '投资';
        $this->businessLog['money'] = $money;
        $this->businessLog['busi_id'] = $deal_id;
        // 验证表单令牌
        if(!bid_check_token()){
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", $ajax);
        }
        //如果邀请码固定，那么邀请码选择固定的 add wangzhen
        \FP::import("libs.utils.logger");
        $consumeUserId = $GLOBALS['user_info']['id'];
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($consumeUserId));

        if(empty($couponLatest))
        {
            $couponLatest['is_fixed'] = true;
        }

        \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, __LINE__, $consumeUserId, $deal_id, 'getCouponLatest result',json_encode($couponLatest))));
        if($couponLatest['is_fixed'] === true)
        {
            $coupon = isset($couponLatest['coupon']['short_alias']) ? $couponLatest['coupon']['short_alias']:"";
        }

        // 检查标的是不是通知贷
        $pickList = 0;

        if(deal_belong_current_site($deal_id)){
            $deal = $this->rpc->local("DealService\getDeal", array($deal_id, true));
        }else{
            $deal = null;
        }
        if (empty($deal)) {
            return app_redirect(url("Bid"));
        }

        // 企业融资户交易拦截
        //$isEnterpriseUser = (new \core\service\UserService())->checkEnterpriseUser($user['id']);
        $isEnterpriseSite = is_qiye_site();
        if ($isEnterpriseSite && $user['user_purpose'] == EnterpriseModel::COMPANY_PURPOSE_FINANCE) {
            return $this->show_error("投资功能仅对企业投资户开放", "", 1);
        }
        // 个人列表中的旧企业投资户投资丝路金交标的提示升级
        if ($isEnterpriseSite && substr($user['mobile'], 0, 1) == '6' && $user['mobile_code'] == '86' && DealAgencyService::isXiJinJiaoAgency($deal['jys_id'])) {
            return $this->show_error('投资该项目标的需重新注册，详情请拨打客服电话400-890-9888。', '', 1);
        }

        //强制风险评测
        if($user['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($user['id']), $money));
            if($riskData['needForceAssess'] == 1){
                return $this->show_error("请您投资前先完成风险承受能力评估", "", 1);
            }
            //单笔投资限额
            if($deal['deal_type'] == 0 && $riskData['isLimitInvest'] == 1){
                return $this->show_error("超出单笔最高投资额度", "", 1);
            }
        }

        RiskServiceFactory::instance(Risk::BC_BID)->check(array('id'=>$user['id'],'user_name'=>$user['user_name'],'mobile'=>$user['mobile'],'money'=>$money),Risk::ASYNC,$deal);

        if ($deal['deal_type'] == 1) {
            $res = $this->rpc->local("DealCompoundService\bid", array($user['id'], $deal_id, $money, $coupon, $source_type, $site_id));
        } else {
            // 处理投资劵START
            $data = $this->form->data;
            $discountId = $data['discountId'] ?: 0;
            if ($discountId > 0) {
                $discountGroupId   = $data['discountGroupId'];
                $discountSign      = $data['discountSign'];
                $checkDiscount = (new DiscountService())->validate($user['id'], $discountId, $discountGroupId, $discountSign, $deal_id, $deal['loantype'], $money);
                if ($checkDiscount['errCode'] != 0) {
                    return $this->show_error($checkDiscount['errMsg'], "", 1);
                }
            }
            // 处理投资劵END
            $pickList = 1;
            $discountType = empty($data['discountType']) ? 1 : $data['discountType'];


            /**** 存管逻辑 ******/
            try{
                $userModel = $this->rpc->local("UserService\getUserViaSlave", array($user['id']));
                $globalOrderId = Idworker::instance()->getId();
                $globalOrderId = "$globalOrderId";// 整数到页面会溢出 所以转为字符串
            }catch (\Exception $ex){
                \libs\utils\Logger::error("dobid 获取Idworker异常 errMsg:".$ex->getMessage());
                return $this->show_error("系统繁忙请稍后再试", "", 1);
            }

            try{
                $bidParams = array(
                    'couponId' => $coupon,
                    'sourceType' => $source_type,
                    'siteId' => $site_id,
                    'discountId'=>$discountId,
                    'discountType' => $discountType,
                    'discountGoodsPrice' =>$data['discountGoodsPrice'],
                    'discountGoodsType' => $data['discountGoodsType'],
                );
                $beforeBid = $this->rpc->local('P2pDealBidService\beforeBid', array($globalOrderId,$userModel,$deal,$money,$bidParams));
            }catch (\Exception $ex){
                return $this->show_error($ex->getMessage(), "", 1);
            }

            if($beforeBid['status'] !== \core\service\P2pDealBidService::STATUS_NONE){
                return ajax_return($beforeBid);
            }
            $res = $this->rpc->local("DealLoadService\bid", array($user['id'], $deal, $money, $coupon, $source_type, $site_id, false, $discountId, $discountType,$bidParams));
        }

        if($res['error']){
            // 项目风险和个人评估 错误提示特殊处理
            if (isset($res['remaining_assess_num'])){
                return $this->show_error_data_ajax($res['msg'],'',array('remaining_assess_num' => $res['remaining_assess_num']));
            }
            return $this->show_error($res['msg'], "", $ajax);
        }else{
            $otherParams = array(
                'siteId' => $site_id,
                'discountId' => $discountId,
                'discountGoodsPrice' => isset($data['discountGoodsPrice']) ? $data['discountGoodsPrice'] : '',
                'discountGoodsType' =>  isset($data['discountGoodsType'])  ? $data['discountGoodsType'] : '',
            );
            $jumpData = $this->rpc->local("DealLoadService\getJumpDataAfterBid", array($user, $res['load_id'],$deal_id,$money,$otherParams));
            $jumpData['bm'] = $res['use_bonus_money'];
            return $this->show_success(
                $GLOBALS['lang']['DEAL_BID_SUCCESS']
                ,''
                ,$ajax
                ,0
                ,Url::gene("deal", "success", $jumpData)
                ,array('money' => $money)
            );//投标成功！
        }
    }
}
