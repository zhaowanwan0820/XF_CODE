<?php
/**
 * 获取conf系统配置信息
 * @author yanjun <yanjun5@ucfgroup.com>
 */
namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;

class GetConf extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "string", 'option' => array('optional' => true)),
            "site_id" => array("filter" => "int", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        //新手专区是否显示
        $result['isShowNewUserCenter'] = $this->rpc->local('NewUserPageService\isNewUserSwitchOpen', array());
        $result['isShowDealUser'] = 0;
        if(!empty($data['token'])){
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            //存管标的是否显示
            $p2pIsDisplay = false;
            $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
            $svInfo = $this->rpc->local('SupervisionService\svInfo', array($userId));
            if (!empty($svInfo['status']) && $this->app_version >= 450) {
                $p2pIsDisplay = true;
            }
            $site_id = empty($data['site_id']) ? 0 : $data['site_id'];
            // 标定制用户
           $isShowDealUser = $this->rpc->local('DealCustomUserService\checkIsShowUser', array($userId,false,true,$site_id,array(),false,$p2pIsDisplay));
           $result['isShowDealUser'] = empty($isShowDealUser) ? 0 : 1;
            // $result['isShowDealUser'] = 0;

            if($result['isShowNewUserCenter'] == 1){
                $result['isShowNewUserCenter'] = $this->rpc->local('NewUserPageService\isNewUser', array($userInfo['id'], $userInfo['create_time'])) ? 1 : 0;
            }
        }

        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');
        $result['wxAccountConfig'] = $accountInfo[0];
        $result['p2pAccountConfig'] = $accountInfo[1];
        $result['country_code'] = array_values($GLOBALS['dict']['MOBILE_CODE']);
        $this->json_data = $result;

    }
}
