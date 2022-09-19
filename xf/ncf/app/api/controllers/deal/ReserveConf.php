<?php
/**
 * @wap站随心约首页配置信息(入口)
 * @确定wap站首页的随心约入口是否开放、随心约的可投资类型
 *
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestDealBid;
use libs\utils\Aes;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\dao\conf\ApiConfModel;
use core\service\reserve\ReservationConfService;
use core\service\reserve\ReservationEntraService;
use core\enum\DealEnum;
use core\enum\ReserveEntraEnum;
use core\enum\ReserveConfEnum;

class ReserveConf extends AppBaseAction
{
    protected $needAuth = false;

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
            "token" => array("filter" => "string"),
            "type" => array("filter" => "string"),
            "need_bid" => array("filter" => "int"),
        );

        if(!$this->form->validate()){
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        /*$result = $this->_isFenZhanUserBid($data); //分站且用户已经首投了,才进行下续判断
        if (!$result) {
            $this->json_data = array('conf'=>array('wap_close'=>1));
            return true;
        }*/

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

        $rConfService = new ReservationConfService();
        //首页随心约打开/关闭开关，不针对随心约卡片
        $resData['conf'] = $rConfService->getReserveSysConf($this->_ReservaTags); //随心约基本配置
        $resData['conf']['indexUrl'] = $configData['index_url'];
        if($this->_ReserveIndexAccoutSwitch == true){
            $resData['conf']['index_close'] = 1;
        }

        //banner图片
        $reserveNotice = $rConfService->getReserveInfoByType(ReserveConfEnum::TYPE_NOTICE_P2P);
        $resData['conf']['bannerUrl'] = !empty($reserveNotice['banner_uri']) ? $reserveNotice['banner_uri'] : '';
        $resData['conf']['reserveListUrl'] = '/deal/reserveMy';

        //随心约类型列表
        $resData['list'] = array();
        $entraService = new ReservationEntraService();
        $resData['list'] = $entraService->getReserveEntraDetailList(ReserveEntraEnum::STATUS_VALID, 1000, 0, $userInfo);
        if(!empty($resData['list']['list'])){
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            $url = $http . $_SERVER['HTTP_HOST'];
            $list = $resData['list']['list'];
            foreach ($list as $key=>$val){
                $list[$key]['appointUrl'] = sprintf("/deal/reserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $list[$key]['detailUrl'] = sprintf("/deal/reserveDetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $list[$key]['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
                $list[$key]['rate'] = substr($val['rate'],0,-1); //去掉百分号
            }

            $resData['list'] = $list;
        }

        $this->json_data = $resData;
        return true;
    }

    private function _isFenZhanUserBid() {
        if (isset($data['need_bid'])) { //分站需要传此标识
            return true;
        }

        $user_info = $this->getUserByToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $service = new \core\service\UserTagService();
        $result  = $service->getTagsViaSlave($user_info['id']);
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

