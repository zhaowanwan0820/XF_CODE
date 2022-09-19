<?php

/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 * */

namespace api\controllers\deals;

use core\enum\DealEnum;
use libs\web\Form;
use api\controllers\AppBaseAction;

class Index extends AppBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    const DEAL_LIST_TYPE_P2P = 'p2p';
    const DEAL_LIST_TYPE_JYS = '2';

    private static $_deal_type_map = array(
        self::DEAL_LIST_TYPE_P2P     => '0,1',
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
            "site_id" => array("filter" => "int"),
            'compound' => array('filter' => 'int', "option" => array('optional' => true)),
            'refresh' => array('filter' => 'int', "option" => array('optional' => true)),
            'dealListType' => array('filter' => 'string', "option" => array('optional' => true)),
            "product_class_type" => array("filter"=>"int",'option' => array('optional' => true)), //产品大类
            "loan_user_customer_type" =>  array("filter"=>"int",'option' => array('optional' => true)), //借款客群
        );

        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        //$site_id = $data['site_id'] ? intval($data['site_id']) : 1;
        // 根据后台配置读取站点
        $site_id = 0;
        $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = get_config_db('DEAL_SITE_ALLOW', $site_id);
        $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1;
        $compound = isset($data['compound']) ? intval($data['compound']) : 0;
        $dealTypes = $compound == 1 ? '0,1,3' : '0,3';
        $refresh = isset($data['refresh']) ? intval($data['refresh']) : 0;
        $dealListType = $data['dealListType'];
        $product_class_type = intval($data['product_class_type']); //产品大类
        $loan_user_customer_type = intval($data['loan_user_customer_type']); //借款客群
        $isp2pindex = 1; //是否普惠首页

        // 获取list_type对应的标的列表
        self::$_deal_type_map[self::DEAL_LIST_TYPE_P2P] = (1 == $compound) ? '0,1' : '0';
        $jys_type_str = $site_id == 1 ? ','.self::DEAL_LIST_TYPE_JYS : '';//只有主站显示交易所的标
        $type_cate = $data['type'];

        $type_cate = '';
        $deal_type_str = self::$_deal_type_map[$dealListType];


        //存管标的是否显示
        $p2pIsDisplay = false;
        $userId = isset($loginUser['id']) ? $loginUser['id'] : 0;
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($userId), 'supervision');
        if (!empty($svInfo['status'])) {
            $p2pIsDisplay = true;
        }

        $result = array();
        $option= array();
        $option['product_class_type'] = intval($data['product_class_type']); //产品大类
        $option['loan_user_customer_type'] = intval($data['loan_user_customer_type']); //借款客群

        $p2pSiteTags = \SiteApp::init()->dataCache->call(
            $this->rpc,
            'local',
            array('DealService\getP2pSiteTags', array(), 'deal'),
            30,
            false,
            false
        );

        $result['product_class_types'] = $p2pSiteTags['product_class_types'];
        $result['loan_user_customer_types'] = $p2pSiteTags['loan_user_customer_types'];
        $result['list'] = array();

        // 用户未登录不显示专项的标
        if (empty($loginUser['id'])) {
            $option['not_deal_type'] = '3';
        }

        // 修改这里的参数时注意同步修改indexDataRefresh
        if ($refresh == 1) {
            $deals = $this->rpc->local('DealService\getList', array(
                $type_cate,
                $data['sort'],
                $data['field'],
                $p,
                $data['count'],
                false,
                $site_id,
                true,
                $deal_type_str,
                '',
                false,
                $p2pIsDisplay,
                $option
            ), 'deal');
        } else {
            $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getList', array(
                $type_cate,
                $data['sort'],
                $data['field'],
                $p,
                $data['count'],
                false,
                $site_id,
                true,
                $deal_type_str,
                '',
                false,
                $p2pIsDisplay,
                $option
            ), 'deal'), 30);

            $deals['list']['list'] = $this->rpc->local(
                'DealService\UserDealStatusSwitch',
                array($deals['list']['list']),
                'deal'
            );
        }

        foreach ($deals['list']['list'] as $k => $v) {
            $result['list'][$k] = $this->handleDeal($v);
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

        $ret['timelimit'] = ($dealInfo['deal_type'] == 1 ? ($dealInfo['lock_period'] + $dealInfo['redemption_period']) . '~' : '')
            . $dealInfo['repay_time'] . ($dealInfo['loantype'] == 5 ? "天" : "个月");

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

        // jira:4080 显示的时候只显示基本利率app 时间base_rate + ext_rate 加起来了 app端改的话需要发版，所以在api这里进行修改
        //$ret['income_ext_rate'] = $dealInfo['income_ext_rate'];
        $ret['income_ext_rate'] = 0;

        $ret['rate'] = $ret['max_rate'] = $dealInfo['income_total_show_rate'];
        if (in_array($dealInfo['deal_crowd'], array(DealEnum::DEAL_CROWD_NEW, DealEnum::DEAL_CROWD_MOBILE_NEW))) {
            $ret['money_loan'] = number_format($dealInfo['min_loan_money'], 2, ".", "");
        } else {
            $ret['money_loan'] = number_format($dealInfo['need_money_decimal'], 2, ".", "");
        }
        $ret['daren'] = ($dealInfo['min_loan_total_count'] > 0 || $dealInfo['min_loan_total_amount'] > 0) ? 1 : 0;
        $ret['deal_tag_name'] = $dealInfo['deal_tag_name'];
        $ret['deal_tag_name1'] = $dealInfo['deal_tag_name1'];
        $dealInfo['deal_compound_status'] = isset($dealInfo['deal_compound_status']) ? strval($dealInfo['deal_compound_status']) : '';
        $ret['deal_compound_status'] = $dealInfo['deal_status'] == 4 && $dealInfo['deal_compound_status'] === '0' ? '3' : $dealInfo['deal_compound_status'];
        $ret['product_name'] = $dealInfo['product_name'];
        $ret['holiday_repay_type'] = 0;//暂时改成0值 $dealInfo['holiday_repay_type'];

        return $ret;
    }
}
