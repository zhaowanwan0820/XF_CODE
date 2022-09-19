<?php

/**
 * RssService class file.
 * @author yangqing <yangqing@ucfgroup.com>
 * */

namespace core\service;

use core\dao\DealLoanTypeModel;
use core\dao\DealModel;

class RssService extends BaseService {

    private $rss_title = '网信理财';
    private $rss_link = '';
    private $rss_description = 'firstp2p.com';
    private $rss_imgur = '';
    private $rss_ulink = 'http://u.firstp2p.com/click?redirect=';

    public function getIncome($cate)
    {
        $incomeService = new \core\service\EarningService();
        //$income = $incomeService->getIncomeViewByCate(false,$cate);
        $income = $incomeService->getDealsIncomeView();
        \FP::import("libs.libs.feed_rss");
        $this->rss_link = \libs\web\Url::getDomain();
        $this->rss_description = app_conf(SHOP_DESCRIPTION);
        $rss = new \FeedRss($this->rss_title, $this->rss_link, $this->rss_description, $this->rss_imgur);
        $name = '收益统计';
        $desc = '年化收益率，投资人已累计投资，已为投资人带来收益，即将带来收益';
        if($income)
        {
            $rss->AddItem($name, $this->rss_link, $desc,date('Y-m-d H:i:s'), $income);
            return $rss->Display();
        }
        else
        {
            return $rss->Display();
        }
    }

    public function getDealInfo($id, $will_return = false){
        $dealService = new \core\service\DealService();
        //走从库
        $deal = $dealService->getDeal($id, true);
        if($deal)
        {
            \FP::import("libs.libs.feed_rss");
            $this->rss_link = \libs\web\Url::getDomain();
            $this->rss_description = app_conf(SHOP_DESCRIPTION);
            $rss = new \FeedRss($this->rss_title, $this->rss_link, $this->rss_description, $this->rss_imgur);

            $company = $dealService->getDealUserCompanyInfo($deal);

            $userService = new \core\service\UserService();
            $dealUser = $userService->getUserViaSlave($deal['user_id']);
            $dealUser = $userService->getExpire($dealUser);
            $dealUser = $dealUser->getRow();

            $dealLoadService = new \core\service\DealLoadService();
            $load_list = $dealLoadService->getDealLoanListByDealId($deal['id']);

            $rssData = array();
            $rssData['deal_id'] = $deal['id'];
            $rssData['ulink'] = $this->rss_ulink.$deal['url'];
            $dealName = $deal['old_name'];
            $dealURL = $deal['url'];
            $description = $deal['description'];
            $createTime =$deal['create_time'];
            $rssData['cate_id'] = $deal['cate_id'];
            $rssData['income_rate'] = ($deal['income_ext_rate']=='0')?$deal['income_fee_rate_format']:$deal['income_base_rate'].'%+'.$deal['income_ext_rate'].'%';//年化收益绿

            //收益方式
            if($deal['loantype'] == '5'){
                $rssData['repay_time'] = $deal['repay_time'].'天';
            }
            else{
                $rssData['repay_time'] = $deal['repay_time'].'个月';
            }
            $rssData['loantype_name'] = $deal['loantype_name'];//还款方式

            if($deal['warrant'] == '1'){
                $rssData['warrant'] = '担保本金';
            }
            elseif ($deal['warrant'] == '2') {
                $rssData['warrant'] = '担保本息';
            }
            else{
                $rssData['warrant'] = '无担保';
            }
            $rssData['need_money_detail'] = ($deal['deal_status']=='3')?'0.00':$deal['need_money_detail']; //可投金额
            $rssData['borrow_amount_detail'] = $deal['borrow_amount_format_detail']; //投资总额
            $rssData['load_money'] = format_price($deal['load_money']); //已投金额
            $rssData['min_loan_money'] = ($deal['min_loan_money']<1000)?$deal['min_loan_money'].'元':$deal['min_loan'].'万'; //起投金额
            $rssData['start_loan_time'] = $deal['start_loan_time_format']; //起投时间
            $rssData['remain_time'] = $deal['remain_time_format']; //剩余时间
            $rssData['deal_type'] = $deal['deal_status'];
            if ($deal['is_update'] == '1' || $deal['guarantor_status'] != '2' || $deal['deal_status'] == '0') {
                $rssData['deal_status'] = '等待确认';
            } elseif ($deal['deal_status'] == '4') {
                $rssData['deal_status'] = '还款中';
            } elseif ($deal['deal_status'] == '2') {
                $rssData['deal_status'] = '满标';
            } elseif ($deal['deal_status'] == '5') {
                $rssData['deal_status'] = '已还清';
            } else {
                $rssData['deal_status'] = '投资';
            }
            $rssData['tag_name'] = $deal['deal_tag_name']; //tag名称
            $rssData['tag_desc'] = $deal['deal_tag_desc']; //tag描述
            $rssData['deal_crowd'] = $deal['deal_crowd']; //1是新手标
            $rssData['agency'] = array();
            $rssData['agency']['name'] = $this->formatValue4Xml($deal['agency_info']['name'], !$will_return);
            $rssData['agency']['brief'] = $this->formatValue4Xml($deal['agency_info']['brief'], !$will_return);

            unset($deal);
            unset($company['user_id']);
            unset($company['borrow_user_idno']);
            unset($company['borrow_mobile']);
            unset($company['borrow_postcode']);
            unset($company['borrow_email']);
            unset($company['borrow_user_name']);
            $company['borrow_real_name'] = block_info($company['borrow_real_name'],3);
            $company['real_name'] = block_info($company['real_name'],3);
            $company['company_description_html'] = $this->formatValue4Xml($company['company_description_html'], !$will_return);
            $company['company_description'] = $this->formatValue4Xml($company['company_description'], !$will_return);

            $rssData['company'] = $company; //借款企业信息

            $deal_userinfo =array(
                'username'=>block_info($dealUser['real_name'],3),
                'sex'=>$dealUser['sex'],
                'graduatedyear'=>$dealUser['graduatedyear'], //入学年份
                'officecale'=> !empty($dealUser['workinfo']['officecale']) ? $dealUser['workinfo']['officecale'] : '', //公司规模
                'hashouse'=>$dealUser['hashouse'], //有无购房
                'byear'=>$dealUser['byear'], //年龄
                'work_province'=>$this->formatValue4Xml($dealUser['work_province'], !$will_return), //工作城市
                'workinfo'=> !empty($dealUser['workinfo']['position']) ? $this->formatValue4Xml($dealUser['workinfo']['position'], !$will_return) : '', //职位
                'houseloan'=>$dealUser['houseloan'], //有无房贷
                'marriage'=>$dealUser['marriage'], //婚姻状况
                'region'=> !empty($dealUser['region']) ? $dealUser['region'] : '', //所在地
                'salary'=> !empty($dealUser['workinfo']['salary']) ? $dealUser['workinfo']['salary'] : '', //工作收入
                'hascar'=>$dealUser['hascar'], //有无购车
                'graduation'=>$this->formatValue4Xml($dealUser['graduation'], !$will_return), //学历
                'officedomain'=> !empty($dealUser['workinfo']['officedomain']) ? $this->formatValue4Xml($dealUser['workinfo']['officedomain'], !$will_return) : '', //公司行业
                'workyears'=> !empty($dealUser['workinfo']['workyears']) ? $this->formatValue4Xml($dealUser['workinfo']['workyears'], !$will_return) : '', //现工作单位时间
                'carloan'=>$dealUser['carloan'], //有无车贷
                'info'=>$this->formatValue4Xml($dealUser['info'], !$will_return), //简介
                'idcardpassed'=>$dealUser['idcardpassed'], // 身份审核，1代表审核通过
                'workpassed'=>$dealUser['workpassed'], //工作认证 1代表审核通过
                'creditpassed'=>$dealUser['creditpassed'], //信用报告 1代表审核通过
                'incomepassed'=>$dealUser['incomepassed'], //收入认证 1代表审核通过
                'housepassed'=>$dealUser['housepassed'], //房产认证 1代表审核通过
                'carpassed'=>$dealUser['carpassed'], //购车证明 1代表审核通过
                'marrypassed'=>$dealUser['marrypassed'], //结婚认证 1代表审核通过
                'edupassed'=>$dealUser['edupassed'], //学历认证 1代表审核通过
                'skillpassed'=>$dealUser['skillpassed'], //技术职称认证 1代表审核通过
                'videopassed'=>$dealUser['videopassed'], //视频认证 1代表审核通过
                'mobiletruepassed'=>$dealUser['mobiletruepassed'], //手机实名认证 1代表审核通过
                'residencepassed'=>$dealUser['residencepassed'], //居住地证明 1代表审核通过
            );

            $rssData['deal_user'] = $deal_userinfo;
            $loan_list = array();
            foreach ($load_list as $key => $value) {
                $loan_list[]= array(
                    'index' => $key,
                    'user_name' => user_name_format($value['user_name']),
                    'real_name' => $value['user_deal_name'],
                    'money' => $value['money'],
                    'time' => $value['create_time']+date('Z'),
                );
            }
            $rssData['loan_list'] = $loan_list;

            if($will_return) {
                return array(
                        'title' => $dealName,
                        'link' => $dealURL,
                        'description' => $description,
                        'pubDate' => date('Y-m-d H:i:s', $createTime),
                ) + $rssData;
            } else {
                $rss->AddItem($dealName,$dealURL, $description,date('Y-m-d H:i:s',$createTime),$rssData);

                return $rss->Display();
            }
        }
    }

    /**
     * formatValue4Xml 给value加xml的<![CDATA[]]>
     * 比如value: vivi 将格式化为 <![CDATA[vivi]]>
     *
     * $param string $value
     * @param bool $format
     * @access private
     * @return string
     */
    private function formatValue4Xml($value, $format) {
        if($format) {
            return '<![CDATA['.$value.']]>';
        }
        return $value;
    }

    /**
     *
     * @param number $cate
     * @param number $page
     * @param number $pagesize
     * @param number $site_id
     * @param bool $is_real_site  默认是false，读全站数据,如果是true，读分站数据
     */
    public function getNewDealList($cate = 0, $page = 1, $pagesize = 10,$site_id=0,$is_real_site=false, $will_return = false) {
        $page = $page <= 0 ? 1 : $page;
        if($site_id ===  "0"){
            $is_all_site = true;
        }else{
            $is_all_site = false;
        }
        if($site_id === '' || in_array($site_id, [51])){
            $deals = array();
        }else{
            $deals = $this->getDealList($cate, null, null, $page, $pagesize,$is_all_site,$site_id,$is_real_site);
        }
        //优化去除count
        if (!empty($deals) /*&& $deals['count'] > 0*/ && !empty($deals['list'])) {
            \FP::import("libs.libs.feed_rss");
            $this->rss_link = \libs\web\Url::getDomain();
            $this->rss_description = app_conf(SHOP_DESCRIPTION);
            $rss = new \FeedRss($this->rss_title, $this->rss_link, $this->rss_description, $this->rss_imgur);
            $list = $deals['list'];
            $deal_type_arr = $deals['deal_type'];
            //只允许1000条
            $deals['count'] = 1000;
            $pageinfo = array(
                'count' => $deals['count'],
                'pagenum' => ceil($deals['count']/$pagesize),
                'pagesize' => $pagesize,
                'pageno' => $page,
            );

            unset($deals);
            foreach ($list['list'] as $key => $row) {
                $item = $row;
                $item_ext = array();
                $item_ext['deal_id'] = $item['id'];

                //总额
                $item_ext['totalMoney'] = $item['borrow_amount_format_detail'] . '万';
                //万为单位
                $item_ext['totalMoneyNum'] = sprintf("%.2f", $item['borrow_amount']);
                //担保
                $item_ext['warrant'] = $item['warrant'];
                if ($item['warrant'] == 1) {
                    $item_ext['warrant_str'] = '担保本金';
                } elseif ($item['warrant'] == 2) {
                    $item_ext['warrant_str'] = '担保本息';
                } else {
                    $item_ext['warrant_str'] = '担保本息';
                }
                $item_ext['typeID'] = $item['type_id'];
                //$item_ext['typeStr'] = $item['type_info']['name'];
                $item_ext['typeStr'] = $deal_type_arr[$item['type_id']]['name'];//$item['type_info']['name'];
                $item_ext['agencyID'] = $item['agency_id'];
                if ($item['agency_id'] > 0) {
                    $item_ext['agency_str'] = $item['agency_info']['short_name'];
                } else {
                    $item_ext['agency_str'] = '';
                }

                //年化收益率
                if (intval($item['income_float_rate']) == 0) {
                    $item_ext['rateShow'] = $item['rate_show'] . '%';
                } else {
                    $item_ext['rateShow'] = $item['income_base_rate'] . '%+' . $item['income_ext_rate'] . '%';
                }

                //期限
                if (intval($item['loantype']) == 5) {
                    $item_ext['repayTime'] = $item['repay_time'] . '天';
                } else {
                    $item_ext['repayTime'] = $item['repay_time'] . '个月';
                }
                $item_ext['tag_name'] = $item['deal_tag_name']; //tag名称
                $item_ext['tag_desc'] = $item['deal_tag_desc']; //tag描述
                //收益方式
                $item_ext['loantypeName'] = $item['loantype_name'];
                //起投金额
                $item_ext['minLoanMoney'] = ($item['min_loan_money']<1000)?$item['min_loan_money'].'元':$item['min_loan'].'万'; //起投金额

                //投资进度
                if ($item['is_update'] == '1') {
                    $item_ext['dealFlow_1'] = '等待确认';
                    $item_ext['dealFlow_2'] = '开始时间：' . $item['start_loan_time_format'];
                    $item_ext['dealFlow_1_str'] = '等待确认';
                    $item_ext['dealFlow_1_num'] = 0;
                } elseif ($item['deal_status'] == '4') {
                    $item_ext['dealFlow_1'] = '投资成功';
                    $item_ext['dealFlow_2'] = '成功时间：' . $item['full_scale_time'];
                    $item_ext['dealFlow_1_str'] = '投资成功';
                    $item_ext['dealFlow_1_num'] = 0;
                } elseif ($item['deal_status'] == '1' && $item['remain_time'] <= '0') {
                    $item_ext['dealFlow_1'] = '投资结束';
                    $item_ext['dealFlow_2'] = '结束时间：' . $item['flow_standard_time'];
                    $item_ext['dealFlow_1_str'] = '投资结束';
                    $item_ext['dealFlow_1_num'] = 0;
                } elseif ($item['deal_status'] == '0' || $item['guarantor_status'] != '2') {
                    $item_ext['dealFlow_1'] = '等待确认';
                    if ($item['start_loan_time_format'])
                        $item_ext['dealFlow_2'] = '结束时间：' . $item['start_loan_time_format'];
                    else
                        $item_ext['dealFlow_2'] = '结束时间：等待确认';

                    $item_ext['dealFlow_1_str'] = '等待确认';
                    $item_ext['dealFlow_1_num'] = 0;
                }
                elseif ($item['deal_status'] == '2') {
                    $item_ext['dealFlow_1'] = '可投金额：<font color="#F3781B">' . $item['need_money_detail'] . '元</font>';
                    $item_ext['dealFlow_2'] = '成功时间：' . $item['full_scale_time'];
                    $item_ext['dealFlow_1_str'] = '可投金额';
                    $item_ext['dealFlow_1_num'] = $item['need_money_detail'];
                } elseif ($item['deal_status'] == '5') {
                    $item_ext['dealFlow_1'] = '投资成功';
                    $item_ext['dealFlow_2'] = '成功时间：' . $item['full_scale_time'];
                    $item_ext['dealFlow_1_str'] = '投资成功';
                    $item_ext['dealFlow_1_num'] = 0;
                } else {
                    $item_ext['dealFlow_1'] = '可投金额：<font color="#F3781B">' . $item['need_money_detail'] . '元</font>';
                    $item_ext['dealFlow_2'] = '剩余时间：' . $item['remain_time_format'];
                    $item_ext['dealFlow_1_str'] = '可投金额';
                    $item_ext['dealFlow_1_num'] = $item['need_money_detail'];
                }

                $investRation = 1;
                //状态
                if ($item['is_update'] == '1') {
                    $item_ext['dealStatus'] = '查看';
                } elseif ($item['deal_status'] == '4') {
                    $item_ext['dealStatus'] = '还款中';
                } elseif ($item['deal_status'] == '1' && $item['remain_time'] <= '0') {
                    $item_ext['dealStatus'] = '流标';
                } elseif ($item['deal_status'] == '0' || $item['guarantor_status'] != '2') {
                    $item_ext['dealStatus'] = '查看';
                } elseif ($item['deal_status'] == '2') {
                    $item_ext['dealStatus'] = '满标';
                } elseif ($item['deal_status'] == '5') {
                    $item_ext['dealStatus'] = '已还清';
                } else {
                    //$investRation = 1.0 - floatval(str_replace(",", "", $item['need_money_detail'])) / $item_ext['totalMoneyNum'];
                    $investRation = 1.0 - $item['need_money_decimal'] / $item_ext['totalMoneyNum'];
                    $item_ext['dealStatus'] = '投资';
                }

                $item_ext['investRation'] = sprintf("%.2f", $investRation*100);
                $item_ext['ulink'] = $this->rss_ulink.$item['url'];
                $item_ext['wapBid'] = "/deal/bid?dealid=".end(explode("/", $item['url']));
                $item_ext['wapDetail'] = "/deal/detail?dealid=".end(explode("/", $item['url']));
                $item_ext['webBid'] = "/deal/bid/".end(explode("/",$item['url']));
                $item_ext['webDetail'] = $item['url'];

                $item_ext['key'] = $key;

                $create_time = date('Y-m-d H:i:s', $item['start_time']);
                $description = !empty($item['description']) ? $item['description'] : '';
                $rss->AddItem($item['name'], $item['url'], $description, $create_time, $item_ext);

                if($will_return) {
                    $item_list[] = array(
                        'title' => $item['old_name'],
                        'link' => $item['url'],
                        'description' => (string)$item['description'],
                        'pubDate' => $create_time,
                    ) + $item_ext;
                }
            }
            if($will_return) {
                $jsonArr = $pageinfo;
                $jsonArr['item'] = $item_list;
                return $jsonArr;
            } else {
                return $rss->Display($pageinfo);
            }
        }
    }

    /**
     * 获取投资列表
     */
    private function getDealList($cate, $type, $field, $page, $page_size = 0, $is_all_site = false, $site_id = 0,$is_real_site=false) {
        $deal_types_data = DealLoanTypeModel::instance()->getDealTypes(FALSE);
        $arr_types = $deal_types_data['others'];
        if (!in_array($cate, $arr_types)) {
            $cate = 0;
        }

        $sort = array(
            "field" => '',
            "type" => '',
        );

        $page = $page <= 0 ? 1 : $page;
        $page_size = $page_size <= 0 ? app_conf("DEAL_PAGE_SIZE") : $page_size;

        $deal_type = $deal_types_data['data'];
        //var_dump($deal_type);exit;
        foreach ($deal_type as $k => $v) {
            if ($cate == $k) {
                $deals = DealModel::instance()->getList($v['id'], $sort, $page, $page_size, $is_all_site, true, $site_id,array(),$is_real_site, false, false, false);
                $list = $deals['list'];
                foreach ($list as $key => $deal) {
                    $list[$key] = DealModel::instance()->handleDeal($deal, 1);
                }
                $result['page_size'] = $page_size;
                //$result['count'] = $deals['count'];
                break;
            } else {
                //$deals = DealModel::instance()->getList($v['id'], $sort, $page, $page_size, $is_all_site, true, $site_id);
            }

            if ($deals) {
                //$deal_type[$k]['count'] = $deals['count'];
            }
        }

        $data['list'] = $list;
        $result['list'] = $data;
        $result['sort'] = $sort;
        $result['deal_type'] = $deal_type;
        $result['cate'] = $cate;
        return $result;
    }

}
