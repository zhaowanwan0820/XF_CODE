<?php
// +----------------------------------------------------------------------
// | 借款保证人管理
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------
class DealGuarantorAction extends CommonAction {
	// 首页
	public function index() {
		// 开始加载搜索条件
		$deal_id = 0;
		if (intval ( $_REQUEST ['did'] ) > 0){
			$deal_id = intval ( $_REQUEST ['did'] );
		}
        
        if (intval ( $_REQUEST ['gid'] ) > 0){
			$guarantor_id = intval ( $_REQUEST ['gid'] );
		}
		
		$deal_list = M("Deal")->query("SELECT `id`,`name`,`parent_id` FROM ".DB_PREFIX."deal");
		$relation = $GLOBALS['dict']['DICT_RELATIONSHIPS'];
		$status = $GLOBALS['dict']['DEAL_GUARANTOR_STATUS'];
		
		$parent_id = 0;
		foreach($deal_list as $key => $val){
			$deal_list[$val['id']] = $val['name'];
			if(($val['id'] == $deal_id) && $val['parent_id'] > 0){
				$parent_id = $val['parent_id'];
			}
		}
		
		$where = '';
		if($deal_id)	$where = ' where deal_id = '. $deal_id;
		if($parent_id) 	$where = " where deal_id in ($deal_id,$parent_id)";
        if($guarantor_id) $where .= ' and id='.$guarantor_id;
		$all = M(MODULE_NAME)->query("select * from ".DB_PREFIX."deal_guarantor $where order by id desc");
		
		if($all){
			foreach($all as &$val){
				$val['relationship'] = $relation[$val['relationship']];
				$val['status'] = $status["{$val['status']}"];
				$val['deal_title'] = $deal_list[$val['deal_id']];
				$val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
			}
		}
		
		$this->assign("all", $all);
		$this->display ();
		return;
	}
	// 编辑
	public function edit() {
		if (empty ( $_POST ['submit'] )) {
			$model = M ( 'DealGuarantor' );
			$id = intval($_REQUEST['id']);
			$result = $model->where ( "id = '$id'" )->find ();
			$this->assign ( 'result', $result );
			$this->display ();
		} else {
			$id = empty ( $_POST ['id'] ) ? '' : intval ( $_POST ['id'] );
			if (empty ( $id ))
				$this->error ( "更新失败，请重试！" );
			$model = M(MODULE_NAME);
			$data = $model->create ();
			$status = $model->where ( "id = " . $id )->save ( $data );
			if ($status)
				$this->success ( "更新成功！" );
			$this->error ( "更新失败，请重试！" );
		}
	}
	//删除
	public function foreverdelete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );		

				$rel_data = M(MODULE_NAME)->where($condition)->findAll();				
				foreach($rel_data as $data)
				{
					$info[] = $data['id'];	
				}
				if($info) $info = implode(",",$info);
				$list = M(MODULE_NAME)->where ( $condition )->delete();			
				if ($list!==false) {
					M(MODULE_NAME)->where(array ('id' => array ('in', explode ( ',', $id ) )))->delete();
					save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
					$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
				} else {
					save_log($info.l("FOREVER_DELETE_FAILED"),0);
					$this->error (l("FOREVER_DELETE_FAILED"),$ajax);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}
}
?>