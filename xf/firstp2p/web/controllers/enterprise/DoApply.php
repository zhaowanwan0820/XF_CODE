<?php

namespace web\controllers\enterprise;

use web\controllers\BaseAction;
use libs\web\Form;
use core\dao\EnterpriseRegisterModel;
use core\dao\EnterpriseModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

/**
 * 企业用户开户申请
 */
class DoApply extends BaseAction {
    public function init() {
        $this->check_login();
    }

    public function invoke() {
        $applyData = $_POST;
        $userId = $GLOBALS['user_info']['id'];
        // 获取代理人手机号用于检查验证码正确性
        if ($applyData['major_type'] == UserAccountEnum::MAJOR_TYPE_SELF) {
            // 代理人手机号
            $mobile = $applyData['major_mobile_self'];
        } else {
            // 代理人手机号
            $mobile = $applyData['major_mobile'];
        }

        // 校验短信验证码
        // 是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($mobile));
            if ($vcode != $applyData['code']) {
                $this->ajaxReturn(-1, '验证码不正确');
                return false;
            }
        }

        // 数据校验
        $checkResultData = $this->rpc->local('EnterpriseService\validateApply', array($userId, $applyData));
        $checkResult = ['code' => 0, 'data' => []];
        if (!empty($checkResultData)) {
            $this->ajaxReturn(-1, $checkResultData);
            return false;
        }

        // 数据提交
        $result = $this->rpc->local('EnterpriseService\apply', array($applyData, $userId));
        $jsonResult = ['code' => 0, 'data' => []];
        if (empty($result)) {
            // 注册失败提示
            $this->ajaxReturn(1, '用户开户申请失败');
            return false;
        }
        $this->ajaxReturn(0, []);
        return false;
    }

    /**
     * 返回ajax数据 企业用户开户使用
     * @param int $code
     * @param array|string $data
     */
    private function ajaxReturn($code, $data) {
        $result['code'] = $code;
        $result['data'] = $data;
        echo json_encode($result);
    }
}
