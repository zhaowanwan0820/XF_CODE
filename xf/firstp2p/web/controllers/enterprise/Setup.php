<?php

/**
 * 企业用户注册页面
 * @author 文岭<liwenling@ucfgroup.com>
 */

namespace web\controllers\enterprise;

use core\dao\EnterpriseRegisterModel;
use web\controllers\BaseAction;

class Setup extends BaseAction {

    public function init() {
        $this->check_login();
    }

    public function invoke() {
        $user = $GLOBALS ['user_info'];

        $info = $this->rpc->local('EnterpriseService\getInfo', array($user['id']));
        $bank_info = $this->rpc->local('UserBankcardService\getBankcard',array($user['id']));
        if($info['register']['verify_status'] != EnterpriseRegisterModel::VERIFY_STATUS_NO_INFO){
            return app_redirect(url('account'));
        }
        if($info)foreach ($info as $item) {
            if($item)foreach ($item as $key=>$value){
                $this->tpl->assign($key ,$value);
            }
        }

        $credentialsTypes = !empty($GLOBALS['dict']['CREDENTIALS_TYPE'])
            ? $GLOBALS['dict']['CREDENTIALS_TYPE']
            : array('其他企业证件', '营业执照' ,'组织机构代码证' ,'三证合一营业执照');

        $this->tpl->assign('bankcard',$bank_info->bankcard);
        $this->tpl->assign('bank_id',$bank_info->bank_id);
        $this->tpl->assign('card_name',$bank_info->card_name);
        $this->tpl->assign('bankzone',$bank_info->bankzone);
        $this->tpl->assign('credentialsTypes' ,$credentialsTypes);
        $this->tpl->assign('credentials_type_name',isset($credentialsTypes[$info['base']['credentials_type']]) ? $credentialsTypes[$info['base']['credentials_type']] :'');
        if (!empty($_GET['client_id'])) {
            $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
        }
        $clientId = trim($_REQUEST['client_id']);
        $this->tpl->assign('clientId' ,$clientId);
        if($_GET['step'] == '2'){
            $this->tpl->assign('idTypes', $GLOBALS['dict']['ID_TYPE']);
            $this->tpl->assign('legalbody_credentials_type_name', isset($GLOBALS['dict']['ID_TYPE'][$info['base']['legalbody_credentials_type']])
                ?$GLOBALS['dict']['ID_TYPE'][$info['base']['legalbody_credentials_type']]:'');

            $template = 'step2';
        }elseif ($_GET['step'] == '3'){
            $bank_list = $this->rpc->local('BankService\bankList');
            $this->tpl->assign('bank_list',$bank_list);
            $region_str = (int)$bank_info->region_lv2.",".(int)$bank_info->region_lv3.','.(int)$bank_info->region_lv4;
            $regions = $this->rpc->local('DeliveryRegionService\getRegions',array($region_str));
            $this->tpl->assign('bankzone_region0',isset($regions[$bank_info->region_lv2]['name'])
                                                        ?$regions[$bank_info->region_lv2]['name']:'');
            $this->tpl->assign('bankzone_region1',isset($regions[$bank_info->region_lv3]['name'])
                                                        ?$regions[$bank_info->region_lv3]['name']:'');
            $this->tpl->assign('bankzone_region2',isset($regions[$bank_info->region_lv4]['name'])
                                                        ?$regions[$bank_info->region_lv4]['name']:'');
            if($bank_list)foreach ($bank_list as $bank){
                if($bank['id'] == $bank_info->bank_id){
                    $this->tpl->assign('bank_name' ,$bank['name']);
                    break;
                }
            }
            $template = 'step3';
        }elseif ($_GET['step'] == '4'){
            $this->tpl->assign('idTypes', $GLOBALS['dict']['ID_TYPE']);
            $this->tpl->assign('major_condentials_type_name',
                isset($GLOBALS['dict']['ID_TYPE'][$info['contact']['major_condentials_type']])
                    ?$GLOBALS['dict']['ID_TYPE'][$info['contact']['major_condentials_type']]:'');
            $major_contract_region = explode(',',$info['contact']['major_contract_region']);
            $regions = $this->rpc->local('DeliveryRegionService\getRegions',array(trim($info['contact']['major_contract_region'],',')));
            $this->tpl->assign('major_contract_region0',isset($regions[$major_contract_region[1]]['name'])?$regions[$major_contract_region[1]]['name']:'');
            $this->tpl->assign('major_contract_region1',isset($regions[$major_contract_region[2]]['name'])?$regions[$major_contract_region[2]]['name']:'');
            $this->tpl->assign('major_contract_region2',isset($regions[$major_contract_region[3]]['name'])?$regions[$major_contract_region[3]]['name']:'');
            $template = 'step4';
        }elseif ($_GET['step'] == 'suc'){
	        return app_redirect(url('account'));
        }else{
            $registration_region = explode(',',$info['base']['registration_region']);
            $contract_region = explode(',',$info['base']['contract_region']);
            $region_str = $info['base']['registration_region'].','.$info['base']['contract_region'];
            $region_str = trim($region_str,',');
            $regions = $this->rpc->local('DeliveryRegionService\getRegions',array($region_str));
            $this->tpl->assign('registration_address0',isset($regions[$registration_region[1]]['name'])?$regions[$registration_region[1]]['name']:'');
            $this->tpl->assign('registration_address1',isset($regions[$registration_region[2]]['name'])?$regions[$registration_region[2]]['name']:'');
            $this->tpl->assign('registration_address2',isset($regions[$registration_region[3]]['name'])?$regions[$registration_region[3]]['name']:'');
            $this->tpl->assign('contract_address0',isset($regions[$contract_region[1]]['name'])?$regions[$contract_region[1]]['name']:'');
            $this->tpl->assign('contract_address1',isset($regions[$contract_region[2]]['name'])?$regions[$contract_region[2]]['name']:'');
            $this->tpl->assign('contract_address2',isset($regions[$contract_region[3]]['name'])?$regions[$contract_region[3]]['name']:'');
            $template = 'step1';
        }

        return $this->template = 'web/views/v3/user/registercompany_'.$template.'.html';
    }

}

