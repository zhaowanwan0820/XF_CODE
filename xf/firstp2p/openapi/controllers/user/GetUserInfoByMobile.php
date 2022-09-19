<?php

/**
 * 
 * @abstract 通过oauth_token获取用户信息
 * @author yutao<yutao@ucfgroup.com>
 * @date   2014-11-27
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;
use core\service\CouponService;
use core\service\CouponBindService;

class GetUserInfoByMobile extends BaseAction {

    private $user_item = array("id"=>"", "userId" => "", "userName" => "", "realName" => "", "sex" => "","siteName"=>"", "inviteSiteName"=>"", "status" => "未注册");

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "mobile" => array("filter" => "required", "message" => "mobile is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $clientInfo = $this->getClientIdByAccessToken();
        if (!$clientInfo || $clientInfo['client_id'] !== $data['client_id']) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $mobile = (string) $data['mobile'];
        if(!is_mobile($mobile)){
            $this->errorCode = -99;
            $this->errorMsg = "param set error";
            return false;
        }

        $mobileRequest = new RequestUserMobile();
        $mobileRequest->setMobile($mobile);
        $userResponse = $this->userServiceRequest(array('method'=> 'getUserInfoByMobile', 'args' => $mobileRequest));
        if($userResponse->resCode){
            $this->errorCode = 1;
            $this->errorMsg = "未注册";
            $this->json_data_err = $this->user_item;
            return false;
        }

        $userId = (int) $userResponse->getUserId();
        $groupId= (int) $userResponse->getGroupId();
        $userName = (string) $userResponse->getUserName();
        $realName = (string) $userResponse->getRealName();
        $regSex   = (int) $userResponse->getSex();
        $regTime  = (int) $userResponse->getRegisterTime();
        $regTime  = !empty($regTime) ? strtotime(to_date($regTime)) : 0;

        //用户是否已投资
        $dealRequest = new ProtoUser();
        $dealRequest->setUserId($userId);
        $dealResponse = $this->userServiceRequest(array('method' => 'getUserDealTotal', 'args' =>$dealRequest));
        $deal_total   = (int)$dealResponse->getTotal();
        $status = ($deal_total == 0) ? '注册未投资' : '已投资';

        //获取用户所属网站、注册邀请码
        $userSiteRequest = new ProtoUser();
        $userSiteRequest->setGroupId($groupId);
        $userSiteResponse = $this->userServiceRequest(array('method' => 'getUserGroupInfoById', 'args' => $userSiteRequest));
        $regSiteName = $userSiteResponse->getSiteName();
        $regSiteName = !empty($regSiteName) ? $regSiteName : 0;
        $regAlias = (new CouponService())->getUserCoupon($userId);
        //是否邀请投资
        $investCouponBid = (new CouponBindService())->getByUserId($userId);
        if(!empty($investCouponBid['refer_user_id'])){
            $investCode  = (string) $investCouponBid['short_alias'];
            $referUserId = (int)    $investCouponBid['refer_user_id'];
            $refererRequest = new ProtoUser();
            $refererRequest->setUserId($referUserId);
            $refererResponse = $this->userServiceRequest(array('method' => 'getUserGroup', 'args' =>$refererRequest));
            $inviteSiteName  = $refererResponse->getInviteSiteName();
            $inviteSiteName  = !empty($inviteSiteName) ? $inviteSiteName : 0;
        }else{
            $inviteCode  = $referUserId = $inviteSiteName = 0;
        }

        $regAlias = !empty($regAlias) ? '(' . $regAlias . ')' : '';

        $result = array();
        $result['id']       = numTo32($userId);
        $result['userId']   = $userId;
        $result['regTime']  = $regTime;
        $result['userName'] = $userName;
        $result['realName'] = $realName;
        $result['siteName'] = $regSiteName . $regAlias;
        $result['status']   = $status;
        $result['sex']      = ($regSex == 0) ? '女':'男';
        $result['inviteSiteName'] = !empty($inviteSiteName) ? $inviteSiteName . '(' . $investCode . ')' : '0';
        $result['referUserId'] = $referUserId;
        $result['referUserGroupName'] = !empty($inviteSiteName) ? $inviteSiteName : '';

        $this->json_data = $result;
        return true;
    }

    /**
     * @请求UserService函数
     * @param  array  $params_array
     * @return object
     */
    private function userServiceRequest($params_array)
    {
        $req_res = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => $params_array['method'], 'args' => $params_array['args']));

        return $req_res;
    }

    /**
     * @处理盈华、联合邀请码
     * @param  array coupon_list
     * @return string
     */
    private function changeSpeciailCounpon($coupon_list)
    {
        foreach($coupon_list as $vals){
            if($vals[0] == 'L' || $vals[0] == 'Y') return $vals;
        }

        return $vals[0];
    }
}
