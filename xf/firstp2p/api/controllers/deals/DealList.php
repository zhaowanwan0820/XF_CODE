<?php

/**
 * app4.0 列表页
 * 返回结果根据传的dealListType参数来返回相应的数据结构改变，其他逻辑不变
 * @date 2016-09-13
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\dao\DealLoanTypeModel;
use core\service\DealLoanTypeService;
use core\service\DealTypeGradeService;
use core\service\marketing\RecommendService;
use core\dao\DealModel;
use libs\utils\Logger;

class DealList extends AppBaseAction {

    const DEAL_LIST_TYPE_P2P = 'p2p';
    const DEAL_LIST_TYPE_ZX = 'zx';
    const DEAL_LIST_TYPE_ZX_P2P = 'zxp2p';//获取专享和p2p标的列表
    const DEAL_LIST_TYPE_ZX_P2P_NUM = 2;//每种标显示前两个
    const DEAL_LIST_TYPE_JYS = '2';
    const DEAL_LIST_TYPE_CU = 'cu'; // 定制用户标

    private static $_deal_type_map = array(
        self::DEAL_LIST_TYPE_P2P     => '0,1',
        //self::DEAL_LIST_TYPE_ZX      => '3',
    );
    private static $sort_map = array(
        "供应链" => '1',
        "企业经营贷" => '2',
        "消费贷" => '3',
        "个体经营贷" => '4',
    );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string', "option" => array('optional' => true)),
            "offset" => array("filter" => "int"),
            "count" => array("filter" => "int"),
            "type" => array("filter" => "int"),
            "sort" => array("filter" => "int"),
            "field" => array("filter" => "int"),
            "siteId" => array("filter" => "int"),
            'compound' => array('filter' => 'int', "option" => array('optional' => true)),
            'refresh' => array('filter' => 'int', "option" => array('optional' => true)),
            'dealListType' => array('filter' => 'string', "option" => array('optional' => true)),
            'dealSiteAllow' => array('filter' => 'string'),
            "isp2pindex" => array("filter"=>"int",'option' => array('optional' => true)), //是不是普惠首页
            "product_class_type" => array("filter"=>"int",'option' => array('optional' => true)), //产品大类
            "loan_user_customer_type" =>  array("filter"=>"int",'option' => array('optional' => true)), //借款客群
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;

        $dealListType = isset($data['dealListType']) ? $data['dealListType'] : '';
        // 专项的话返回空
        if (self::DEAL_LIST_TYPE_ZX == $dealListType){
            $this->json_data = array(
                'deal_type' => self::DEAL_LIST_TYPE_ZX,
                'deal_list' => array(),
            );
            return;
        }
        //请求普惠接口数据 START-----//
        if ($dealListType == self::DEAL_LIST_TYPE_ZX_P2P || $dealListType == self::DEAL_LIST_TYPE_P2P || $dealListType == self::DEAL_LIST_TYPE_ZX_P2P_NUM) {
            (new \core\service\ncfph\Proxy())->execute();// 代理请求普惠接口
        }
        //请求普惠接口数据  END-----//
        if(isset($data['count'])){
            $data['count'] = intval($data['count']);
        }

        $loginUser = $this->getUserByToken();
        $siteId = $data['siteId'] ? intval($data['siteId']) : 1;
        if (!empty($data['dealSiteAllow'])){
            $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = $data['dealSiteAllow'];
        }else{
            $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = get_config_db('DEAL_SITE_ALLOW', $siteId);
        }
        $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1;
        $compound = isset($data['compound']) ? intval($data['compound']) : 0;
        $refresh = isset($data['refresh']) ? intval($data['refresh']) : 0;
        $dealListType = isset($data['dealListType']) ? $data['dealListType'] : '';

        if($dealListType == self::DEAL_LIST_TYPE_ZX ) {
            $this->json_data = [];
            return;
        }

        $product_class_type = !empty($data['product_class_type']) ? intval($data['product_class_type']) : 0; //产品大类
        $loan_user_customer_type = !empty($data['loan_user_customer_type']) ? intval($data['loan_user_customer_type']) : 0; //借款客群
        $isp2pindex = !empty($data['isp2pindex']) ? intval($data['isp2pindex']) : 0; //是否普惠首页

        self::$_deal_type_map[self::DEAL_LIST_TYPE_P2P] = (1 == $compound) ? '0,1' : '0';

        //存管标的是否显示
        $p2pIsDisplay = false;
        $userId = isset($loginUser['id']) ? $loginUser['id'] : 0;
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($userId));
        if (!empty($svInfo['status']) && $this->app_version >= 450) {
            $p2pIsDisplay = true;
        }

        // 获取list_type对应的标的列表
        if (self::DEAL_LIST_TYPE_ZX_P2P == $dealListType) {
            $result = $this->getZxP2pDealList($refresh, $data['sort'], $data['field'], $p, $data['count'], $siteId, $p2pIsDisplay, $loginUser['id']);
            $this->json_data = $result;
            return;
        }
        // 获取标定制列表
        if(self::DEAL_LIST_TYPE_CU == $dealListType){
            if (!empty($loginUser['id'])){
                $result = $this->getDealListUserVisible($loginUser['id'],false,true,$siteId,array(),false,$p2pIsDisplay);
                if (!empty($result['deal_list'])){
                    $result['deal_list'] = array_splice($result['deal_list'],$data['offset'],$data['count']);
                }
                $this->json_data = $result;
                return;
            }else{
                $this->json_data = array(
                    'deal_type' => self::DEAL_LIST_TYPE_CU,
                    'deal_list' => array(),
                );
                return;
            }
        }

        $type_cate = $data['type'];

        if ($this->isWapCall()) {
            $jys_type_str = ',' . self::DEAL_LIST_TYPE_JYS;
        } else {
            $jys_type_str = $siteId == 1 ? ','.self::DEAL_LIST_TYPE_JYS : '';//只有主站显示交易所标
        }

        if (isset(self::$_deal_type_map[$dealListType])) {
            $type_cate = 0;
            $deal_type_str = self::$_deal_type_map[$dealListType];
            if($dealListType == self::DEAL_LIST_TYPE_ZX ) {
                $deal_type_str .= $jys_type_str;
            }
        }else {
            $deal_type_str = (1 == $compound) ? '0,1,3' : '0,3';
            $deal_type_str .= $jys_type_str;
        }

        $result = array();
        $option= array();
        if($isp2pindex == 1) {
            $option['product_class_type'] = intval($data['product_class_type']); //产品大类
            $option['loan_user_customer_type'] = intval($data['loan_user_customer_type']); //借款客群

            $p2pSiteTags = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getP2pSiteTags', array()), 30, false, false);
            $result['deal_list']['product_class_types'] = $p2pSiteTags['product_class_types'];
            $result['deal_list']['loan_user_customer_types'] = $p2pSiteTags['loan_user_customer_types'];
            $result['deal_list']['list'] = array();
        }
        // 用户未登录不显示专项的标
        if (empty($loginUser['id'])){
            // 只有siteId情况
            $option['not_deal_type'] = '3';
            if (!empty($deal_type_str)){
                $deal_type_str = $this->rpc->local('DealCustomUserService\filterZx', array($deal_type_str));
                if (!empty($deal_type_str)){
                    $option['not_deal_type'] = '';
                }
            }
        }
        if ($refresh == 1) {
            $deals = $this->rpc->local('DealService\getList', array($type_cate, $data['sort'], $data['field'], $p, $data['count'], false, $siteId, true, $deal_type_str, '', false, $p2pIsDisplay,$option));
        } else {
            $params = array($type_cate, $data['sort'], $data['field'], $p, $data['count'], false, $siteId, true, $deal_type_str, '', false, $p2pIsDisplay,$option);
            $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getList', $params), 10);
        }

        if ($loginUser['id']) {
            $res = (new RecommendService)->getDealRecommend($loginUser['id']);
            $recommend = $res['data'];
            if ($recommend) {
                $deals['list']['list'] = $this->topRecommendDeal($deals['list']['list'], $recommend);
            }
        }

        $result['deal_type'] = $dealListType;
        foreach ($deals['list']['list'] as $k => $v) {
            if($isp2pindex == 1) {
                $result['deal_list']['list'][$k] = $this->handleDeal($v);
            } else {
                $result['deal_list'][$k] = $this->handleDeal($v);
            }
        }

        $this->json_data = $result;
    }

    public function handleDeal($dealInfo) {
        $ret['productID'] = $dealInfo['id'];
        $ret['type'] = $dealInfo['type_match_row'];

        // JIRA#3271 JIRA#3844 平台名称变更 by fanjingwen
        // 规则：  1、新逻辑：有product_class，显示标的全名；
        //        2、旧逻辑：[1.无标名前缀，显示标名全程；][2.有标名前缀，显示'前缀+项目名']
        if (!empty($dealInfo['product_class']) || empty($dealInfo['deal_name_prefix'])) {
            $ret['title'] = $dealInfo['old_name'];
        } else {
            $ret['title'] = $dealInfo['deal_name_prefix'] . $dealInfo['project_name'];
        }

        $ret['timelimit'] = ($dealInfo['deal_type'] == 1 ? ($dealInfo['lock_period'] + $dealInfo['redemption_period']) . '~' : '') . $dealInfo['repay_time'] . ($dealInfo['loantype'] == 5 ? "天" : "个月");
        $ret['total'] = $dealInfo['borrow_amount_wan_int'];
        $ret['avaliable'] = $dealInfo['need_money_detail'];
        $ret['mini'] = $dealInfo['min_loan_money_format'];
        $ret['repayment'] = $dealInfo['deal_type'] == 1 ? '提前' . $dealInfo['redemption_period'] . '天申赎' : $dealInfo['loantype_name'];
        $ret['loantype'] = $dealInfo['loantype'];
        $ret['stats'] = $dealInfo['deal_status'];
        $ret['crowd_str'] = $dealInfo['crowd_str'];
        $ret['deal_crowd'] = $dealInfo['deal_crowd'];
        $ret['start_loan_time'] = isset($dealInfo['start_loan_time_format']) ? $dealInfo['start_loan_time_format'] : "";
        $ret['income_base_rate'] = $dealInfo['income_base_rate'];
        $ret['income_ext_rate'] = $dealInfo['income_ext_rate'];

        $ret['rate'] = $ret['max_rate'] = $dealInfo['income_total_show_rate'];
        // jira:4080 显示的时候只显示基本利率app 时间base_rate + ext_rate 加起来了 app端改的话需要发版，所以在api这里进行修改
        $ret['income_ext_rate'] = 0;

        if (in_array($dealInfo['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
            $ret['money_loan'] = number_format($dealInfo['min_loan_money'], 2, ".", "");
        } else {
            $ret['money_loan'] = number_format($dealInfo['need_money_decimal'], 2, ".", "");
        }
        $ret['daren'] = ($dealInfo['min_loan_total_count'] > 0 || $dealInfo['min_loan_total_amount'] > 0) ? 1 : 0;
        $ret['deal_tag_name'] = $dealInfo['deal_tag_name'];
        if (!empty($dealInfo['deal_tag_name1'])) {
            $ret['deal_tag_name1'] = $dealInfo['deal_tag_name1'];
        }
        // IOS客户端不支持type=3 因为着急上线 所以暂时返回type=0来兼容 以后IOS需要升级来支持不同type类型 jira:4156
        $ret['deal_type'] = ($dealInfo['deal_type'] == 3 || $dealInfo['deal_type'] == 2) ? 0 : $dealInfo['deal_type'];
        switch($dealInfo['deal_type']){
            case DealModel::DEAL_TYPE_EXCHANGE:
            case DealModel::DEAL_TYPE_EXCLUSIVE:
                $ret['cu_type'] = 'zx';
                break;
            case DealModel::DEAL_TYPE_GENERAL:
            case DealModel::DEAL_TYPE_COMPOUND:
                $dealTypeGradeService = new DealTypeGradeService();
                $info=$dealTypeGradeService->getbyId($dealInfo['product_class_type']);
                $ret['product_class_name']=empty($info['name']) ? '' : $info['name'];
                $ret['sort_id'] = isset( self::$sort_map[$ret['product_class_name']] ) ? intval(self::$sort_map[$ret['product_class_name']]) : '';
                $ret['cu_type'] = 'p2p';
                break;
            default:
                // 防止未识别出类型，前端展示错误
                $ret['cu_type'] = 'ot';
                Logger::error(__CLASS__.' '.__FUNCTION__.' '.'deal_type Unidentified '.$dealInfo['deal_type']);
                break;

        }
        $ret['needLogin'] = in_array($dealInfo['deal_type'], [2, 3]) ? 1 : 0;

        $ret['product_name'] = $dealInfo['product_name'];

        return $ret;
    }

    //获取专享和p2p标的，每种标返回指定的个数，（显示的标的为未满标的标的），如果都满标了，则不显示
    public function getZxP2pDealList($refresh, $type, $field, $page, $page_size, $site_id, $p2pIsDisplay, $uid) {

        $reult = array();
        $recommend = null;
        if ($uid) {
            $res = (new RecommendService)->getDealRecommend($uid);
            $recommend = $res['data'];
        }
        $loginUser = $this->getUserByToken();
        foreach (self::$_deal_type_map as $key => $deal_type) {
            // 未登录用户不显示专项的标
            if ($key == self::DEAL_LIST_TYPE_ZX && empty($loginUser['id'])){
                if ($site_id == 1){
                    $deal_type = self::DEAL_LIST_TYPE_JYS;
                }else{
                    continue;
                }
            }

            if($site_id == 1 && $key == self::DEAL_LIST_TYPE_ZX){
                $deal_type .= ','.self::DEAL_LIST_TYPE_JYS;
            }
            if ($refresh == 1) {
                $deals = $this->rpc->local('DealService\getList', array(0, $type, $field, $page, $page_size, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay));
            } else {

                //$param如果变化,indexDataRefresh脚本同步更新
                $params = array(0, null, null, 1, 20, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay);
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getList', $params), 120, false, true);

            }
            $tmp[$key] = isset($deals['list']['list']) ? $deals['list']['list'] : array();
            $showNum = 0;
            if ($recommend) {
                $tmp[$key] = $this->topRecommendDeal($tmp[$key], $recommend, true);
            }
            $repayTimeCount = [];
            foreach ($tmp[$key] as $k => $v) {
                /**
                 * 1.屏蔽vip大客户标的
                 * 2.期限一样去重
                 * @from 温光磊紧急需求
                 */
                if (false !== stripos($v['deal_tag_name'], 'vip')) continue;
                $dateType = $v['loantype'] == 5 ? "D":"M";
                $repayTimeKey = $v['repay_time'].'.'.$dateType.'.'.$v['deal_type'];
                if (isset($repayTimeCount[$repayTimeKey])){
                    continue;
                } else {
                    $repayTimeCount[$repayTimeKey] = 1;
                }
                /* end */

                if ($v['deal_status'] != 1) break;//只显示进行中的标
                if($showNum == self::DEAL_LIST_TYPE_ZX_P2P_NUM) break;//只显示指定数量的标
                $result[$key][] = $this->handleDeal($v);
                $showNum ++;
            }
        }
        return $result;
    }

    /**
     * 根据用户偏好的投资天数，对标的进行排序
     */
    private function topRecommendDeal($list, $recommend, $checkNum = false)
    {
        $recommend *= 30;
        $top = [];
        foreach ($list as $key => $item) {
            if ($item['deal_status'] != 1) {
                $top[99999][] = $key;
                continue;
            }
            $days = ($item['loantype'] == 5) ? $item['repay_time'] : $item['repay_time'] * 30;
            $top[abs($recommend - $days)][] = $key;
        }
        ksort($top);
        $res = [];
        $showNum = 0;
        foreach ($top as $keys) {
            foreach ($keys as $k) {
                $deal = $list[$k];
                $res[] = $deal;
                if ($checkNum) {
                    if($showNum == self::DEAL_LIST_TYPE_ZX_P2P_NUM) return $res;
                    $showNum ++;
                }
            }
        }

        return $res;
    }

    /**
     * 获取用户所属定指标
     * @param $userId
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     */
    private function getDealListUserVisible($userId,$is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false,$is_show_p2p=false){

        $deals = $this->rpc->local('DealCustomUserService\getDealCustomUserList', array($userId,$is_all_site,$is_display,$site_id,$option,$is_real_site,$is_show_p2p));
        $ret = array(
            'deal_type' => self::DEAL_LIST_TYPE_CU,
            'deal_list' => $deals,
        );
        if (!empty($deals)){
            foreach($deals as $key => $deal){
                $deals[$key] = $this->handleDeal($deal);
            }

            $ret['deal_list'] = $deals;
        }
        return $ret;
    }

}
