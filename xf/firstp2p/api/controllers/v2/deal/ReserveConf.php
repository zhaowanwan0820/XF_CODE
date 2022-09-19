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
use core\service\ncfph\ReserveEntraService as PhReserveEntraService;
use core\service\ReservationEntraService;
use libs\utils\Risk;
use core\dao\ApiConfModel;
use core\dao\ReservationEntraModel;
use core\dao\UserReservationModel;
use core\dao\ReservationConfModel;
use core\dao\DealModel;

class ReserveConf extends AppBaseAction
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
            "token" => array("filter" => "string"),
            "type" => array("filter" => "string"),
            "need_bid" => array("filter" => "int"),
            "product_type" => array("filter" => "int"),
        );

        if(!$this->form->validate()){
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $productType = isset($data['product_type']) ? (int) $data['product_type'] : 0; //默认全部  0全部  1网贷  2尊享
        // 根据token获取用户信息
        $userInfo = $this->getUserByTokenForH5($data['token']);
        // 用户ID
        $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;

        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($productType, $userId));
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

        //首页随心约打开/关闭开关，不针对随心约卡片
        $resData['conf'] = $this->rpc->local('ReservationConfService\getReserveSysConf', array($this->_ReservaTags)); //随心约基本配置
        $resData['conf']['indexUrl'] = $configData['index_url'];
        if($this->_ReserveIndexAccoutSwitch == true){
            $resData['conf']['index_close'] = 1;
        }

        //banner图片
        $reserveNotice = $this->rpc->local('ReservationConfService\getReserveInfoByType', array(ReservationConfModel::TYPE_NOTICE));
        $resData['conf']['bannerUrl'] = !empty($reserveNotice['banner_uri']) ? $reserveNotice['banner_uri'] : '';
        $resData['conf']['reserveListUrl'] = '/deal/reserveMy';

        //随心约类型列表
        $resData['list'] = array();
        if (empty($dealTypeList)) {
            $this->json_data = $resData;
            return true;
        }

        //拆分成网贷和专享类型列表
        $p2pDealTypeList = array_intersect([DealModel::DEAL_TYPE_GENERAL], $dealTypeList);
        $exclusiveDealTypeList = array_diff($dealTypeList, [DealModel::DEAL_TYPE_GENERAL]);

        $p2pCards = $exclusiveCards = [];
        //请求网贷接口
        if ($p2pDealTypeList) {
            $phReserveEntraService = new PhReserveEntraService();
            $result = $phReserveEntraService->getReserveEntraList(1000000, 0, $userId);
            $p2pCards = !empty($result['list']) ? $result['list'] : [];

        }

        if ($exclusiveDealTypeList) {
            $entraService = new ReservationEntraService();
            $result = $entraService->getReserveEntraDetailList(ReservationEntraModel::STATUS_VALID, 1000, 0, $userInfo);
            $exclusiveCards = !empty($result['list']) ? $result['list'] : [];
        }

        //聚合结果
        $cards = array_merge($p2pCards, $exclusiveCards);
        if(!empty($cards)){
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            $url = $http . $_SERVER['HTTP_HOST'];
            foreach ($cards as $key=>$val){
                $productType = $this->rpc->local('UserReservationService\getProductByDealType', [$val['dealType']]);
                $cards[$key]['appointUrl'] = sprintf("/deal/reserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $cards[$key]['detailUrl'] = sprintf("/deal/reservedetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $cards[$key]['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
                $cards[$key]['rate'] = substr($val['rate'],0,-1); //去掉百分号
                $cards[$key]['productType'] = $productType;
            }

            $resData['list'] = $cards;
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

