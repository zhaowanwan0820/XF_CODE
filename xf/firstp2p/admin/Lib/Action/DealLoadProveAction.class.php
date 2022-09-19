<?php
// +----------------------------------------------------------------------
// | 见证人证明书
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------

FP::import("libs.common.app");
class DealLoadProveAction extends CommonAction {
	// 首页
	public function index() {
		$map = array ();
		$_REQUEST ['listRows'] = 20;
		
		$name = $this->getActionName ();
		$model = D ( $name );
		
		if (isset ( $_REQUEST ['_order'] )) {
			$order = $_REQUEST ['_order'];
		} else {
			$order = $model->getPk ();
		}
		$sort = 'desc';
		if (isset ( $_REQUEST ['_sort'] )) {
			$sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
		}
		// 取得满足条件的记录数
		$count = $model->where ( $map )->count ( 'id' );
		
		if ($count > 0) {
			// 创建分页对象
			$listRows = '';
			if (! empty ( $_REQUEST ['listRows'] ))
				$listRows = $_REQUEST ['listRows'];
			$p = new Page ( $count, $listRows );
			
			// 分页查询数据
			$voList = $model->where ( $map )->order ( "`".$order."` ".$sort )->limit ( $p->firstRow . ',' . $p->listRows )->findAll ();
			
			foreach($voList as $key => &$val){
				
				$load_user_id = $GLOBALS['db']->getOne('select user_id from '.DB_PREFIX.'deal_load where id ='.$val['load_id']);
				
				if(empty($load_user_id)){
					unset($voList[$key]);
					continue;
				}
				
				$load_user_name = $this->get_real_name($load_user_id);
				
				$val['load_user_id'] = $load_user_id;
				$val['load_user_name'] = $load_user_name;
				
				$borrow_user = $GLOBALS['db']->getRow('select * from '.DB_PREFIX.'deal where id ='.$val['deal_id']);
				
				if(empty($borrow_user)){
					unset($voList[$key]);
					continue;
				}
				
				$borrow_user_info = get_deal_borrow_info($borrow_user);
				if($borrow_user_info['is_company'] == 1){
					$borrow_user_name = $borrow_user_info['company_name'];
				}else{
					$borrow_user_name = ! empty ( $borrow_user_info ['borrow_real_name'] ) ? $borrow_user_info ['borrow_real_name'] : $borrow_user_info ['borrow_user_name'];
				}
				
				//$val['deal_id'] = $borrow_user['id'];
				$val['deal_name'] = $borrow_user['name'];
				$val['borrow_user_name'] = $borrow_user_name;
				$val['borrow_user_id'] = $borrow_user['user_id'];
				
				if(isset($borrow_user['agency_id'])){
					$agency_name = $GLOBALS['db']->getOne('select name from '.DB_PREFIX.'deal_agency where id ='.$borrow_user['agency_id']);
					$val['agency_id'] = $borrow_user['agency_id'];
					$val['agency_name'] = $agency_name;
				}
				
				//合同创建时间
				$type = $val['type'] == 1 ? 1 : 4;
				$deal = array(
						'id' => $val['deal_id'], 
						'parent_id' => -1
				);
				$contract_num = get_contract_number($deal, $load_user_id, $val['load_id'], $type);

				$val['cron_time'] = $GLOBALS['db']->getOne('select create_time from '.DB_PREFIX.'contract where number =  "'.$contract_num.'" and type = '.$type.' and deal_id = '.$val['deal_id'].' and user_id = '. $load_user_id);
			}

			// 分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val ))
					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
			}
			
			// 分页显示
			$page = $p->show ();
			
			// 列表排序显示
			$sortImg = $sort; // 排序图标
			$sortAlt = $sort == 'desc' ? l ( "ASC_SORT" ) : l ( "DESC_SORT" ); // 排序提示
			$sort = $sort == 'desc' ? 1 : 0; // 排序方式
			                                 
			// 模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'sort', $sort );
			$this->assign ( 'order', $order );
			$this->assign ( 'sortImg', $sortImg );
			$this->assign ( 'sortType', $sortAlt );
			$this->assign ( "page", $page );
			$this->assign ( "nowPage", $p->nowPage );
		}
		$this->display ();
	}
	
	private function get_real_name($id) {
		if (! $id)
			return false;
		$userinfo = $GLOBALS ['db']->getRow ( "select user_name,real_name from " . DB_PREFIX . "user where id = " . intval ( $id ) );
		$user_name = ! empty ( $userinfo ['real_name'] ) ? $userinfo ['real_name'] : $userinfo ['user_name'];
		return $user_name;
	}
	
	public function edit() {
		$id = empty ( $_GET ['id'] ) ? '' : intval ( $_GET ['id'] );
		$user_id = empty ( $_GET ['user_id'] ) ? '' : intval ( $_GET ['user_id'] );
		
		if (empty ( $id ) || empty( $user_id ))
			$this->error ( "非法操作！" );
		
		$name = $this->getActionName ();
		$model = D ( $name );
		$prove_info = $model->where ( "id = '" . $id . "'" )->find ();
		
		$this->assign ( 'prove_info', $prove_info );
		
		$prove_user = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "user_prove where user_id = " . $user_id );
		$prove_user['province'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."region_conf where id =".$prove_user['province_id']);
		$prove_user['city'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."region_conf where id =".$prove_user['city_id']);

		$this->assign('prove_user', $prove_user);
		
		$this->display ();
	}
	
	public function update() {
		$id = empty ( $_POST ['id'] ) ? '' : intval ( $_POST ['id'] );
		$is_send = empty ( $_POST ['is_send'] ) ? '' : $_POST ['is_send'];
		
		if (empty ( $id )) {
			$this->error ( "非法操作！" );
		}
		
		$name = $this->getActionName ();
		$model = D ( $name );
		$prove_info = $model->where ( "id = '" . $id . "'" )->find ();
		
		if($prove_info['type'] != 1 && $prove_info['type'] != 2){
			$this->error ( "非法操作！" );
		}
		
		if($prove_info['effect_time'] == 0){
			$this->error ( "合同尚未签署！" );
		}
		
		// 更新数据
		$data = array ();
		
		$data ['is_send'] = $is_send == 1 ? 1 : 0;
		$data ['remark'] = empty ( $_POST ['remark'] ) ? '' : htmlspecialchars( $_POST ['remark'] );
		
		if($data['is_send'] == 1){
			$data['send_time'] = get_gmtime();
		}
		
		$update = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_load_prove",$data,"UPDATE","id=".$id);
		
		if (false !== $update) $this->success ( L ( "UPDATE_SUCCESS" ) );
		$this->error ( L ( "UPDATE_FAILED" ) );
	}
	
	public function export(){
		
		$id = empty ( $_GET ['id'] ) ? '' : intval ( $_GET ['id'] );
		
		if (empty ( $id ))
			$this->error ( "非法操作！！！" );
		
		$name = $this->getActionName ();
		$model = D ( $name );
		$prove_info = $model->where ( "id = '" . $id . "'" )->find ();
		
		if($prove_info['type'] != 1 && $prove_info['type'] != 2){
			$this->error ( "非法操作！！！" );
		}
		
		if($prove_info['effect_time'] == 0){
			$this->error ( "合同尚未签署！" );
		}
		
		//出借人
		$load_user_info = $GLOBALS['db']->getRow('select user_id,money from '.DB_PREFIX.'deal_load where id ='.$prove_info['load_id']);
		$load_user_name = $this->get_real_name($load_user_info['user_id']);
		
		//借款人
		$deal_info = $GLOBALS['db']->getRow('select * from '.DB_PREFIX.'deal where id ='.$prove_info['deal_id']);
		//$borrow_user_name = $this->get_real_name($deal_info['user_id']);
		$borrow_user_info = get_deal_borrow_info($deal_info);
		if($borrow_user_info['is_company'] == 1){
			$borrow_user_name = $borrow_user_info['company_name'];
		}else{
			$borrow_user_name = ! empty ( $borrow_user_info ['borrow_real_name'] ) ? $borrow_user_info ['borrow_real_name'] : $borrow_user_info ['borrow_user_name'];
		}
		$deal = array('id' => $prove_info['deal_id'], 'parent_id' => -1);
		
		//担保机构
		$agency_name = $GLOBALS['db']->getOne('select name from '.DB_PREFIX.'deal_agency where id ='.$deal_info['agency_id']);
		
		//证明书模板
		$type = 1;
		$type_name = '借款合同';
		$tmpl = 'TPL_DEAL_LOAN_PROVE';
		
		if($prove_info['type'] == 2){
			$type = 4;
			$type_name = '保证合同';
			$tmpl = 'TPL_DEAL_WARRANT_PROVE';
		}
		
		$template = $GLOBALS['db']->getOne("select content from ".DB_PREFIX."msg_template where name = '".$tmpl."'");
		
		//解析模板
		$notice['agency_name'] = $agency_name;
		$notice['loan_real_name'] = $load_user_name;
		$notice['borrow_real_name'] = $borrow_user_name;
		$notice['year'] = to_date($prove_info['effect_time'],'Y');
		$notice['month'] = to_date($prove_info['effect_time'],'m');
		$notice['day'] = to_date($prove_info['effect_time'],'d');
		$notice['money'] = $load_user_info['money'];
		$notice['repay_time'] = $deal_info['repay_time'];	
		$notice['repay_time_unit'] = $deal_info['loantype'] == 5 ? $deal_info['repay_time'].'天' : $deal_info['repay_time'].'个月';	
		$notice['sign_time'] = to_date($prove_info['effect_time'], 'Y年m月d日');	
		$notice['contract_num'] = get_contract_number($deal, $load_user_info['user_id'], $prove_info['load_id'], $type);
			
		$GLOBALS['tmpl']->assign("notice",$notice);
		$msg = $GLOBALS['tmpl']->fetch("str:".$template);		
		
		$time = to_date($prove_info['apply_time'], 'YmdHi');
		$filename = '见证人证明书（'.$type_name.'）-出借人-'.$load_user_name.'-'.$time;
		export_word_doc($msg, $filename);
	}
}
?>
