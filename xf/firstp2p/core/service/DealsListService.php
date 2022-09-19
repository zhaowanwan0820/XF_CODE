<?php

/**
 * DealListService.php
 * @date 2017-07-19
 * @author yanjun <yanjun5@ucfgroup.com>
 * */

namespace core\service;

use core\dao\UserReservationModel;
use core\dao\ReservationEntraModel;
use core\service\DealTypeGradeService;
use core\service\ncfph\ReserveEntraService as PhReserveEntraService;
use core\service\ReservationEntraService;
use core\dao\DealModel;
use libs\utils\Curl;
use libs\utils\Logger;
use libs\rpc\Rpc;

class DealsListService extends BaseService {

    const DEAL_LIST_TYPE_P2P = 'p2p';
    const DEAL_LIST_TYPE_ZX = 'zx';
    const DEAL_LIST_TYPE_ZX_P2P = 'zxp2p'; //获取专享和p2p标的列表
    const DEAL_LIST_TYPE_ZX_P2P_NUM = 2; //p2p标显示前两个
    const DEAL_LIST_TYPE_RSV_NUM = 2; //随心约标显示前两个
    const DEAL_LIST_TYPE_RSVZX_NUM = 2; //随心约尊享标显示前两个
    const DEAL_LIST_CNT_QUANZHI = 10000;    //权值次数
    const DEAL_LIST_MONEY_QUANZHI = 1;  //投资金额权值
    const DEAL_LIST_ERDERTYPE_P2P = 1;  //order_type 1 代表p2p
    const DEAL_LIST_ERDERTYPE_ZX = 2;  //order_type 2 代表专享
    const DEAL_LIST_ERDERTYPE_RSVP2P = 3; //order_type 3 代表随心约网贷
    const DEAL_LIST_ERDERTYPE_RSVZX = 4;  //order_type 4 代表随心约尊享
    const DEAL_LIST_ERDERTYPE_DUOTOU = 5;  //order_type 5 代表智多鑫

    const DEALS_LIST_USER = 'deals_list_user';
    const DEAL_LIST_QUANZHI_EXPIRE = 86400; //缓存的有效时间

    private static $_deal_type_map = array(
        self::DEAL_LIST_TYPE_P2P => '0,1',
            //self::DEAL_LIST_TYPE_ZX      => '3',
    );
    private static $sort_map = array(
        "供应链" => '1',
        "企业经营贷" => '2',
        "消费贷" => '3',
        "个体经营贷" => '4',
    );
    public static $rateText = array(
        0 => '年化借款利率',
        1 => '预期年化',
        2 => '预期年化',
        3 => '预期年化',
    );

    public function __construct() {
        $this->rpc = new Rpc();
    }

    /**
     * 获取列表
     * @param $userId
     * @param $limit
     * @param $refresh
     * @return array
     */
      public function getList($userId, $limit = 'all', $refresh = 0) {

        //当前用户是不是新用户
        $isNew = $this->isNew($userId);
        //获取新手标,是新用户
        $newUserList = array();
        if($isNew && $userId != 0){
           $newUserList = $this->getNewUserList($refresh, NULL, NULL, NULL, NULL, 1, NULL,$userId);
         }else{
            $newUserList = array();
        }
        //获取定制标
         $dingzhiList=array();
         if($userId != 0){
          $dingzhiList = $this->getDingzhiList($refresh, NULL, NULL, NULL, NULL, 1, NULL, $userId);
         }
         $showNum=0;
             foreach ($newUserList as $item) {
             $showNum = count($item)+$showNum;
          }
          foreach ($dingzhiList as $item) {
             $showNum = count($item)+$showNum;
          }
        //p2p和专项,
         $p2pzxList=array();
        if($showNum<2){
         $p2pzxList = $this->getZxP2pDealList($refresh, NULL, NULL, NULL, NULL, 1, NULL,$userId,$showNum);
        }
        //随心约列表
        $sxyList = $this->getAppoinmentsList($userId);
        //智多鑫列表获取
        $duotouList = $this->getDuotuoList($userId, 1);

        if ($userId == 0) {
            $result = $this->mergeArr($duotouList, $p2pzxList, $sxyList);
        }else{
           $result = $this->sortDealsList($duotouList, $p2pzxList, $sxyList, $userId);
        }

        //将新手标和定制标置顶，先新手标再定制标
        //拼接定制标
        if (!empty($dingzhiList)) {
            foreach ($dingzhiList as $item) {
                $result = array_merge($item, $result);
            }
        }
        //拼接新手标
        if (!empty($newUserList)) {
            foreach ($newUserList as $item) {
                $result = array_merge($item, $result);
            }
        }
        if ($limit == 'all') {
            return $result;
        }
        return array_slice($result, 0, $limit);
    }

     //DealsList根据权值排序
    private function sortDealsList($duotouList, $p2pzxList, $sxyList, $userId) {

        //获取权值
        $quanzhiArr = $this->getQuanZhiByUerid($userId);
        if(empty($quanzhiArr['data'])){
            $result = $this->mergeArr($duotouList, $p2pzxList,$sxyList);
            return $result;
        }
        $i = 0;
        $j = 0;
        $dealsArr = array();

         //智多鑫计算权值
        foreach ($duotouList as $key => $item) {
            //代表网贷
            $avgCnt = $quanzhiArr['data']['invest_cnt_duotou'];
            $avgMoney = $quanzhiArr['data']['weighted_avg_money_duotou'];
            $avgTerm = $quanzhiArr['data']['weighted_avg_term_duotou'];
            $term = intval($item['lock_day']);

            //计算公式
            $cnt = $avgCnt * $this::DEAL_LIST_CNT_QUANZHI;
            $money = $avgMoney * $this::DEAL_LIST_MONEY_QUANZHI;
            //计算公式
            $num = ($cnt + $money) / (abs($avgTerm - $term)+1);
            $dealsArr[$i]['num'] = $num;
            $dealsArr[$i]['num2'] = $j;
            $dealsArr[$i]['data'] = $item;
            $i++;
            $j++;
        }

         //随心约计算权值
        foreach ($sxyList as $key => $item) {
            //代表网贷
            if ($item['productType'] == 1) {
                $avgCnt = $quanzhiArr['data']['invest_cnt_rsvp2p'];
                $avgMoney = $quanzhiArr['data']['weighted_avg_money_rsvp2p'];
                $avgTerm = $quanzhiArr['data']['weighted_avg_term_rsvp2p'];
            } else {   //代表专项
                $avgCnt = $quanzhiArr['data']['invest_cnt_rsvzx'];
                $avgMoney = $quanzhiArr['data']['weighted_avg_money_rsvzx'];
                $avgTerm = $quanzhiArr['data']['weighted_avg_term_rsvzx'];
            }
            //判断loantype的类型
            if ($item['unitType'] == 1) {
                $term = intval($item['investLine']);
            } else {
                $term = intval($item['investLine']) * 30;
            }
            //计算公式
            $cnt = $avgCnt * $this::DEAL_LIST_CNT_QUANZHI;
            $money = $avgMoney * $this::DEAL_LIST_MONEY_QUANZHI;
            //计算公式
            $num = ($cnt + $money) / (abs($avgTerm - $term)+1);

            $dealsArr[$i]['num'] = $num;
            $dealsArr[$i]['num2'] = $j;
            $dealsArr[$i]['data'] = $item;
            $i++;
            $j++;
        }

        foreach ($p2pzxList as $items) {
            foreach ($items as $item) {
                //先判断是什么类型;
                if ($item['cu_type'] == 'p2p') {
                    $avgCnt = $quanzhiArr['data']['invest_cnt_p2p'];
                    $avgMoney = $quanzhiArr['data']['weighted_avg_money_p2p'];
                    $avgTerm = $quanzhiArr['data']['weighted_avg_term_p2p'];
                } else {
                    $avgCnt = $quanzhiArr['data']['invest_cnt_zx'];
                    $avgMoney = $quanzhiArr['data']['weighted_avg_money_zx'];
                    $avgTerm = $quanzhiArr['data']['weighted_avg_term_zx'];
                }
                //判断loantype的类型
                if ($item['loantype'] == 5) {
                    $term = intval($item['repay_time']);
                } else {
                    $term = intval($item['repay_time']) * 30;
                }
                $cnt = $avgCnt * $this::DEAL_LIST_CNT_QUANZHI;
                $money = $avgMoney * $this::DEAL_LIST_MONEY_QUANZHI;
                //计算公式
                $num = ($cnt + $money) / (abs($avgTerm - $term)+1);
                $dealsArr[$i]['num'] = $num;
                $dealsArr[$i]['num2'] = $j;
                $dealsArr[$i]['data'] = $item;
                $i++;
                $j++;
            }
        }

        //排序根据 $dealsArr[$i]['num'];
        foreach ($dealsArr as $key => $value) {
            $sortKey[$key] = $value['num'];
        }
        array_multisort($sortKey, SORT_DESC, $dealsArr);
        $result = array();
        foreach ($dealsArr as $key => $value) {
            $result[] = $value['data'];
        }
        return $result;
    }

    //数组合并
    private function mergeArr($duotouList, $p2pzxList, $sxyList) {

        $dealsArr = array();
        if (!empty($duotouList)) {
            $dealsArr = $duotouList;
        }
        if (!empty($sxyList)) {
            $dealsArr = array_merge($dealsArr, $sxyList);
        }
        foreach ($p2pzxList as $item) {
            $dealsArr = array_merge($dealsArr, $item);
        }
          return $dealsArr;
    }
  //获取专享和p2p标的，每种标返回指定的个数，（显示的标的为未满标的标的），如果都满标了，则不显示
    public function getZxP2pDealList($refresh, $type, $field, $page, $page_size, $site_id, $p2pIsDisplay,$uid,$showNum) {
        $result = array();
        foreach (self::$_deal_type_map as $key => $deal_type) {
            // 未登录用户不显示专项的标
            if ($key == self::DEAL_LIST_TYPE_ZX && empty($uid)) {
                if ($site_id == 1) {
                    $deal_type = self::DEAL_LIST_TYPE_JYS;
                } else {
                    continue;
                }
            }

            if ($site_id == 1 && $key == self::DEAL_LIST_TYPE_ZX) {
                $deal_type .= ',' . self::DEAL_LIST_TYPE_JYS;
            }
            if ($refresh == 1) {
                // $deals = $this->rpc->local('DealService\getList', array(0, $type, $field, $page, $page_size, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay));
                $deals = (new \core\service\ncfph\DealService)->getList(0, $type, $field, $page, $page_size, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay);
            } else {
                //$param如果变化,indexDataRefresh脚本同步更新
                $instanse = (new \core\service\ncfph\DealService);
                $params = array(0, null, null, 1, 25, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay);
                $deals = \SiteApp::init()->dataCache->call($instanse, 'getList', $params, 120, false, true);
            }
            $tmp[$key] = isset($deals['list']['list']) ? $deals['list']['list'] : array();

            $repayTimeCount = [];
            foreach ($tmp[$key] as $k => $v) {
                /**
                 * 1.屏蔽vip大客户标的
                 * 2.期限一样去重
                 * @from 温光磊紧急需求
                 */
                if (false !== stripos($v['deal_tag_name'], 'vip'))
                    continue;
                $dateType = $v['loantype'] == 5 ? "D" : "M";
                $repayTimeKey = $v['repay_time'] . '.' . $dateType . '.' . $v['deal_type'];
                if (isset($repayTimeCount[$repayTimeKey])) {
                    continue;
                } else {
                    $repayTimeCount[$repayTimeKey] = 1;
                }
                /* end */
                if ($v['deal_status'] != 1)
                    break; //只显示进行中的标
                if ($showNum == self::DEAL_LIST_TYPE_ZX_P2P_NUM)
                    break; //只显示指定数量的标

                //不是定制标也不是新用户标
                if ($v['deal_crowd'] != '1' && $v['deal_crowd'] != '16') {
                    $result[$key][] = $this->handleDeal($v);
                    $showNum ++;
                    continue;
                }
            }
        }
        return $result;
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
        $ret['loantype'] = $dealInfo['loantype'];
        $ret['repay_time'] = $dealInfo['repay_time'];
        $ret['timelimit'] = ($dealInfo['deal_type'] == 1 ? ($dealInfo['lock_period'] + $dealInfo['redemption_period']) . '~' : '') . $dealInfo['repay_time'] . ($dealInfo['loantype'] == 5 ? "天" : "个月");
        $ret['total'] = $dealInfo['borrow_amount_wan_int'];
        $ret['avaliable'] = $dealInfo['need_money_detail'];
        $ret['total_origin'] = $dealInfo['borrow_amount_origin'];
        $ret['avaliable_origin'] = $dealInfo['need_money_origin'];
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
        $ret['deal_tag_name1'] = empty($dealInfo['deal_tag_name1']) ? '' : $dealInfo['deal_tag_name1'];
        // IOS客户端不支持type=3 因为着急上线 所以暂时返回type=0来兼容 以后IOS需要升级来支持不同type类型 jira:4156
        $ret['deal_type'] = ($dealInfo['deal_type'] == 3 || $dealInfo['deal_type'] == 2) ? 0 : $dealInfo['deal_type'];
        switch ($dealInfo['deal_type']) {
            case DealModel::DEAL_TYPE_EXCHANGE:
            case DealModel::DEAL_TYPE_EXCLUSIVE:
                $ret['cu_type'] = 'zx';
                $ret['order_type'] = $this::DEAL_LIST_ERDERTYPE_ZX;
                break;
            case DealModel::DEAL_TYPE_GENERAL:
            case DealModel::DEAL_TYPE_COMPOUND:
                $dealTypeGradeService = new DealTypeGradeService();
                $info = $dealTypeGradeService->getbyId($dealInfo['product_class_type']);
                $ret['product_class_name'] = empty($info['name']) ? '' : $info['name'];
                $ret['sort_id'] = isset(self::$sort_map[$ret['product_class_name']]) ? intval(self::$sort_map[$ret['product_class_name']]) : '';
                $ret['cu_type'] = 'p2p';
                $ret['order_type'] = $this::DEAL_LIST_ERDERTYPE_P2P;
                break;
            default:
                // 防止未识别出类型，前端展示错误
                $ret['cu_type'] = 'ot';
                Logger::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . 'deal_type Unidentified ' . $dealInfo['deal_type']);
                break;
        }
        $ret['needLogin'] = in_array($dealInfo['deal_type'], [2, 3]) ? 1 : 0;

        $ret['product_name'] = $dealInfo['product_name'];

        if(isset($dealInfo['holiday_repay_type'])) {
            $ret['holiday_repay_type'] = 0;//暂时改成0值 $dealInfo['holiday_repay_type'];
        }

        return $ret;
    }

    private function getDuotuoList($userId, $siteId) {

        //$param如果变化,indexDataRefresh脚本同步更新
        $instanse = (new \core\service\ncfph\DuotouService);
        $params = array($userId, $siteId);
        $duotouList = \SiteApp::init()->dataCache->call($instanse, 'getIndexDuotouList', $params, 120, false, true);
        foreach ($duotouList as & $activity) {
            $activity['order_type'] = $this::DEAL_LIST_ERDERTYPE_DUOTOU;
        }
        return $duotouList;
    }

    private function getAppoinmentsList($userId) {

        $params['product_type'] = isset($data['product_type']) ? intval($data['product_type']) : 0; //默认全部  0全部  1网贷  2尊享

        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($params['product_type'], $userId));
        if (empty($dealTypeList)) {
            $this->json_data = [];
            return true;
        }
        //拆分成网贷和专享类型列表
        $p2pDealTypeList = array_intersect([DealModel::DEAL_TYPE_GENERAL], $dealTypeList);
        $exclusiveDealTypeList = array_diff($dealTypeList, [DealModel::DEAL_TYPE_GENERAL]);

        $p2pCards = $exclusiveCards = [];
        //请求网贷接口
        if ($p2pDealTypeList) {
            $phReserveEntraService = new PhReserveEntraService();
            $result = $phReserveEntraService->getReserveEntraList(self::DEAL_LIST_TYPE_RSV_NUM, 0, $userId);
            $p2pCards = !empty($result['list']) ? $result['list'] : [];
        }

        $userInfo = \core\dao\UserModel::instance()->find($userId);
        if ($exclusiveDealTypeList) {
            $entraService = new ReservationEntraService();
            $result = $entraService->getReserveEntraDetailList(ReservationEntraModel::STATUS_VALID, self::DEAL_LIST_TYPE_RSVZX_NUM, 0, $userInfo);
            $exclusiveCards = !empty($result['list']) ? $result['list'] : [];
        }

        //聚合结果
        $cards = array_merge($p2pCards, $exclusiveCards);

       if (!empty($cards)) {
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            foreach ($cards as &$val) {
                $productType = $this->rpc->local('UserReservationService\getProductByDealType', [$val['dealType']]);
                $url = $http . $_SERVER['HTTP_HOST'];
                if ($productType ==  UserReservationModel::PRODUCT_TYPE_P2P) {
                    $appointUrl = sprintf(
                        $url."/ncfph/dealReserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s",
                        $val['investLine'],
                        $val['unitType'],
                        $val['dealType'],
                        $val['loantype'],
                        $val['investRate']
                    );

                    $detailUrl = sprintf(
                        $url."/ncfph/dealReserveDetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s",
                        $val['investLine'],
                        $val['unitType'],
                        $val['dealType'],
                        $val['loantype'],
                        $val['investRate']
                    );
                } else {
                    $appointUrl = sprintf(
                        $url."/deal/reserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s",
                        $val['investLine'],
                        $val['unitType'],
                        $val['dealType'],
                        $val['loantype'],
                        $val['investRate']
                    );

                    $detailUrl = sprintf(
                        $url."/deal/reserveDetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s",
                        $val['investLine'],
                        $val['unitType'],
                        $val['dealType'],
                        $val['loantype'],
                        $val['investRate']
                    );
                }

                $val['appointUrl'] = $appointUrl;
                $val['detailUrl'] = $detailUrl;
                $val['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
                $val['productType'] = $productType;
                if ($val['productType'] == 1) { //网贷
                    $val['order_type'] = $this::DEAL_LIST_ERDERTYPE_RSVP2P;
                } else {  //专项
                    $val['order_type'] = $this::DEAL_LIST_ERDERTYPE_RSVZX;
                }
            }
        } else {
            $cards = array();
        }
        return $cards;
    }

    //获取权值
    private function getQuanZhiByUerid($userId) {
        //先从缓存中获取，如果缓存中没有，再从数据库获取
        $key = self::DEALS_LIST_USER . '_' . $userId;
        $result = \SiteApp::init()->cache->get($key);
        if (empty($result)) {
            $result = Curl::get(app_conf('API_XINZAIZHINENG_QUANZHI') . "?userId=" . $userId);
            \SiteApp::init()->cache->set($key, $result, self::DEAL_LIST_QUANZHI_EXPIRE);
            Logger::info("---quanzhi---" . $userId . "|". $result);
        }
        return json_decode($result, true);
    }

    //获取用户的定指标
    public function getDingzhiList($refresh, $type, $field, $page, $page_size, $site_id, $p2pIsDisplay, $uid) {

        $result = array();
        foreach (self::$_deal_type_map as $key => $deal_type) {
            // 未登录用户不显示专项的标
            if ($key == self::DEAL_LIST_TYPE_ZX && empty($uid)) {
                if ($site_id == 1) {
                    $deal_type = self::DEAL_LIST_TYPE_JYS;
                } else {
                    continue;
                }
            }

            if ($site_id == 1 && $key == self::DEAL_LIST_TYPE_ZX) {
                $deal_type .= ',' . self::DEAL_LIST_TYPE_JYS;
            }
            if ($refresh == 1) {
                // $deals = $this->rpc->local('DealService\getList', array(0, $type, $field, $page, $page_size, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay));
                $deals = (new \core\service\ncfph\DealService)->getList(0, $type, $field, $page, $page_size, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay);
            } else {

                //$param如果变化,indexDataRefresh脚本同步更新
                $instanse = (new \core\service\ncfph\DealService);
                $params = array(0, null, null, 1, 25, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay);
                $deals = \SiteApp::init()->dataCache->call($instanse, 'getList', $params, 120, false, true);
            }
            $tmp[$key] = isset($deals['list']['list']) ? $deals['list']['list'] : array();
            foreach ($tmp[$key] as $k => $v) {
                /**
                 * 1.屏蔽vip大客户标的
                 * @from 温光磊紧急需求
                 */
                if (false !== stripos($v['deal_tag_name'], 'vip'))
                    continue;

                if ($v['deal_status'] != 1)
                    break; //只显示进行中的标

                if ($v['deal_crowd'] == 16 && $v['deal_specify_uid'] == $uid) { //定指标
                    $result[$key][] = $this->handleDeal($v);
                }
            }
        }
        return $result;
    }

   //新手标
    public function getNewUserList($refresh=0) {
            $result = array();
            if ($refresh == 1) {
                // $deals = $this->rpc->local('DealService\getList', array(0, $type, $field, $page, $page_size, false, $site_id, true, $deal_type, '', false, $p2pIsDisplay));
                $deals = (new \core\service\ncfph\DealService)->getIndexNewUserList();
            } else {
                $instanse = (new \core\service\ncfph\DealService);
                $deals = \SiteApp::init()->dataCache->call($instanse,'getIndexNewUserList',array(),120,false,true);
            }
            $tmp[0] = isset($deals['list']['list']) ? $deals['list']['list'] : array();
            foreach ($tmp[0] as $k => $v) {
                $result[0][] = $this->handleDeal($v);
             }
        return $result;
    }

    private function isNew($userId){
        //获取权值
        $quanzhiArr = $this->getQuanZhiByUerid($userId);
        //0：新用户;1：已注册未投资，注册30天以上未投资;2：已投资:，注册且投资
        if($quanzhiArr['data']['user_type']==="0"||$quanzhiArr['data']['user_type']==="1" || empty($quanzhiArr['data'])){
            return true;
        }
        return false;
    }
}
