<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.uc");
FP::import("app.deal");
FP::import("libs.libs.send_contract");
use app\models\dao\DealLoanRepay;
use app\models\dao\DealLoad;
use app\models\dao\Deal;
use app\models\service\LoanType;
use app\models\service\Finance;

class uc_investModule extends SiteBaseModule {
    public function index() {
        $status         = intval($_REQUEST['status']);
        $date_start     = $_REQUEST['date_start'];
        $date_end       = $_REQUEST['date_end'];
        $page           = intval($_REQUEST['p']);
        $page           = $page <= 0 ? 1 : $page;
        $page_size      = app_conf("PAGE_SIZE");
        $user_id        = intval($GLOBALS['user_info']['id']);
        $ajax           = intval($_REQUEST['ajax']);
        $page_size_loan = 7;

        $result = DealLoad::instance()->getUserLoanList($user_id, $page, $page_size, $status, $date_start, $date_end);
        $count  = $result['count'];
        $list   = $result['list'];

        foreach ($list as $k => $v) {
            $deal                         = get_deal($v['deal_id']);
            $list[$k]['deal']             = $deal;
            $list[$k]['repay_start_time'] = $deal['repay_start_time'] == 0 ? "-" : to_date($deal['repay_start_time'], 'Y-m-d');
            $list[$k]['loantype_name']    = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];

            $deal_model = new Deal();
            $deal = $deal_model->find($v['deal_id']);
            //$list[$k]['income'] = $deal->getEarningMoney($v['money']);
	        $list[$k]['income'] = Finance::getExpectEarningByDealLoan($v);

	        if (LoanType::getLoanTagByTypeId($deal['type_id']) == LoanType::TYPE_ZCZR) {
				$list[$k]['is_lease'] = 1;
			}

            if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
                $loan_repay      = DealLoanRepay::instance()->getLoanRepayListByLoanId($v['id']);
                $loan_repay_list = $loan_repay['list'];

                foreach ($loan_repay_list as $key => $val) {
                    if ($val['type'] == DealLoanRepay::MONEY_MANAGE || $val['money'] == 0) {
                        unset($loan_repay_list[$key]);
                        continue;
                    }
                    $loan_repay_list[$key]['money_type']   = DealLoanRepay::getLoanRepayType($val['type']);
                    $loan_repay_list[$key]['money_status'] = DealLoanRepay::getLoanRepayStatus($val['status']);
                    if ($val['real_time'] > 0) {
                        $real_date = to_date($val['real_time'], "Y-m-d");
                        if ($real_date > to_date($val['time'])) {
                            $loan_repay_list[$key]['is_delay'] = 1;
                        }
                        $loan_repay_list[$key]['real_time'] = $real_date;
                    } else {
                        $loan_repay_list[$key]['real_time'] = "-";
                    }
                }

                $c          = count($loan_repay_list);
                $page_loan  = ceil($c / $page_size_loan);
                $repay_list = array();

                for ($i = 0; $i < $page_loan; $i++) {
                    for ($j = 0; $j < $page_size_loan; $j++) {
                        $repay = array_shift($loan_repay_list);
                        if (!$repay) {
                            break 2;
                        }
                        $repay_list[$i][$j] = $repay;
                    }
                }

                $arr_page_loan = array();
                for ($i = 1; $i <= $page_loan; $i++) {
                    $arr_page_loan[] = $i;
                }

                $list[$k]['loan_repay_list'] = $repay_list;
                $list[$k]['loan_page']       = $arr_page_loan;
                $list[$k]['real_income']     = DealLoanRepay::instance()->getTotalIncomeMoney($v['id']);
            }

            $contractTool = new sendContract();
            $contract1    = $contractTool->create_deal_number($deal, $user_id, $v['id'], 1);
            $contract2    = $contractTool->create_deal_number($deal, $user_id, $v['id'], 4);
            $contract3    = $contractTool->create_deal_number($deal, $user_id, $v['id'], 5);
            //合同列表
            $sql_contract          = "SELECT id,title,number FROM " . DB_PREFIX . "contract WHERE user_id='{$user_id}' and number in('{$contract1}','{$contract2}','{$contract3}')";
            $list[$k]['contracts'] = $GLOBALS['db']->getAll($sql_contract);
        }

        if ($count > $page_size) {
            $page_model = new Page($count, $page_size); //初始化分页对象
            $pages      = $page_model->show(array("addtourl"=>1, "status", "date_start", "date_end"));
            $GLOBALS['tmpl']->assign('pages', $pages);
        }

        $GLOBALS['tmpl']->assign("date_start", $date_start);
        $GLOBALS['tmpl']->assign("date_end", $date_end);
        $GLOBALS['tmpl']->assign("status", $status);
        $GLOBALS['tmpl']->assign("list", $list);
        $GLOBALS['tmpl']->assign("page_title", "投资的项目");

        $GLOBALS['tmpl']->assign("inc_file", "inc/uc/uc_invest.html");
        //$this->set_nav(array("我的P2P"=>url("index", "uc_center"), "投资的项目"));
        $this->display();
    }

    public function loan_repay_list(){
    	
//     	return app_redirect(url("account/loan"));
    	$page = intval($_REQUEST['p']);
    	
        if($page==0){
        	$page = 1;
        }
        
        $where = '';
    	$user_id = $GLOBALS['user_info']['id'];
        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");
        
        //搜索条件设置
        $repay_status = getRequestString('repay_status', '');
        $money_type = getRequestString('money_type', '');
        $start_time = isset($_GET['start_time']) ? to_timespan($_GET['start_time']) : '';
        $end_time = isset($_GET['end_time']) ? to_timespan($_GET['end_time']) : '';
        
        if($repay_status !== ''){
        	$where .= ' and l.status = '.intval($repay_status); 
        }
        
        if($money_type){
        	$where .= ' and l.type = '.intval($money_type);
        }
        
        if($start_time){
        	$where .= ' and l.time >= '.intval($start_time);
        }
        
        if($end_time){
        	$where .= ' and l.time <= '.intval($end_time);
        }

        $sql = "select l.* from ".DB_PREFIX."deal_loan_repay as l where l.loan_user_id = $user_id $where
        		and (l.`type` in (2,3,4,5,7) or (l.`type` = 1 and l.money != 0 )) order by l.deal_id desc,l.time asc limit ".$limit;
        
        $count_sql = "select count(l.id) from ".DB_PREFIX."deal_loan_repay as l where l.loan_user_id = $user_id $where
        		and (l.`type` in (2,3,4,5,7) or (l.`type` = 1 and l.money != 0 ))";
        
    	$loan_repay_list = $GLOBALS['db']->getAll($sql);
    	$loan_repay_count =$GLOBALS['db']->getOne($count_sql);
    	
    	$repay_status_all = array(0 => '未还', 1 => '已还', 2 => '因提前还款而取消');
    	$money_type_all = array(1 => '本金', 2 => '利息', 3 => '提前还款', 4 => '提前还款补偿金', 5 => '逾期罚息', 7 => '提前还款利息');
    	$search = array('money_type' => $money_type, 'repay_status' => $repay_status, 'start_time' => $_GET['start_time'], 'end_time' => $_GET['end_time']);
     	
    	if($loan_repay_list){
	    	foreach($loan_repay_list as &$repay){
	    		$repay['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal where id = ".$repay['deal_id']);
	    		$repay['time'] = to_date($repay['time'],'Y-m-d');
	    		$repay['real_time'] = $repay['real_time'] ? to_date($repay['real_time'],'Y-m-d') : '-';
	    		$repay['is_timeout'] = $repay['real_time'] > $repay['time'] && $repay['time'] > 0 ? 1 : 0;
	    		$repay['repay_status'] = $repay_status_all[$repay['status']];
	    		$repay['money_type'] = $money_type_all[$repay['type']];
	    	}
    	}

    	$page = new Page($loan_repay_count,app_conf("PAGE_SIZE"));
    	$page_str = $page->show();
    	
    	$GLOBALS['tmpl']->assign('pages',$page_str);
    	$GLOBALS['tmpl']->assign("list",$loan_repay_list);
    	$GLOBALS['tmpl']->assign('money_type',$money_type_all);
    	$GLOBALS['tmpl']->assign('repay_status',$repay_status_all);
    	$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_invest_new.html");
    	$GLOBALS['tmpl']->assign('search', $search);
    	//$this->set_nav(array("我的P2P"=>url("index", "uc_center"), "回款计划"));
    	
    	$this->display();
    }

    /**
     * 预约列表
     * @author wenyanlei  2013-9-12
     */
    public function booked() {
        $mobile = $GLOBALS['user_info']['mobile'];

        $page = intval($_REQUEST['p']);
        if ($page == 0) $page = 1;
        $limit = (($page - 1) * app_conf("PAGE_SIZE")) . "," . app_conf("PAGE_SIZE");

        $sql_count = "select count(*) from " . DB_PREFIX . "preset a left join " . DB_PREFIX . "preset_program b
            on a.program_id = b.id where a.user_name = '" . $GLOBALS['user_info']['user_name'] . "'";
        $count     = $GLOBALS['db']->getOne($sql_count);

        $page_obj = new Page($count, app_conf("PAGE_SIZE")); //初始化分页对象
        $p        = $page_obj->show();
        $GLOBALS['tmpl']->assign('pages', $p);

        $sql  = "select a.id as pid,a.*,b.program_name from " . DB_PREFIX . "preset a left join " . DB_PREFIX . "preset_program b
            on a.program_id = b.id where a.user_name = '" . $GLOBALS['user_info']['user_name'] . "'
            order by a.create_time desc limit $limit";
        $list = $GLOBALS['db']->getAll($sql);

        $GLOBALS['tmpl']->assign('page', $page);
        $GLOBALS['tmpl']->assign("booked_list", $list);
        $GLOBALS['tmpl']->assign("inc_file", "inc/uc/uc_invest_booked.html");
        $GLOBALS['tmpl']->display("page/uc.html");
    }

    /**
     * 删除预约
     * @author wenyanlei  2013-9-12
     * @param $id 预约id
     */
    public function booked_del() {
        $id = intval($_REQUEST['id']);
        if ($id > 0) {
            $user_name = $GLOBALS['db']->getOne("select user_name from " . DB_PREFIX . "preset where id =" . $id);
            if ($user_name == $GLOBALS['user_info']['user_name']) {
                $GLOBALS['db']->query('delete from ' . DB_PREFIX . 'preset where id = ' . $id . ' limit 1');
            }
        }
        return app_redirect(url("uc_invest-booked?p=" . intval($_GET['p'])));
    }
}
?>
