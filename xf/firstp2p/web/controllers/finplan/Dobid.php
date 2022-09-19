<?php
/**
 * DoBid class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace web\controllers\finplan;

use libs\web\Form;
use libs\web\Url;
use libs\utils\Site;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use web\controllers\BaseAction;
use app\models\service\LoanType;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Common\Library\Idworker;

/**
 * 执行投资操作
 * @userlock
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class DoBid extends BaseAction {

    public function init() {
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->show_tips("系统维护中，请稍后再试！","系统维护");
            exit;
        }
        if(!is_duotou_inner_user()) {
            $this->show_tips("没有权限,仅内部员工可以查看智多新内容！","没有权限");
            exit;
        }
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array(
                'filter' => 'int',
                'message' =>"借款不存在"
            ),
            'bid_money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            'coupon_id' => array(//优惠码
                'filter' => 'string',
                'optional' => true,
            ),
            'activity_id' => array(//参与活动Id
                'filter' => 'int',
                'optional' => true,
            ),
            'discountId' => array('filter' => 'int', 'optional' => true),
            'discountType' => array('filter' => 'int', 'optional' => true),
            'discountGroupId' => array('filter' => 'int', 'optional' => true),
            'discountSign' => array('filter' => 'string', 'optional' => true),
        );
        if (!$this->form->validate()) {
            //$this->json_data = array('error'=>$this->form->getErrorMsg());
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }

    }

    public function invoke() {
        $user = $GLOBALS['user_info'];
        if(empty($user)) {
            $this->show_error("未登陆");
        }

        $data = $this->form->data;
        $deal_id = $data['id'];
        $site_id = app_conf("TEMPLATE_ID");
        $project_id = $data['id'];
        $money = $data['bid_money'];
        $coupon_id = $data['coupon_id'];
        $coupon_id = $coupon_id==null ? '' : $coupon_id;
        $activity_id = $data['activity_id'];
        $discount_id = $data['discountId'];
        $discount_type = $data['discountType'];
        $discount_group_id = $data['discountGroupId'];
        $discount_sign = $data['discountSign'];
        //业务日志参数
        $this->businessLog['busi_name'] = '智多新投资';
        $this->businessLog['busi_id'] = $deal_id;
        $this->businessLog['money'] = $money;
        //如果邀请码固定，那么邀请码选择固定的 add wangzhen
        \FP::import("libs.utils.logger");
        $ajax = 1;
        // 验证表单令牌
        if(!bid_check_token()){
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", $ajax);
        }

        //添加强制测评逻辑  绑卡，非企业用户
        if($GLOBALS['user_info']['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($GLOBALS['user_info']['id'])));
            if($riskData != false){
                if($riskData['needForceAssess'] == 1){
                    //需要强制测评
                    return $this->show_error("请您投资前先完成风险承受能力评估", "", $ajax);
                }

                $riskData2 = $this->rpc->local('DealProjectRiskAssessmentService\checkUserProjectRisk', array($GLOBALS['user_info']['id'], 2, true, $riskData));
                if ($riskData2['result'] == false) {
                    $needReAssess = 1;
                    $remainAssessNum = $riskData2['remaining_assess_num'];
                    $riskLevel = $riskData2['user_risk_assessment'];
                    return $this->show_error("请您投资前先完成风险承受能力评估", "", $ajax);
                }
            }

        }

        // 处理投资劵
        if (!empty($discount_id)) {
            $params = array(
                $user['id'],
                $activity_id,
                $discount_id,
                $discount_group_id,
                $discount_sign,
                $money,
                CouponGroupEnum::CONSUME_TYPE_DUOTOU
            );
            $res = $this->rpc->local('O2OService\checkDiscountSignature', $params);
            if ($res === false) {
                $errorMsg = $this->rpc->local('O2OService\getErrorMsg');
                return $this->show_error($errorMsg, "", 1);
            }
        }

        $globalOrderId = Idworker::instance()->getId();
        $globalOrderId = "$globalOrderId"; //防止溢出

        $optionParams=array();
        $optionParams['activityId'] = $activity_id ;
        // 传投资券信息
        $optionParams['discount_id'] = $discount_id;
        $optionParams['discount_type'] = $discount_type;
        $optionParams['discount_group_id'] = $discount_group_id;
        $optionParams['discount_sign'] = $discount_sign;
        $optionParams['couponId'] = $coupon_id;

        try{
            $beforeBid = $this->rpc->local('DtBidService\beforeBid', array($globalOrderId,$user['id'],$project_id,$money,$optionParams));
        }catch (\Exception $ex){
            return $this->show_error($ex->getMessage(), "", 1);
        }
        if($beforeBid['status'] !== \core\service\P2pDealBidService::STATUS_NONE){
            return ajax_return($beforeBid);
        }

        $res = $this->rpc->local('DtBidService\bid', array($user['id'],$project_id, $money,$coupon_id,$optionParams));

        if($res['errCode']){
            return $this->show_error($res['errMsg'], "", $ajax);
        }

        return $this->show_success(
            $GLOBALS['lang']['DEAL_BID_SUCCESS']
            ,''
            ,$ajax
            ,0
            ,Url::gene("finplan", "dtsuccess",array('id' => $res['data']['token']))
            ,array('money'=>$money)
        );//投标成功！
    }
}
