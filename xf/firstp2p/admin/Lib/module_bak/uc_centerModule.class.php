<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use core\service\AccountService;

FP::import("app.uc");
FP::import("app.deal");
FP::import("libs.common.dict");
//error_reporting(E_ALL);
class uc_centerModule extends SiteBaseModule {
	private $space_user;
	public function init_main() {
		// $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($GLOBALS['user_info']['id']));
		// FP::import("libs.extend.ip");
		// $iplocation = new iplocate();
		// $address=$iplocation->getaddress($user_info['login_ip']);
		// $user_info['from'] = $address['area1'].$address['area2'];
		$GLOBALS ['tmpl']->assign ( 'user_auth', get_user_auth () );
	}
	public function init_user() {
		$this->user_data = $GLOBALS ['user_info'];
		
		$province_str = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "region_conf where id = " . $this->user_data ['province_id'] );
		$city_str = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "region_conf where id = " . $this->user_data ['city_id'] );
		if ($province_str . $city_str == '')
			$user_location = $GLOBALS ['lang'] ['LOCATION_NULL'];
		else
			$user_location = $province_str . " " . $city_str;
		
		$this->user_data ['fav_count'] = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "topic where user_id = " . $this->user_data ['id'] . " and fav_id <> 0" );
		$this->user_data ['user_location'] = $user_location;
		$this->user_data ['group_name'] = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "user_group where id = " . $this->user_data ['group_id'] . " " );
		
		$GLOBALS ['tmpl']->assign ( 'user_statics', sys_user_status ( $GLOBALS ['user_info'] ['id'], true ) );
	}
	
	/**
	 * 账户概览
	 */
	public function index(){
		return app_redirect(url("account"));
	    
	    $user_info = $GLOBALS ['user_info'];
	    $account_service = new AccountService();
	    $bankcard = $account_service->getUserBankInfo($user_info['id']);
	    
	    //用户统计
	    $user_statics = user_statics($GLOBALS['user_info']['id']);
	    //资产总额
	    $user_statics['money_all'] = $user_info['money'] + $user_info['lock_money'] + $user_statics['stay'];
	    //资金记录
	    $log = get_user_log(5,$user_info['id'],'money');
	    //投资概览
	    $invest = $account_service->getInvestOverview($user_info['id']);
	    //回款计划
	    $deal_repay = $account_service->getDealRepayOverview($user_info['id']);

	    $GLOBALS['tmpl']->assign('is_audit',$bankcard['is_audit']);
	    $GLOBALS['tmpl']->assign('user_info',$user_info);
	    $GLOBALS['tmpl']->assign('user_statics',$user_statics);
	    $GLOBALS['tmpl']->assign('bankcard',$bankcard);
	    $GLOBALS['tmpl']->assign('log',$log['list']);
	    $GLOBALS['tmpl']->assign("invest",$invest);
	    $GLOBALS['tmpl']->assign("deal_repay",$deal_repay);
	    $GLOBALS['tmpl']->assign("inc_file", "inc/uc/new/uc_center_index.html");
	    
	    $this->display();
	}
	
/* 	public function index(){
		$user_info = $GLOBALS ['user_info'];
		//获取用户银行卡信息
		$bankcard = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
		if($bankcard['bank_id']){
			$bank_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."bank where id =".$bankcard['bank_id']);
			$bankcard['name'] = $bank_info['name'];
			
			//地区1
			if($bankcard['region_lv1']){
			    $sql = "select * from ".DB_PREFIX."delivery_region where id = ".intval($bankcard['region_lv1']);
			    $country   = $GLOBALS['db']->getRow($sql);
			}
			//地区2
			if($bankcard['region_lv2']){
			    $sql = "select * from ".DB_PREFIX."delivery_region where id = ".intval($bankcard['region_lv2']);
			    $r   = $GLOBALS['db']->getRow($sql);
			    $bankcard['city'] = $r['name'];
			}
			//地区3
			if($bankcard['region_lv3']){
			    $sql = "select * from ".DB_PREFIX."delivery_region where id = ".intval($bankcard['region_lv3']);
				$r   = $GLOBALS['db']->getRow($sql);
				if(!empty($r)) {
					//if($bankcard['city'] && $bankcard['city'] != $r['name']) {
					    $bankcard['city'] .= '&nbsp;&nbsp;'.$r['name'];
					//}
				}
			}
			if(!empty($country))
			    $bankcard['city'] = $country['name'].'&nbsp;&nbsp;'.$bankcard['city'];
		}
		//用户统计
		$user_statics = user_statics($GLOBALS['user_info']['id']);
		//资产总额
		$user_statics['money_all'] = $user_info['money'] + $user_info['lock_money'] + $user_statics['stay'];
		//资金记录
		$log = get_user_log(5,$user_info['id'],'money');
		//投资概览
		$invest = $this->get_invest_overview($user_info['id']);
		//回款计划
		$deal_repay = $this->get_deal_repay($user_info['id']);
        
		//判断是否提交过审核银行卡信息
		$auditInfo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard_audit where user_id =".$GLOBALS['user_info']['id']." ORDER BY id DESC  LIMIT 1");
		$bankcard['newbankcard'] = $auditInfo['bankcard'];
		if(!empty($auditInfo))
		    $is_audit = $auditInfo['status'];
		else
		    $is_audit = 0;
		$GLOBALS['tmpl']->assign('is_audit',$is_audit);
		
	
		$GLOBALS ['tmpl']->assign('user_info',$user_info);
		$GLOBALS ['tmpl']->assign('user_statics',$user_statics);
		$GLOBALS ['tmpl']->assign('bankcard',$bankcard);
		$GLOBALS ['tmpl']->assign('log',$log['list']);
		$GLOBALS['tmpl']->assign("invest",$invest);
		$GLOBALS['tmpl']->assign("deal_repay",$deal_repay);
		
		$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/new/uc_center_index.html" );
		$this->display();
	} */
	
	public function focustopic() {
		$this->init_user ();
		$user_info = $this->user_data;
		$ajax = intval ( $_REQUEST ['ajax'] );
		if ($ajax == 0) {
			$this->init_main ();
		}
		$user_id = intval ( $GLOBALS ['user_info'] ['id'] );
		// 输出发言列表
		$page = intval ( $_REQUEST ['p'] );
		if ($page == 0)
			$page = 1;
		$limit = (($page - 1) * app_conf ( "PAGE_SIZE" )) . "," . app_conf ( "PAGE_SIZE" );
		
		// 开始输出相关的用户日志
		$uids = $GLOBALS ['db']->getOne ( "select group_concat(focused_user_id) from " . DB_PREFIX . "user_focus where focus_user_id = " . $user_info ['id'] . " " );
		
		if ($uids) {
			$uids = trim ( $uids, "," );
			$result = get_topic_list ( $limit, " user_id in (" . $uids . ") " );
		}
		
		$GLOBALS ['tmpl']->assign ( "topic_list", $result ['list'] );
		$page = new Page ( $result ['total'], app_conf ( "PAGE_SIZE" ) ); // 初始化分页对象
		$p = $page->show ();
		$GLOBALS ['tmpl']->assign ( 'pages', $p );
		$GLOBALS ['tmpl']->assign ( 'user_data', $user_info );
		if ($ajax == 0) {
			$list_html = $GLOBALS ['tmpl']->fetch ( "inc/topic_col_list.html" );
			$GLOBALS ['tmpl']->assign ( "list_html", $list_html );
			$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['UC_CENTER_MYFAV'] );
			$GLOBALS ['tmpl']->assign ( "post_title", $GLOBALS ['lang'] ['UC_CENTER_MYFAV'] );
			$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_index.html" );
			$GLOBALS ['tmpl']->display ( "page/uc.html" );
		} else {
			header ( "Content-Type:text/html; charset=utf-8" );
			echo $GLOBALS ['tmpl']->fetch ( "inc/topic_col_list.html" );
		}
	}
	public function lend() {
		$this->init_user ();
		$user_info = $this->user_data;
		$ajax = intval ( $_REQUEST ['ajax'] );
		if ($ajax == 0) {
			$this->init_main ();
		}
		$user_id = intval ( $user_info ['id'] );
		$user_info ['user_name'] = empty ( $user_info ['real_name'] ) ? $user_info ['user_name'] : $user_info ['real_name'];
		// 输出发言列表
		$page = intval ( $_REQUEST ['p'] );
		if ($page == 0)
			$page = 1;
		$limit = (($page - 1) * app_conf ( "PAGE_SIZE" )) . "," . app_conf ( "PAGE_SIZE" );
        
        $sql = "select d.*,u.user_name,dl.create_time as load_create_time,dl.deal_id,dl.money,u.level_id,u.province_id,u.city_id,dl.id as load_id from ".DB_PREFIX."deal as d left join ".DB_PREFIX."deal_load as dl on d.id = dl.deal_id LEFT JOIN ".DB_PREFIX."user u ON u.id=d.user_id where dl.user_id = ".$user_id." AND parent_id!=0 group by dl.id order by dl.create_time desc limit ".$limit;
        $sql_count = "select count(DISTINCT dl.id) from ".DB_PREFIX."deal as d left join ".DB_PREFIX."deal_load as dl on d.id = dl.deal_id where dl.user_id = ".$user_id." AND parent_id!=0";
        
        $result['total'] = $GLOBALS ['db']->getOne($sql_count);
        $result ['list'] = $GLOBALS ['db']->getAll($sql);
		
		// 借款图片
		$icon_arr = array ();
		$icon = $GLOBALS ['db']->getAll ( "select id,icon,name from " . DB_PREFIX . "deal_loan_type where is_effect = 1 and is_delete = 0" );
		foreach ( $icon as $ikey => $ival ) {
			$icon_arr [$ival ['id']] = $ival;
		}
		
		foreach ( $result ['list'] as &$list_item ) {
			//利率不从配置取，改取数据库  edit by wenyanlei 20130816
			$list_item ['deal_rate'] = $list_item['rate'].'%';
			$list_item ['rate'] = ($list_item['income_fee_rate'] > 0) ? $list_item['income_fee_rate'] : get_invest_rate_data($list_item['loantype'], $list_item['repay_time']) ;
			$list_item ['loan_icon'] = str_replace("public/images/", "images/", $icon_arr [$list_item ['type_id']] ['icon']);
			$list_item ['loan_name'] = get_deal_title($list_item['name'], $icon_arr [$list_item ['type_id']] ['name']);
            $list_item['create_time'] = $list_item['load_create_time'];
		}
		
		$page = new Page ( $result ['total'], app_conf ( "PAGE_SIZE" ) ); // 初始化分页对象
		$p = $page->show ();
		
		$GLOBALS ['tmpl']->assign ( 'pages', $p );
		$GLOBALS ['tmpl']->assign ( "lend_list", $result ['list'] );
		$GLOBALS ['tmpl']->assign ( "user_data", $user_info );
		
		if ($ajax == 0) {
			$list_html = $GLOBALS ['tmpl']->fetch ( "inc/uc/uc_center_lend.html" );
			$GLOBALS ['tmpl']->assign ( "list_html", $list_html );
			$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['UC_CENTER_LEND'] );
			$GLOBALS ['tmpl']->assign ( "post_title", $GLOBALS ['lang'] ['UC_CENTER_LEND'] );
			$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_index.html" );
			$GLOBALS ['tmpl']->display ( "page/uc.html" );
		} else {
			header ( "Content-Type:text/html; charset=utf-8" );
			echo $GLOBALS ['tmpl']->fetch ( "inc/uc_center_lend.html" );
		}
	}
	public function deal() {
		$this->init_user ();
		$user_info = $this->user_data;
		$ajax = intval ( $_REQUEST ['ajax'] );
		if ($ajax == 0) {
			$this->init_main ();
		}
		$user_id = intval ( $user_info ['id'] );
		$user_info ['user_name'] = empty ( $user_info ['real_name'] ) ? $user_info ['user_name'] : $user_info ['real_name'];
		
		// 输出借款记录
		$page = intval ( $_REQUEST ['p'] );
		if ($page == 0)
			$page = 1;
		$limit = (($page - 1) * app_conf ( "PAGE_SIZE" )) . "," . app_conf ( "PAGE_SIZE" );
		
		FP::import("app.deal");
		
		$result = get_deal_list ( $limit, 0, "user_id=" . $user_id, "id DESC" );
		
		$GLOBALS ['tmpl']->assign ( "deal_list", $result ['list'] );
		
		$page = new Page ( $result ['count'], app_conf ( "PAGE_SIZE" ) ); // 初始化分页对象
		$p = $page->show ();
		$GLOBALS ['tmpl']->assign ( 'pages', $p );
		
		$GLOBALS ['tmpl']->assign ( 'user_data', $user_info );
		if ($ajax == 0) {
			$list_html = $GLOBALS ['tmpl']->fetch ( "inc/uc/uc_center_deals.html" );
			$GLOBALS ['tmpl']->assign ( "list_html", $list_html );
			$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['UC_CENTER_MYDEAL'] );
			$GLOBALS ['tmpl']->assign ( "post_title", $GLOBALS ['lang'] ['UC_CENTER_MYDEAL'] );
			$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_index.html" );
			$GLOBALS ['tmpl']->display ( "page/uc.html" );
		} else {
			header ( "Content-Type:text/html; charset=utf-8" );
			echo $GLOBALS ['tmpl']->fetch ( "inc/uc/uc_center_deals.html" );
		}
	}
	public function mayfocus() {
		$user_info = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "user where id = " . intval ( $GLOBALS ['user_info'] ['id'] ) );
		$GLOBALS ['tmpl']->assign ( "user_data", $user_info );
		$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['YOU_MAY_FOCUS'] );
		$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_mayfocus.html" );
		$GLOBALS ['tmpl']->display ( "page/uc.html" );
	}
	public function fans() {
		$user_info = $this->user_data;
		
		$page_size = 24;
		
		$page = intval ( $_REQUEST ['p'] );
		if ($page == 0)
			$page = 1;
		$limit = (($page - 1) * $page_size) . "," . $page_size;
		
		$user_id = intval ( $GLOBALS ['user_info'] ['id'] );
		
		// 输出粉丝
		$fans_list = $GLOBALS ['db']->getAll ( "select focus_user_id as id,focus_user_name as user_name from " . DB_PREFIX . "user_focus where focused_user_id = " . $user_id . " order by id desc limit " . $limit );
		$total = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "user_focus where focused_user_id = " . $user_id );
		
		foreach ( $fans_list as $k => $v ) {
			$focus_uid = intval ( $v ['id'] );
			$focus_data = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "user_focus where focus_user_id = " . $user_id . " and focused_user_id = " . $focus_uid );
			if ($focus_data)
				$fans_list [$k] ['focused'] = 1;
		}
		$GLOBALS ['tmpl']->assign ( "fans_list", $fans_list );
		
		$page = new Page ( $total, $page_size ); // 初始化分页对象
		$p = $page->show ();
		$GLOBALS ['tmpl']->assign ( 'pages', $p );
		
		$GLOBALS ['tmpl']->assign ( "user_data", $user_info );
		$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['MY_FANS'] );
		$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_fans.html" );
		$GLOBALS ['tmpl']->display ( "page/uc.html" );
	}
	public function focus() {
		$this->init_user ();
		$user_info = $this->user_data;
		
		$page_size = 24;
		
		$page = intval ( $_REQUEST ['p'] );
		if ($page == 0)
			$page = 1;
		$limit = (($page - 1) * $page_size) . "," . $page_size;
		
		$user_id = intval ( $GLOBALS ['user_info'] ['id'] );
		
		// 输出粉丝
		$focus_list = $GLOBALS ['db']->getAll ( "select focused_user_id as id,focused_user_name as user_name from " . DB_PREFIX . "user_focus where focus_user_id = " . $user_id . " order by id desc limit " . $limit );
		$total = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "user_focus where focus_user_id = " . $user_id );
		
		foreach ( $focus_list as $k => $v ) {
			$focus_uid = intval ( $v ['id'] );
			$focus_data = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "user_focus where focus_user_id = " . $user_id . " and focused_user_id = " . $focus_uid );
			if ($focus_data)
				$focus_list [$k] ['focused'] = 1;
		}
		$GLOBALS ['tmpl']->assign ( "focus_list", $focus_list );
		
		$page = new Page ( $total, $page_size ); // 初始化分页对象
		$p = $page->show ();
		$GLOBALS ['tmpl']->assign ( 'pages', $p );
		
		$list_html = $GLOBALS ['tmpl']->fetch ( "inc/uc/uc_center_focus.html" );
		$GLOBALS ['tmpl']->assign ( "list_html", $list_html );
		$GLOBALS ['tmpl']->assign ( "user_data", $user_info );
		$GLOBALS ['tmpl']->assign ( "user_id", $user_id );
		$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['MY_FOCUS'] );
		
		$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_index.html" );
		$GLOBALS ['tmpl']->display ( "page/uc.html" );
	}
	public function setweibo() {
		// 微博绑定功能禁用
		// return app_redirect(APP_ROOT."/");exit();
		$user_info = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "user where id = " . intval ( $GLOBALS ['user_info'] ['id'] ) );
		
		$apis = $GLOBALS ['db']->getAll ( "select * from " . DB_PREFIX . "api_login where is_weibo = 1" );
		
		foreach ( $apis as $k => $v ) {
			if ($user_info [strtolower ( $v ['class_name'] ) . "_id"]) {
				$apis [$k] ['is_bind'] = 1;
				if ($user_info ["is_syn_" . strtolower ( $v ['class_name'] )] == 1) {
					$apis [$k] ['is_syn'] = 1;
				} else {
					$apis [$k] ['is_syn'] = 0;
				}
			} else {
				$apis [$k] ['is_bind'] = 0;
			}
			
			// if(file_exists(APP_ROOT_PATH."system/api_login/".$v['class_name']."_api.php"))
			// {
			// FP::import("libs.api_login.".$v['class_name']."_api");
			// $api_class = $v['class_name']."_api";
			// $api_obj = new $api_class($v);
			// $url = $api_obj->get_bind_api_url();
			// $apis[$k]['url'] = $url;
			// }
		}
		$GLOBALS ['tmpl']->assign ( "apis", $apis );
		$GLOBALS ['tmpl']->assign ( "user_data", $user_info );
		$GLOBALS ['tmpl']->assign ( "page_title", $GLOBALS ['lang'] ['SETWEIBO'] );
		$GLOBALS ['tmpl']->assign ( "inc_file", "inc/uc/uc_center_setweibo.html" );
		$GLOBALS ['tmpl']->display ( "page/uc.html" );
	}
	
	/**
	 * 合同列表
	 */
	public function contract() {
        // 老入口直接下掉
        exit;
		
		$user_id = intval ( $GLOBALS ['user_info'] ['id'] );
		
		$page = isset($_REQUEST ['p']) ? intval ( $_REQUEST ['p'] ) : 1;
		if ($page == 0){
			$page = 1;
		}
		
		$limit = (($page - 1) * app_conf ( "PAGE_SIZE" )) . "," . app_conf ( "PAGE_SIZE" );
		// 判断是不是配置文件设置汇赢担保帐号
		if( in_array($GLOBALS['user_info']['user_name'], dict::get('HY_DB')) ){
			
			$agencyUserInfo = array(
				'user_id' => $GLOBALS['user_info']['id'],
				'user_name' => $GLOBALS['user_info']['user_name'],
				'agency_id' => $GLOBALS['dict']['HY_DBGS'],
				'is_hy' => 1
			);
		}else{
			// 判断用户是否是担保公司审核帐号
			$agencyUserInfo = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_user  where user_id = " . $user_id );
		}

		if ($agencyUserInfo) {
			
			// 如果是配置文件帐号则只显示汇赢合同
			if($agencyUserInfo['is_hy'])
			{
			    
				//判断这个用户 是否也是担保公司的账号
				$agencyUser = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "agency_user  where user_id = " . $user_id );
				$agid = array();
				$agencyTotal = 0;
				$agencyResult = array();
				
				if($agencyUser){
					foreach ($agencyUser as $agUser){
						$agid[] = $agUser['agency_id'];
					}
						
					$agids = implode(',', $agid);
					if($agid){
						$agencyTotal = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "contract as a, " . DB_PREFIX . "deal as b where a.agency_id in ($agids) and (a.type = 2 or a.type =3 or a.type = 4) and b.contract_tpl_type != 'HY' and  a.deal_id = b.id ");
						$agencyResult = $GLOBALS ['db']->getAll ( "select a.* from " . DB_PREFIX . "contract as a, " . DB_PREFIX . "deal as b where a.agency_id in ($agids) and (a.type = 2 or a.type =3 or a.type = 4) and b.contract_tpl_type != 'HY' and  a.deal_id = b.id ORDER BY a.id  DESC limit $limit");
					}
				}
				
				if($agid){
					$agid[] = $agencyUserInfo ['agency_id'];
					$agids = implode(',', $agid);
					$agencyWhere = " a.agency_id in ($agids) ";
				}else{
					$agencyWhere = " a.agency_id = ".$agencyUserInfo ['agency_id'];
				}
				$total = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "contract as a, " . DB_PREFIX . "deal as b where $agencyWhere and (a.type = 2 or a.type =3 or a.type = 4) and b.contract_tpl_type = 'HY' and  a.deal_id = b.id and b.is_delete = 0");
				$result = $GLOBALS ['db']->getAll ( "select a.* from " . DB_PREFIX . "contract as a, " . DB_PREFIX . "deal as b where $agencyWhere and (a.type = 2 or a.type =3 or a.type = 4) and b.contract_tpl_type = 'HY' and  a.deal_id = b.id and b.is_delete = 0 ORDER BY a.id  DESC limit $limit");
				
				$total += $agencyTotal;
				$result = array_merge($result, $agencyResult);
			}
			// 如果是正常保证帐号但是配置担保公司的则不显示汇赢合同
			elseif($agencyUserInfo['agency_id'] == $GLOBALS['dict']['HY_DBGS'])
			{
			    
				$total = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "contract as a, " . DB_PREFIX . "deal as b where a.agency_id = " . $agencyUserInfo ['agency_id'] . " and (a.type = 2 or a.type =3 or a.type = 4) and b.contract_tpl_type != 'HY' and  a.deal_id = b.id and b.is_delete = 0");
				$result = $GLOBALS ['db']->getAll ( "select a.* from " . DB_PREFIX . "contract as a, " . DB_PREFIX . "deal as b where a.agency_id = " . $agencyUserInfo ['agency_id'] . " and (a.type = 2 or a.type =3 or a.type = 4) and b.contract_tpl_type != 'HY' and  a.deal_id = b.id and b.is_delete = 0 ORDER BY a.id  DESC limit $limit");
			}
			else
			{
			    
				$total = $GLOBALS ['db']->getOne ( "select count(*) from " . DB_PREFIX . "contract a left join " . DB_PREFIX . "deal b on a.deal_id = b.id  where a.agency_id = " . $agencyUserInfo ['agency_id'] . " and  (a.type = 2 or a.type =3 or a.type = 4)" );
				$result = $GLOBALS ['db']->getAll ( "select a.* from " . DB_PREFIX . "contract a left join " . DB_PREFIX . "deal b on a.deal_id = b.id where a.agency_id = " . $agencyUserInfo ['agency_id'] . " and (a.type = 2 or a.type =3 or a.type = 4) ORDER BY a.id  DESC  limit $limit" );
			}
			
			$GLOBALS ['tmpl']->assign ( "agencyUserInfo", $agencyUserInfo );
		} else {
			$total = $GLOBALS ['db']->getOne ( "select count(c.id) from " . DB_PREFIX . "contract as c, ". DB_PREFIX ."deal as b where c.user_id = " . $user_id . " AND c.deal_id = b.id AND b.is_delete = 0");
            $result = $GLOBALS ['db']->getAll ( "select c.* from " . DB_PREFIX . "contract as c, ". DB_PREFIX ."deal as b where c.user_id = " . $user_id . " AND c.deal_id = b.id AND b.is_delete = 0 order by c.id desc limit $limit");
		}
		
        $deals = $GLOBALS ['db']->getAll ( "select d.loantype,d.id,d.borrow_amount,d.user_id,d.name as aname from " . DB_PREFIX . "deal as d");
		
		$deal_names = array ();
		foreach ( $deals as $val ) {
			$deal_names [$val ['id']] = $val;
		}
		// 获取担保公司的所有帐号
		if (isset ( $agencyUserInfo ['agency_id'] )){
			if($agencyUserInfo['is_hy']){
				$reinfo = array();
				foreach (dict::get('HY_DB') as $hydb){
					$sql = "select * from ". DB_PREFIX . "user where user_name = '{$hydb}'";
					$hyinfo = $GLOBALS ['db']->getRow($sql);
					$reinfo[] = array(
						'user_id' => $hyinfo['id'],
						'user_name' => $hyinfo['user_name'],
						'agency_id' => $GLOBALS['dict']['HY_DBGS'],
						'is_hy' => 1
					);
				}
			}else{
				$reinfo = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "agency_user  where agency_id = " . $agencyUserInfo ['agency_id'] );
			}
		}
		
		
		foreach ( $result as &$rval ) {
			$rval ['create_time'] = date ( 'Y-m-d H:i:s', $rval ['create_time'] );
			$rval ['deal_name'] = get_deal_title($deal_names [$rval ['deal_id']] ['aname'], $deal_names [$rval ['deal_id']] ['bname']);
			$rval ['amount'] = isset ( $deal_names [$rval ['deal_id']] ) ? format_price ( $deal_names [$rval ['deal_id']] ['borrow_amount'] / 10000 ) . ' 万' : '';
			$rval ['loantype'] = $deal_names [$rval ['deal_id']]['loantype'];
			
			$user_name = '';
			if (isset ( $deal_names [$rval ['deal_id']] ['user_id'] )) {
				$user_name = $GLOBALS ['db']->getOne ( "select user_name from " . DB_PREFIX . "user where id = " . $deal_names [$rval ['deal_id']] ['user_id'] );
			}
			$rval ['user_name'] = $user_name;
			
			// 判断是当用户是否是借款人
			$redeal = $GLOBALS ['db']->getOne ( "select id from " . DB_PREFIX . "deal where id =  {$rval['deal_id']} and user_id = {$user_id} " );
			if ($redeal){
				$rval ['pshow'] = 1;
			}
			
			$qtmp = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_contract  where  contract_id =" . $rval ['id'] . " and user_id = " . $user_id);//and pass = 1
			if (!empty($qtmp)){
				if($qtmp['pass'] == 1){
					$rval ['pass'] = 1;
				}
				
				//单独签署状态
				$rval['alone_pass'] = $qtmp['sign_pass'];
			}
			
			$passlist = array ();
			if (isset ( $reinfo )) {
				foreach ( $reinfo as $item ) {
					$val = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "agency_contract where contract_id = {$rval['id']} and user_id = {$item['user_id']} " );
					if ($val){
						$passlist [$item ['user_id']] = array_merge ( $item, $val );
					}else{
						$passlist [$item ['user_id']] = $item;
					}
				}
				$rval ['passlist'] = $passlist;
			}
			
			//为借款合同展示期限、金额
            if ($rval[ 'type'] == 1){
				FP::import("app.common");
                $other_info = getLoadByConid($rval['id']);

                if($other_info){
                   	$rval['d_money'] = $other_info['money'];
                   	$rval['d_repay_time'] = $other_info['repay_time'];
                }
            }
		}
		
		$GLOBALS ['tmpl']->assign ( "p", $page );
		
		//print_r($result);
		
		$page = new Page ( $total, app_conf ( "PAGE_SIZE" ) ); // 初始化分页对象
		$GLOBALS ['tmpl']->assign ( "user_id", $user_id );
		$GLOBALS ['tmpl']->assign ( 'pages', $page->show () );
		$GLOBALS ['tmpl']->assign ( "contract", $result );
		$GLOBALS ['tmpl']->assign ( "page_title", '我的合同列表' );
		//$this->set_nav(array("我的P2P"=>url("index", "uc_center"), "我的合同列表"));
		$tpl_file = 'inc/uc/uc_center_contract.html';
		if ($agencyUserInfo){
			$tpl_file = 'inc/uc/uc_center_contract2.html';
		}
		
		$GLOBALS ['tmpl']->assign ( "inc_file", $tpl_file );
		$this->display();
		//$GLOBALS ['tmpl']->display ( "page/uc.html" );
	}
	
	/* 担保公司审核合同 */
	function checkContract() {	
		$user_id = intval ( $GLOBALS ['user_info'] ['id'] );
		$contract_id = intval ( $_GET ['contract_id'] ); // 合同id号
		
		$agency_id = $GLOBALS ['db']->getRow ( "select agency_id from " . DB_PREFIX . "contract where id = $contract_id" );
		$agency_id = $agency_id['agency_id'];
		                                             
		// 判断用户是否是担保公司审核帐号
		// 判断是不是配置文件设置汇赢担保帐号
		if( in_array($GLOBALS['user_info']['user_name'], dict::get('HY_DB')) )
		{
			$agencyUserInfo = array(
					'user_id' => $GLOBALS['user_info']['id'],
					'user_name' => $GLOBALS['user_info']['user_name'],
					'agency_id' => $GLOBALS['dict']['HY_DBGS'],
					'is_hy' => 1
			);
		}else{
			$agencyUserInfo = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_user  where user_id = " . $user_id );
		}
		
		$agencyUserInfo['agency_id'] = $agency_id;
		
		if (! $agencyUserInfo) {
			return app_redirect ( url ( "index" ) );
		}
		
		if ($_GET ['action'] == 'show') {
			// 获取担保公司的所有帐号
			$reinfo = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "agency_user  where agency_id = " . $agencyUserInfo ['agency_id'] );
			$info = array ();
			foreach ( $reinfo as $item ) {
				$val = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "agency_contract where contract_id = {$contract_id} and user_id = {$item['user_id']} " );
				if ($val)
					$info [] = array_merge ( $item, $val );
				else
					$info [] = $item;
			}
			
			$GLOBALS ['tmpl']->assign ( "info", $info );
			
			$GLOBALS ['tmpl']->display ( "inc/uc/uc_show_check.html" );
			exit ();
		} elseif ($_GET ['action'] == 'pass') {
			$info = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "contract  where id = " . $contract_id . "  and agency_id = " . $agencyUserInfo ['agency_id'] );
			
			if ($info) {
				// GLOBALS['db']->query("update ".DB_PREFIX."contract set agency_check = '{$agency_check}' where id = {$contract_id} ");
				// GLOBALS['db']->query("insert into ".DB_PREFIX."() ");
				$data = array (
						'user_id' => $agencyUserInfo ['user_id'],
						'user_name' => $agencyUserInfo ['user_name'],
						'agency_id' => $agencyUserInfo ['agency_id'],
						'contract_id' => $contract_id,
						'pass' => 1,
						'deal_id' => $info ['deal_id'],
						'create_time' => time () 
				);
				
				//$GLOBALS ['db']->autoExecute ( DB_PREFIX . "agency_contract", $data, "INSERT" );
				
				//获取是否已有签署数据
				$is_have_sign = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_contract  where user_name = '" . $agencyUserInfo ['user_name'] . "' and user_id = " . $agencyUserInfo ['user_id'] ." and agency_id = ". $agencyUserInfo ['agency_id']." and deal_id =".$info['deal_id']." and contract_id =".$contract_id);
				
				$mode = 'INSERT';
				$where = '';
				if($is_have_sign) {
					$mode = 'UPDATE';
					$where = "user_name = '" . $agencyUserInfo ['user_name'] . "' and user_id = " . $agencyUserInfo ['user_id'] ." and agency_id = ". $agencyUserInfo ['agency_id']." and deal_id =".$info['deal_id']." and contract_id =".$contract_id;
				}
				
				$GLOBALS ['db']->autoExecute ( DB_PREFIX . "agency_contract", $data, $mode, $where );
				
				$this->checkAllPass ( $info ['deal_id'] );
				
				/* if($info['type'] == 4){
					//判断所有《保证合同》是否签署完成
					$is_all_check = $this->checkAllWarrant( $info );
						
					//更新对应的见证人证明书(保证合同)
					if($is_all_check == 1){
						$this->update_prove($info['number'], $info['deal_id'], 2);
					}
				} */
				
				return app_redirect ( url ( "uc_center-contract?p=" . $_GET ['p'] ) );
				exit ();
			}
		}
		
		return app_redirect ( url ( "index" ) );
		exit ();
	}
	
	/* 借款人审核合同 */
	function userContract() {
		$user_id = $GLOBALS ['user_info'] ['id'];
		$user_name = $GLOBALS ['user_info'] ['user_name'];
		$contract_id = intval ( $_GET ['contract_id'] ); // 合同id号
		
		$time = time();
		
		if ($_GET ['action'] == 'pass') {
			$info = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "contract  where id = " . $contract_id . "  and user_id = " . $user_id );
			
			if ($info) {
				
				if($info['type'] == 1){
					// 修改对应deal_id 所有 借款合同 的签署时间  add by wenyanlei 20130816
					$contract_all = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "contract  where type = 1 and deal_id = " . $info ['deal_id'] ." and number = ". $info['number']);
					
					if($contract_all){
						foreach($contract_all as $cont_one){
							//替换借款合同乙方时间为 当前时间
							//签署时间替换之后，保留特殊标记，用于以后获取
							$replace = "<span id='borrow_sign_time'>".date("Y年m月d日",$time)."</span>";
							$content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/",$replace, $cont_one['content']);
							$GLOBALS ['db']->query ( "update " . DB_PREFIX . "contract set content = '".addslashes($content)."' where id = ". $cont_one['id'] );
					
							//删除已生成的合同文件
							$cont_id = ceil ( $cont_one['id'] / 1000 );
							$file = $GLOBALS['dict']['CONTRACT_PDF_PATH']."{$cont_id}/" . md5 ( $cont_one ['number'] ) . ".pdf";
							if (file_exists ( $file ))  @unlink($file);
						}
					}
				}
				
				$data = array (
						'user_id' => $user_id,
						'user_name' => $user_name,
						'agency_id' => 0,
						'contract_id' => $contract_id,
						'pass' => 1,
						'deal_id' => $info ['deal_id'],
						'create_time' => $time 
				);
				
				//获取是否已有签署数据
				$is_have_sign = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_contract  where user_name = '" . $user_name . "' and user_id = " . $user_id ." and agency_id = ". $info['agency_id']." and deal_id =".$info['deal_id']." and contract_id = ".$contract_id);
				
				$mode = 'INSERT';
				$where = '';
				if($is_have_sign) {
					$mode = 'UPDATE';
					$where = "user_name = '" . $user_name . "' and user_id = " . $user_id ." and agency_id = ". $info['agency_id']." and deal_id =".$info['deal_id']." and contract_id = ".$contract_id;
				}
				
				$GLOBALS ['db']->autoExecute ( DB_PREFIX . "agency_contract", $data, $mode, $where );
				
				//$GLOBALS ['db']->autoExecute ( DB_PREFIX . "agency_contract", $data, "INSERT" );
				
				//更新对应的见证人证明书(借款合同)
				/* if($info['type'] == 1){
					$this->update_prove($info['number'], $info['deal_id'], 1);
				} */
				
				$this->checkAllPass ( $info ['deal_id'] );
				
				return app_redirect ( url ( "uc_center-contract?p=" . $_GET ['p'] ) );
				exit ();
			}
		}
		
		return app_redirect ( url ( "index" ) );
		exit ();
	}
	
	/**
	* 借款人和出借人 -- 单独签署合同
	* @author wenyanlei  2013-12-13
	*/
	function userAloneSign() {
		
		$time = time();
		$user_id = $GLOBALS ['user_info'] ['id'];
		$user_name = $GLOBALS ['user_info'] ['user_name'];
		$contract_id = intval ( $_GET ['contract_id'] ); // 合同id号
		$sign_pass_info = $_GET ['action'];
		
		
		$info = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "contract  where id = " . $contract_id . "  and user_id = " . $user_id );
		
		if(empty($info) || false === in_array($sign_pass_info, array('pass','nopass'))){
			return app_redirect ( url ( "index" ) );
		}
		
		//获取是否已有签署数据
		$is_have_sign = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_contract  where user_name = '" . $user_name . "' and user_id = " . $user_id ." and agency_id = ". $info['agency_id']." and deal_id =".$info['deal_id']." and contract_id = ".$contract_id);
		
		$sign_pass = ($sign_pass_info == 'pass') ? 1 : 2;
	
		$data = array (
			'user_id' => $user_id,
			'user_name' => $user_name,
			'agency_id' => 0,
			'contract_id' => $contract_id,
			'deal_id' => $info ['deal_id'],
			'sign_pass' => $sign_pass,
			'sign_time' => $time,
		);
		
		$mode = 'INSERT';
		$where = '';
		if($is_have_sign) {
			$mode = 'UPDATE';
			$where = "user_name = '" . $user_name . "' and user_id = " . $user_id ." and agency_id = ". $info['agency_id']." and deal_id =".$info['deal_id']." and contract_id = ".$contract_id;
		}
		
		$GLOBALS ['db']->autoExecute ( DB_PREFIX . "agency_contract", $data, $mode, $where );
		return app_redirect ( url ( "uc_center-contract?p=" . $_GET ['p'] ) );
	}
	
 	/**
	* 担保公司 -- 单独签署合同
	* @author wenyanlei  2013-12-13
	*/
	function checkAloneSign() {
		
		$sign_pass_info = $_GET ['action'];
		$user_id = intval ( $GLOBALS ['user_info'] ['id'] );
		$contract_id = intval ( $_GET ['contract_id'] ); // 合同id号
	
		$agency_id = $GLOBALS ['db']->getOne ( "select agency_id from " . DB_PREFIX . "contract where id = $contract_id" );
		
		var_dump(dict::get('HY_DB'));
		 
		// 判断用户是否是担保公司审核帐号
		// 判断是不是配置文件设置汇赢担保帐号
		if( in_array($GLOBALS['user_info']['user_name'], dict::get('HY_DB')) ){
			$agencyUserInfo = array(
				'user_id' => $GLOBALS['user_info']['id'],
				'user_name' => $GLOBALS['user_info']['user_name'],
				'agency_id' => $GLOBALS['dict']['HY_DBGS'],
				'is_hy' => 1
			);
		}else{
			$agencyUserInfo = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_user  where user_id = " . $user_id );
		}
	
		$agencyUserInfo['agency_id'] = $agency_id;
		
		$info = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "contract  where id = " . $contract_id . "  and agency_id = " . $agencyUserInfo ['agency_id'] );
	
		if (! $agencyUserInfo || !$agency_id || !$info || false === in_array($sign_pass_info, array('pass','nopass'))) {
			return app_redirect ( url ( "index" ) );
		}
		
		//获取是否已有签署数据
		$is_have_sign = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_contract  where user_name = '" . $agencyUserInfo ['user_name'] . "' and user_id = " . $agencyUserInfo ['user_id'] ." and agency_id = ". $agencyUserInfo ['agency_id']." and deal_id =".$info['deal_id']." and contract_id =".$contract_id);
		
		$sign_pass = ($sign_pass_info == 'pass') ? 1 : 2;
	
		$data = array (
			'user_id' => $agencyUserInfo ['user_id'],
			'user_name' => $agencyUserInfo ['user_name'],
			'agency_id' => $agencyUserInfo ['agency_id'],
			'contract_id' => $contract_id,
			'deal_id' => $info ['deal_id'],
			'sign_pass' => $sign_pass,
			'sign_time' => time ()
		);
		
		$mode = 'INSERT';
		$where = '';
		if($is_have_sign) {
			$mode = 'UPDATE';
			$where = "user_name = '" . $agencyUserInfo ['user_name'] . "' and user_id = " . $agencyUserInfo ['user_id'] ." and agency_id = ". $agencyUserInfo ['agency_id']." and deal_id =".$info['deal_id']." and contract_id =".$contract_id;
		}
		
		$GLOBALS ['db']->autoExecute ( DB_PREFIX . "agency_contract", $data, $mode, $where );
		return app_redirect ( url ( "uc_center-contract?p=" . $_GET ['p'] ) );
	}
	
	/* 检查所有该通的有没有通过 */
	function checkAllPass($deal_id) {
		$a = $this->checkAllBorrower ( $deal_id );
		$b = $this->checkAllAgency ( $deal_id );
		
		if ($a && $b) {
			// 更新所有合同状态为已通过
			$GLOBALS ['db']->query ( "update " . DB_PREFIX . "contract set status = 1 where deal_id = $deal_id " );

			//更新对应的见证人证明书
			$this->update_prove($deal_id);

			// 如果全部通过则调用发邮件接口
			send_contract_email ( $deal_id );
		}
	}
	
	/*
	 * 判断所有借款人是否审核通过 返回 1 则是所有借款人审核通过 返回 0 则是没有全部通过
	 */
	function checkAllBorrower($deal_id) {
		// 获取所有需要验证的合同信息
		$contract = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "contract  where  deal_id = {$deal_id}  and   (type = 1 or type =2 or type = 6) " );
		foreach ( $contract as $item ) {
			if ($item ['user_id'] > 0) {
				$is_loaner = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "deal_load WHERE user_id={$item['user_id']} and deal_id={$deal_id}" );
				if (! empty ( $is_loaner )) {
					continue;
				}
			}

			$pass = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "agency_contract  where contract_id = {$item['id']}  and pass = 1  " );
			if (! $pass) {
				return 0;
			}
		}
		return 1;
	}
	
	/*
	 * 判断所有的担保公司帐户是否审核通过 返回 1 则是所有借款人审核通过 返回 0 则是没有全部通过
	 */
	function checkAllAgency($deal_id) {
		// 获取担保公司id号
		$info = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "contract  where deal_id = {$deal_id} and agency_id > 0 " );

		if ($info) {
			/* 判断 firstp2p_contract 里 type 为 2，3，4的合同是否已经全部审核通过 */

			// 获取担保公司的所有帐号
			// 如果是汇赢的标则读取陪置文件
			$info3 = $GLOBALS ['db']->getRow ( "SELECT * FROM " . DB_PREFIX . "deal  where id = {$deal_id}" );
			if($info3['contract_tpl_type'] == 'HY')
			{
				$reinfo = array();
				foreach (dict::get('HY_DB') as $hydb)
				{
					$sql = "select * from ". DB_PREFIX . "user where user_name = '{$hydb}'";
					$hyinfo = $GLOBALS ['db']->getRow($sql);
					$reinfo[] = array(
							'user_id' => $hyinfo['id'],
							'user_name' => $hyinfo['user_name'],
							'agency_id' => $GLOBALS['dict']['HY_DBGS'],
							'is_hy' => 1
					);
				}
			}
			else
				$reinfo = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "agency_user  where agency_id = " . $info ['agency_id'] );

			$userArr = array ();
			foreach ( $reinfo as $item )
				$userArr [$item ['user_id']] = $item ['user_id'];

			// 获取所有需要验证的合同信息
			$contract = $GLOBALS ['db']->getAll ( "SELECT * FROM " . DB_PREFIX . "contract  where  deal_id = {$info['deal_id']} and agency_id = {$info['agency_id']}  and (type = 2 or type =3 or type = 4)" );
			$contractArr = array ();
			foreach ( $contract as $itme ) {
				// 如果通过的数小于担保公司帐号总数则认为没有全部通过审核
				$passNum = $GLOBALS ['db']->getOne ( "SELECT count(*) FROM " . DB_PREFIX . "agency_contract  where contract_id = {$itme['id']}  and pass = 1  " );
				if ($passNum < count ( $userArr )) {
					return 0;
				}
			}


			return 1;
		}
		return 0;
	}
	
	/**
	* 签署合同时，更新见证人表信息
	* @author wenyanlei  2013-8-21
	* @param $deal_id int 借款申请id
	* @return NULL
	*/
	private function update_prove($deal_id){
		if(intval($deal_id) <= 0)	return;
		$GLOBALS['db']->query("update ".DB_PREFIX."deal_load_prove set effect_time = '".get_gmtime()."' where deal_id =".intval($deal_id));
	}

	/**
	* 判断某个投标的所有的保证合同 是否都签署完
	* @author wenyanlei  2013-8-26
	* @param $contract array 合同信息
	* @return int
	*/
	private function checkAllWarrant( $contract ) {
		
		if($contract ['agency_id'] == 0)	return 0;
		//获取担保公司的所有帐号
		$agency_user = $GLOBALS ['db']->getAll ( "SELECT user_id FROM " . DB_PREFIX . "agency_user where agency_id = " . $contract ['agency_id'] );
		
		if(count ( $agency_user ) == 0)		return 0;
			
		$uid = array();
		foreach($agency_user as $aval){
			
			$uid[] = $aval['user_id'];
		}
		if(count ( $uid ) == 0)		return 0;
		
		$uids = implode(',', $uid);
		
		$passNum = $GLOBALS ['db']->getOne ( "SELECT count(*) FROM " . DB_PREFIX . "agency_contract  where contract_id = {$contract['id']}  and pass = 1 and user_id in ($uids)" );
			
		if ($passNum < count ( $agency_user )) {
			return 0;
		}
		
		return 1;
	}
	
	// ajax获取合同内容 合同pdf文件下载
	public function download() {
		$id = isset ( $_REQUEST ['id'] ) ? intval ( $_REQUEST ['id'] ) : 0;
		
		$ajax = 0;
		if(isset ( $_REQUEST ['tag'] )){
			$ajax = 1;
			if($_REQUEST ['tag'] != 'download'){
				$this->download_return($ajax);
			}
		}
		
		if($id <= 0 || ! isset ( $GLOBALS ['user_info'] ['id'] )){
			$this->download_return($ajax);
		}
		
		$contract = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "contract where id = $id" );
		
		$is_own = $this->check_own($contract);
		
		if(!$is_own){
			$this->download_return($ajax);
		}
			
        //去掉nl2br换行
		if (app_conf ( "CONTRACT_END_TIME" ) > $contract ['create_time']) {
			$contract ['content'] = nl2br ( $contract ['content'] );
		}
        
		if ($ajax == 1) {//下载
			$filename = ceil ( $id / 1000 );
			$file_path = $GLOBALS['dict']['CONTRACT_PDF_PATH'].$filename.'/';
			$pdf_path = $file_path . md5 ( $contract ['number'] ) . ".pdf";
			
			if (! file_exists ( $pdf_path )) {
				FP::import("libs.tcpdf.tcpdf");
				FP::import("libs.tcpdf.mkpdf");
				$mkpdf = new Mkpdf ();
				
				if (! is_dir ( $file_path )){
					mkdir ( $file_path );
				}
				$mkpdf->mk ( $pdf_path, $contract ['content'] );
			}
			
			header ( "Content-type: application/octet-stream" );
			header ( 'Content-Disposition: attachment; filename="' . basename ( $pdf_path ) . '"' );
			header ( "Content-Length: " . filesize ( $pdf_path ) );
			readfile ( $pdf_path );
		} else {//显示
			$str = hide_message($contract['content']);
			echo $str;
		}
	}
	
	private function check_own($contract){
		if(empty($contract)){
			return false;
		}
		
		//汇赢
		$tpl_type = $GLOBALS ['db']->getOne ( "select contract_tpl_type from " . DB_PREFIX . "deal where id = ".$contract['deal_id'] );
	
		$return = true;
		if($tpl_type == 'HY' && $contract['agency_id'] > 0){
			if(!in_array($GLOBALS['user_info']['user_name'], dict::get('HY_DB'))){
				$return = false;
			}
		}elseif($contract['user_id'] > 0 && $contract['user_id'] != $GLOBALS['user_info']['id']){
			$return = false;
		}elseif($contract['agency_id'] > 0){
			$is_have_agency_user = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "agency_user where agency_id = {$contract['agency_id']} and user_name = '{$GLOBALS['user_info']['user_name']}' and user_id = {$GLOBALS['user_info']['id']}" );
			if(empty($is_have_agency_user)){
				$return = false;
			}
		}
		
		return $return;
	}
	
	private function download_return($ajax = 0){
		if($ajax == 0){
			echo '<script>window.parent.location.href="/404.html"</script>';
		}else{
			return app_redirect ('/404.html');
		}
		exit;
	}
	/**
	 * 用户投资概述
	 * @param unknown $uid
	 */
	/* protected function get_invest_overview($uid){
		if(!$uid) return false;
		$data = array();
		//回款中
		$sql1 = "SELECT COUNT(*) AS counts,SUM(d_l.money) AS money FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status = 4 AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = {$uid}";
// 		$sql1 = "SELECT COUNT(*) AS counts,SUM(money) AS money FROM ".DB_PREFIX."deal_loan_repay  WHERE loan_user_id={$uid} AND status = 0 AND type=1";
		$info1 = $GLOBALS ['db']->getRow($sql1);
		$info1['text'] = '回款中';
		$data[] = $info1;
		//投标中
		$sql2 = "SELECT COUNT(*) AS counts,SUM(d_l.money) AS money FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status in (1,2) AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = {$uid}";
		$info2 = $GLOBALS ['db']->getRow($sql2);
		$info2['text'] = '投标中';
		$data[] = $info2;
		//已回款
		$sql3 = "SELECT COUNT(*) AS counts,SUM(d_l.money) AS money FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status = 5 AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = {$uid}";
		$info3 = $GLOBALS ['db']->getRow($sql3);
		$info3['text'] = '已回款';
		$data[] = $info3;
		//总额
		$info4['text'] = '总计';
		$info4['counts'] = $info1['counts'] + $info2['counts'] + $info3['counts'];
		$info4['money'] = $info1['money'] + $info2['money'] + $info3['money'];
		$data[] = $info4;
		
		return $data;
	} */
	/**
	 * 回款计划
	 * @param unknown $uid
	 */
	/* protected function get_deal_repay($uid){
		if(!$uid) return false;
		$data = array();
		$sql = "SELECT COUNT(*) AS counts ,SUM(money) AS money FROM `firstp2p_deal_loan_repay` WHERE type in (1,2,3,4,5,7) and money!=0 and loan_user_id = {$uid} ";
		//本月
		$m = ' AND '.mktime(-8,0,0,date('m'),1,date('Y'))." <= time AND time <= ".mktime(15,59,59,date('m'),date('t'),date('Y'));
		$info1 = $GLOBALS ['db']->getRow($sql.$m);
		$info1['text'] = '本月';
		$data[] = $info1;
		//下月
		$m = ' AND '.mktime(-8,0,0,date('m')+1,1,date('Y'))." <= time AND time <= ".mktime(15,59,59,date('m')+1,date('t'),date('Y'));
		$info2 = $GLOBALS ['db']->getRow($sql.$m);
		$info2['text'] = '下月';
		$data[] = $info2;
		//本年
		$m = ' AND '.mktime(-8,0,0,1,1,date('Y'))." <= time AND time < ".mktime(-8,0,0,1,1,date('Y')+1);
		
		$info2 = $GLOBALS ['db']->getRow($sql.$m);
		$info2['text'] = '本年';
		$data[] = $info2;
		//总计
		$m = '';
		$info3 = $GLOBALS ['db']->getRow($sql.$m);
		
		$info3['text'] = '总计';
		$data[] = $info3;
		return $data;
	} */
}
?>
