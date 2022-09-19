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
use core\service\CouponService;
use openapi\lib\Tools;

class Info extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "get_coupon_info" => array("filter" => "int"),
            "site_id" => array('filter' => 'int', 'option' => array('optional' => true)),
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
        $siteId = empty($data['site_id']) ? 1 : $data['site_id'];
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 工资宝特殊逻辑
        if ($this->_client_id == "10e0a47e10db6fe4dcdac5cf") {
            $tmpBankNo = str_replace("*", "", $userInfo->bankNo);
            $idnoPre = substr($userInfo->idno, 0, 4);
            $checkStr = sprintf("name_%s_idno_%s_cardno_%s_mobile_%s_check", $userInfo->realName, $userInfo->idno, $tmpBankNo, $userInfo->mobile);
            $userInfo->infoCheck = md5($checkStr) . $idnoPre;
        }
        // 增加是否打码的配置，以前不变，后续所有接入都需要配置
        if (!empty($this->_mosaic)) {
            foreach ($this->_mosaic as $key => $format) {
                $userInfo->$key = $userInfo->$key ? call_user_func($format, $userInfo->$key) : '无';
            }
        } else {
            // 历史遗留，为了满足某些第三方需要，openapi获取不到手机号的时候要输出无
            $userInfo->mobile = $userInfo->mobile ? $userInfo->mobile : '无';
        }

        $basicInfo = $userInfo->toArray();
        $userService = new \core\service\UserService($userInfo->userId);
        $shortName = $userService->getBankCodeByUid($userInfo->userId);
        $basicInfo['bankCode'] = $shortName;
        // 投资劵开关
        $discountSwitch = (new \core\service\DiscountService())->siteSwitch($this->getSiteId()) ? 1 : 0;
        $basicInfo['discountSwitch'] = $discountSwitch;

        //根据用户是否企业用户,计算会员编号
        $isEnterprise = $userService->isEnterpriseUser() ? 1 : 0;
        $basicInfo['userNumber'] = numTo32($basicInfo['userId'], $isEnterprise);

        if ($data['get_coupon_info']) {
            $couponService = new CouponService();
            $coupon = $couponService->getUserCoupon($basicInfo['userId']);
            $basicInfo['coupon'] = empty($coupon) ? '' : $coupon['short_alias'];
        }

        //存管
        $basicInfo['wxAssets'] = bcsub(str_replace(',', '', $basicInfo['totalExt']), $basicInfo['cgNorepayPrincipal'], 2);
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($basicInfo['userId']));
        $basicInfo['svStatus'] = $svInfo['status'];
        $basicInfo['isSvUser'] = $svInfo['isSvUser'];
        $basicInfo['isActivated'] = empty($svInfo['isActivated']) ? 0 : $svInfo['isActivated'];
        $basicInfo['isWxFreePayment'] = 1;
        if (!empty($svInfo['status'])) {
            $basicInfo['isWxFreePayment'] = in_array($siteId, [1,100]) && ($userInfo->bankNo != '' && $userInfo->bankNo != '无') ? intval($userInfo->isWxFreePayment) : 1;
            $basicInfo['isFreePayment'] = $svInfo['isFreePayment'];
            if($svInfo['isSvUser']){
                $basicInfo['svAssets'] = bcadd($svInfo['svMoney'], $basicInfo['cgNorepayPrincipal'], 2);
                $basicInfo['svBalance'] = isset($svInfo['svBalance']) ? $svInfo['svBalance']: 0;
                $basicInfo['svFreeze'] = isset($svInfo['svFreeze']) ? $svInfo['svFreeze']: 0;
            }
        }

        $basicInfo['open_id'] = Tools::getOpenID($basicInfo['userId']);
        $this->json_data = $basicInfo;
        return true;
    }
}
