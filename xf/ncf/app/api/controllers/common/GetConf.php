<?php
/**
 * 获取conf系统配置信息
 * @author yanjun <yanjun5@ucfgroup.com>
 */
namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\NewUserPageService;
use core\service\supervision\SupervisionService;
use core\service\deal\DealCustomUserService;
use core\service\conf\ApiConfService;

class GetConf extends AppBaseAction {

    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "string", 'option' => array('optional' => true)),
            "site_id" => array("filter" => "int", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;

        //新手专区是否显示
        $newUPService = new NewUserPageService();
        $result['isShowNewUserCenter'] = $newUPService->isNewUserSwitchOpen();
        $result['isShowDealUser'] = 0;
        if(!empty($data['token'])){
            $userInfo = $this->getUserByToken();

            //存管标的是否显示
            $p2pIsDisplay = false;
            $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
            $svInfo = SupervisionService::svInfo($userId);
            if (!empty($svInfo['status'])) {
                $p2pIsDisplay = true;
            }
            $site_id = empty($data['site_id']) ? $this->defaultSiteId : $data['site_id'];
            // 标定制用户
            $dealCUService = new DealCustomUserService();
            $isShowDealUser = $dealCUService->checkIsShowUser($userId,false,true,$site_id,array(),false,$p2pIsDisplay);
            $result['isShowDealUser'] = empty($isShowDealUser) ? 0 : 1;

            if($result['isShowNewUserCenter'] == 1){
                $result['isShowNewUserCenter'] = $newUPService->isNewUser($userInfo['id'], $userInfo['create_time']) ? 1 : 0;
            }
        }

        $apiConfService = new ApiConfService();
        $accountInfo = $apiConfService->getAccountNameConf();
        $result['wxAccountConfig'] = $accountInfo[0];
        $result['p2pAccountConfig'] = $accountInfo[1];
        $result['country_code'] = array_values($GLOBALS['dict']['MOBILE_CODE']);
        $this->json_data = $result;
    }
}
