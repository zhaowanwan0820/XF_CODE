<?php
/**
 * @wap站随心约首页配置信息(入口)
 * @确定wap站首页的随心约入口是否开放、随心约的可投资类型
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestDealBid;
use libs\utils\Aes;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\dao\ApiConfModel;

class ReserveConf extends BaseAction
{
    private $_ReservaTags = 'YUE';  //从随心约配置表查找

    private $_ReserveApiKey = 'param_dqb'; //api_conf配置名

    private $_ReserveSwitch = 'feature_sxy'; //随心约开关

    private $_WapReserveSwitchName = 'wap_reserve_switch'; //wap站随心约开关配置值(临时开关,最高优先级)

    private $_ReserveIndexAccoutSwitch = false; //首页、我的账户页随心约开关,不对随心约卡片起作用

    public static $rateText = array(
            0 => '年化借款利率',
            1 => '预期年化',
            2 => '预期年化',
            3 => '预期年化',
        );

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "type" => array("filter" => "string"),
            "need_bid" => array("filter" => "int"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if(!$this->form->validate()){
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        /*
        $result = $this->_isFenZhanUserBid($data); //分站且用户已经首投了,才进行下续判断
        if (!$result) {
            $this->json_data = array('conf'=>array('wap_close'=>1));
            return true;
        }
        */

        $type = ($data['type'] == 1 || $data['type'] == 2) ? $data['type'] : 2;
        $conf = $this->getApiConfig($this->_WapReserveSwitchName); //wap站随心约开关(临时开关,最高优先级)
        if($conf['value'] == 'false'){
            $this->json_data = array('conf'=>array('wap_close'=>1));
            return true;
        }

        $conf = $this->getApiConfig($this->_ReserveSwitch); //随心约总开关,只有value为false才是开关关闭(于app端共用)
        if($conf['value'] == 'false'){
            $this->json_data = array('conf'=>array('close'=>1));
            return true;
        }

        /*我的账户页随心约打开或关闭*/
        $configData = $this->getApiConfig($this->_ReserveApiKey);
        if($type == 1){
            if($this->_ReserveIndexAccoutSwitch == true){
                $this->json_data = array('conf'=>array('account_close'=>1));
            }else{
                $this->json_data = $configData;
            }
            return true;
        }

        //首页随心约打开/关闭开关，不针对随心约卡片
        $resData['conf'] = $this->rpc->local('ReservationConfService\getReserveSysConf', array($this->_ReservaTags)); //随心约基本配置
        $resData['conf']['indexUrl'] = $configData['index_url'];
        if($this->_ReserveIndexAccoutSwitch == true){
            $resData['conf']['index_close'] = 1;
        }

        //随心约类型列表
        $resData['list'] = array();
        $resData['list'] = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ReservationCardService\getReserveCardList', array(1000000)), 30, false, true);
        if(!empty($resData['list']['list'])){
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            $url = $http . $_SERVER['HTTP_HOST'];
            $list = $resData['list']['list'];
            foreach ($list as $key=>$val){
                $list[$key]['reserCommitUrl'] = sprintf("/reserveIndex/reserve?investLine=%s&investUnit=%s", $val['investLine'], $val['unitType']);
                $list[$key]['reserveDetailsUrl'] = sprintf("/reserveIndex/details?investLine=%s&investUnit=%s", $val['investLine'], $val['unitType']);
                $list[$key]['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
            }

            $resData['list']['list'] = $list;
        }

        $this->json_data = $resData;
        return true;
    }

    private function _isFenZhanUserBid($data) {
        if (isset($data['need_bid'])) { //分站需要传此标识
            return true;
        }

        $user_info = $this->getUserByAccessToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $service = new \core\service\UserTagService();
        $result  = $service->getTagsViaSlave($user_info->userId);
        if (empty($result)) {
            return false;
        }

        foreach ($result as $tagInfo) {
            $tagName = strtoupper($tagInfo['const_name']);
            if (in_array($tagName, array('BID_ONE', 'BID_MORE'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @获取随心约api配置信息
     * @param string $keyName
     * @param array
     */
    public function getApiConfig($keyName)
    {
        $condition = "is_effect=1 and name='".$keyName."'";
        if($checkinConfObj = ApiConfModel::instance()->findByViaSlave($condition)){
            $checkinConf = $checkinConfObj->getRow();
        }

        if(in_array($keyName, array($this->_ReserveSwitch, $this->_WapReserveSwitchName))) return $checkinConf;

        $configData = json_decode($checkinConf['value'], true);
        if(empty($configData)){
            $this->_ReserveIndexAccoutSwitch = true;
            return;
        }

        if($configData['accounttitle'] && ($configData['wapurl']) || $configData['wapindexurl']){
            return array('title'=>$configData['accounttitle'], 'url'=>$configData['wapurl'], 'index_url'=>$configData['wapindexurl']);
        }

        $this->_ReserveIndexAccoutSwitch = true;
    }
}

