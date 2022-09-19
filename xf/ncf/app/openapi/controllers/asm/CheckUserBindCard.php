<?php

/**
 * @abstract openapi  检查用户信息
 * @author liuzhenpeng <liuzhenpeng@ucfgroup.com>
 * @date 2015-07-08
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\enum\UserAccountEnum;
use core\enum\AccountAuthEnum;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\service\supervision\SupervisionAccountService;

/**
 * 检查用户信息
 *
 * Class CheckUserBindCard
 * @package openapi\controllers\asm
 */
class CheckUserBindCard extends BaseAction
{
    private $user_class = array(1 => '企业', 2 => '个人');

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "string"),
            "real_name" => array("filter" => "required", "message" => "real_name is required"),
            "idno" => array("filter" => "required", "message" => "idno is required"),
            "user_types" => array("filter" => "int"),
            "mobile" => array("filter" => "string"),
            "caved_bindcard" => array("filter" => "int"),
            "user_name" => array("filter" => "string"),
            "certificate_type" => array("filter" => "string"),//1- 校验西安银行开户信息 2—不校验西安银行开户信息，3-校验certificate_ent_no对应的uid是否开通存管户
            "certificate_ent_no" => array("filter" => "string"),//如果certificate_type是1或2则这个字段代表企业证件号，如果为3则代表是用户ID,多个用户uid以英文逗号隔开
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        $user_types = isset($params['user_types']) ? (($params['user_types'] >2 || $params['user_types'] <1) ? 2 : (int) $params['user_types']) : 2;
        $caved_bindcard = (!empty($params['caved_bindcard']) && $params['caved_bindcard'] == 2) ? true : false;
        $params['user_name'] = isset($params['user_name']) ? htmlspecialchars(trim($params['user_name'])) : '';
        if ($user_types == 1 && empty($params['user_name'])) {
            $this->errorCode = 1;
            $this->errorMsg = "企业用户会员名称必填";
            return false;
        }
        if($user_types == 2 && !is_mobile($params['mobile'])){
            $this->errorCode = 1;
            $this->errorMsg = "个人、代理人校验手机号必填";

            return false;
        }

        $res = $this->checkCreditUser($params, $caved_bindcard);
        while($res < 0){
            switch ($res) {
                case -3 :
                    $this->errorCode = 4;
                    $this->errorMsg  = '该' . $this->user_class[$user_types] . '存管未开户';
                    break;
                case -5 :
                    $this->errorCode = 4;
                    $this->errorMsg  = '该存管户非借款户';
                    break;
                case -1 :
                    $this->errorCode = 2;
                    $this->errorMsg  = '该' . $this->user_class[$user_types] . '不存在';
                    break;
                default:
                    $this->errorCode = 3;
                    $this->errorMsg  = '该' . $this->user_class[$user_types] . '未绑定银行卡';
            }

            return false;
        };
        $result = array();
        $userArr = array(array('userType' => UserAccountEnum::ACCOUNT_FINANCE, 'userId' => $res));
        //判断用户是否开通存管
        if ((intval($params['certificate_type']) == 3) && !empty($params['certificate_ent_no'])) {
            $users = json_decode(base64_decode($params['certificate_ent_no']), true);
            $users = !empty($users) ? $users : array();
            $userArr = array_merge($userArr, $users);
        }
        foreach ($userArr as $user) {
            $result[$user['userId']] = $this->checktAuth($user['userId'], $user['userType']);
        }
        $result['curr_supervision'] = $result[$res]; //借款人的授权
        unset($result[$res]);
        $this->json_data = $result;
    }


    /**
     * 判断该用户是否开通授权
     * @param $userId  用户id
     * @param $accountType  账户类型
     * @return array|bool
     */
    private function checktAuth($userId, $accountType)
    {
        //通过用户id获取账户id
        $accountId = AccountService::getUserAccountId($userId, $accountType);
        if (empty($accountId)) {
            // 对应的账户不存在
            return 2;
        }
        $saService = new SupervisionAccountService();
        $isSupervisionUser = $saService->isSupervisionUser($accountId);
        //如果存管开关打开并且该用户未开户，则返回错误
        if ($isSupervisionUser === false) {
            //  '该用户存管未开户'
            return 3;
        }
        // 如果是咨询方，只校验是否开通存管，不校验是否开通授权
        if ($accountType == UserAccountEnum::ACCOUNT_ADVISORY) {
            return 1;
        }
        // 通过账户id检查 免密缴费和免密还款授权
        $checkInfo = AccountAuthService::checkAccountAuth($accountId, AccountAuthEnum::BIZ_TYPE_BORROW);
        if ($checkInfo) {
            // 借款授权
            return 4;
        }
        // 开通存管账户并且开通授权
        return 1;
    }


}
