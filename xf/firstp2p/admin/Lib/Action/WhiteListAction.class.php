<?php
// +----------------------------------------------------------------------
// | 合同管理
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------
class WhiteListAction extends CommonAction {
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
		
		if(trim($_REQUEST['id'])!='')
		{
			$map[DB_PREFIX.'adviser.id'] = intval($_REQUEST['id']);
		}
		
		// 取得满足条件的记录数
		$count = $model->where ( $map )->count ( 'id' );
		
		if ($count > 0) {
			// 创建分页对象
			$listRows = '';
			if (! empty ( $_REQUEST ['listRows'] ))	$listRows = $_REQUEST ['listRows'];
			$p = new Page ( $count, $listRows );
			
			// 分页查询数据
			$voList = $model->where ( $map )->order ( "`" . $order . "` " . $sort )->limit ( $p->firstRow . ',' . $p->listRows )->findAll ();
			
			foreach ( $voList as &$val ){
				$val['user_name'] = get_user_name($val['user_id']);
				$val['create_time'] = to_date($val['create_time']);
				$val['start_time'] = $val['start_time'] == 0 ? '无限制' : to_date($val['start_time'], 'Y-m-d');
				$val['end_time'] = $val['end_time'] == 0 ? '无限制' : to_date($val['end_time'], 'Y-m-d');
				$val['type_str'] = $val['type'] == 0 ? '白名单' : '黑名单';
				$val['project_str'] = $GLOBALS['dict']['WHITE_LIST']["{$val['project']}"];
			}
			
			// 分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val )) $p->parameter .= "$key=" . urlencode ( $val ) . "&";
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
	
	public function add(){
		
		$white_list = $GLOBALS['dict']['WHITE_LIST'];
		$this->assign ( "white_list", $white_list );
		
		$this->display ();
	}
	
	public function edit(){
		
		$id = empty($_GET['id']) ? '' : intval($_GET['id']);
		
		if(empty($id)) $this->error("非法操作！！！");
		
		$info = M("white_list")->where("id = '".$id ."'")->find();
		
		$info['start_time'] = $info['start_time'] == 0 ? 0 : to_date($info['start_time'], 'Y-m-d');
		$info['end_time'] = $info['end_time'] == 0 ? 0 : to_date($info['end_time'], 'Y-m-d');
		
		$white_list = $GLOBALS['dict']['WHITE_LIST'];
		$this->assign ( "white_list", $white_list );
		
		#print_r($info);
		
		$this->assign ( 'info', $info );

		$this->display ();
	}
	
	
	public function insert()
	{
		$data['user_id'] = intval($_POST['user_id']);
		$data['project'] = $_POST['project'];
		$data['type'] = intval($_POST['type']);
		$data['start_time'] = $_POST['start_time'] == 0 ? 0 : to_timespan($_POST['start_time']);
		$data['end_time'] = $_POST['end_time'] == 0 ? 0 : to_timespan($_POST['end_time'] . ' 23:59:59');
		$data['create_time'] = get_gmtime();
		
		// 验证重复数据
		$ekn = M('WhiteList')->where(" user_id = '".$data['user_id']."' and project = '". $data['project'] ."' " )->find();
		if($ekn)
		{
			$this->error("该项目下用户已经添加过相关名单");
			return;
		}
		
		
		$info = M('WhiteList')->add($data);
		
		if ($info) {
			$this->success(L("INSERT_SUCCESS"));
		} else {
			$this->error(L("INSERT_FAILED"));
		}
	}
	
	public function update(){
		$id = intval($_POST['id']);
		$data['user_id'] = intval($_POST['user_id']);
		$data['project'] = $_POST['project'];
		$data['type'] = intval($_POST['type']);
		$data['start_time'] = $_POST['start_time'] == 0 ? 0 : to_timespan($_POST['start_time']);
		$data['end_time'] = $_POST['end_time'] == 0 ? 0 : to_timespan($_POST['end_time'] . ' 23:59:59');
		
		// 验证重复数据
		$ekn = M('WhiteList')->where(" user_id = '".$data['user_id']."' and project = '". $data['project'] ."' and id != '".$id."' " )->find();
		if($ekn)
		{
			$this->error("该项目下用户已经添加过相关名单");
			return;
		}
		
		
		$options['where'] = "id = '".$id ."'";
		
		$list=M('WhiteList')->save ($data, $options);
		
		if (false !== $list) {
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			$this->error(L("UPDATE_FAILED"));
		}
		
	}
	
	public function delete()
	{
		$id = intval($_REQUEST['id']);
		
		if($id)
		{
			M("WhiteList")->where("id = '".$id ."'")->delete();
			$this->success (l("DELETE_SUCCESS"),$ajax);
			exit;
		}
		
		$this->error (l("DELETE_FAILED"),$ajax);
	}
	
	
			
}
?>