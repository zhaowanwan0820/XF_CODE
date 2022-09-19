<?php

/**
 * Coupon.php opanapi
 * @abstract  我的邀请码openapi接口
 * @author yutao <yutao@ucfgroup.com>
 */

namespace openapi\controllers\account;

use core\service\CouponLogService;
use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\lib\Tools;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\OpenService;
use core\service\CouponBindService;
use NCFGroup\Protos\Ptp\ResponseUserCouponInfo;

/**
 * 我的优惠码接口
 *
 * Class Coupon
 */
class CouponPage extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "page_num"    => array("filter" => "reg", "option" => array("regexp" => '/^\d+$/', 'optional' => true), "message" => "page_num need int type"),
            "page_size"   => array("filter" => "reg", "option" => array("regexp" => '/^\d+$/', 'optional' => true), "message" => "page_size need int type"),
            "invitee_id"  => array("filter" => "string", "option" => array('optional' => true)),
            "type"        => array("filter" => "string", "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        if (!empty($data['page_num'])) {
            $userInfo->setPageNum($data['page_num']);
        }

        if (!empty($data['page_size'])) {
            $userInfo->setPageSize($data['page_size']);
        }

        if (!empty($data['type'])) {
            if (!in_array($data['type'], [CouponLogService::MODULE_TYPE_P2P, CouponLogService::MODULE_TYPE_REG, CouponLogService::MODULE_TYPE_DUOTOU]))
                $data['type'] = CouponLogService::MODULE_TYPE_P2P;
            $userInfo->setType($data['type']);
        }

        // 被邀请人ID
        $invitee_id = !empty($data['invitee_id']) ? Tools::decryptID($data['invitee_id']) : '';
        if(!empty($invitee_id)){
            $userInfo->setInviteeId(intval($invitee_id));
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCoupon',
            'method' => 'getUserCouponV2',
            'args' => $userInfo
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user coupon failed";
            return false;
        }
        //根据用户ID，邀请码，获取此邀请码的分站信息，可以为空
        $couponBindSrv = new CouponBindService();
        $bindInfo = $couponBindSrv->getByUserId($userInfo->getUserId());
        //检查邀请人的邀请码是否在配置中
        $showSiteCouponArray = explode(',', trim(app_conf('SHOW_SITE_COUPON')));
        if (!empty($bindInfo) && !empty($bindInfo['short_alias']) && in_array($bindInfo['short_alias'], $showSiteCouponArray)){
            //根据邀请码获取AppInfo信息
            $inviteCode = $bindInfo['short_alias'];
            $openSrv = new OpenService();
            $appInfo = $openSrv->getAppInfoByInviteCode($inviteCode);
            if (!empty($appInfo)){
                $response->appInfo = $appInfo;
            }
        }
        $this->json_data = $response->toArray();
    }
}
