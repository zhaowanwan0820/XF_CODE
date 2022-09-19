<?php
// +----------------------------------------------------------------------
// | 合同管理
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------
class AdviserAction extends CommonAction {
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
			/*
			foreach ( $voList as &$val ){
				
			}
			*/
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
	
	
	public function edit(){
		
		$id = empty($_GET['id']) ? '' : intval($_GET['id']);
		
		if(empty($id)) $this->error("非法操作！！！");
		
		$info = M("Adviser")->where("id = '".$id ."'")->find();
		
		$this->assign ( 'info', $info );

		$this->display ();
	}
	
	
	public function update(){
		$id = empty($_POST['id']) ? '' : intval($_POST['id']);
		$adviser_id = empty($_POST['adviser_id']) ? '' : intval($_POST['adviser_id']);
		$name = empty($_POST['name']) ? '' : $_POST['name'];
		$mobile = empty($_POST['mobile']) ? '' : $_POST['mobile'];
		$email = empty($_POST['email']) ? '' : $_POST['email'];
		
		if(empty($id)  || empty($name) || empty($mobile) || empty($email)){
			$this->error("非法操作！");
		}
		
		$data = array();
		
		$data['name'] = $name;
		$data['mobile'] = $mobile;
		$data['email'] = $email;
		
		//通过接口验证数据
		$url = $GLOBALS['sys_config']['P2P_API_URL'];
		$name = urlencode($name);
		$target = $url.'?act=first_p2p&type=single&n='.$name.'&p='.$mobile;
		$cu = curl_init();
		curl_setopt($cu, CURLOPT_URL, $target);
		curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($cu);
		curl_close($cu);
		
		if(!empty($ret)){
			$arr = json_decode($ret,true);
			
			$data['user_name'] = $arr['user_login_name'];
			$data['adviser_id'] = $arr['id'];
			$data['status'] = 1;
		}else{
			$data['status'] = 0;
		}
		
		$options['where'] = "id = '".$id ."'";
		
		$list=M(MODULE_NAME)->save ($data,$options);
		
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
			M("Adviser")->where("id = '".$id ."'")->delete();
			$this->success (l("DELETE_SUCCESS"),$ajax);
			exit;
		}
		
		$this->error (l("DELETE_FAILED"),$ajax);
	}
	
	
	/* 查看顾问佣金 */
	public function brokerage()
	{
		$id = intval($_REQUEST['id']);
		$p = intval($_REQUEST['p']);
		$p = $p > 0 ? $p : 1; // 当前页数
		$n = 20; // 每页显示条数
		
		$url = $GLOBALS['sys_config']['P2P_API_URL']."?act=first_p2p&type=list&u={$id}&p={$p}&n={$n}";
		
		$url=str_replace('&amp;','&',$url);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
		$result = curl_exec($curl);
		curl_close($curl);
		
		#echo $url . '<br>';
		$info = json_decode($result, true);
		$list = $info['rows'];
		
		if($info['count'] > 0)
		{
			$p = new Page ( $info['count'], $n );
			$p->parameter = "id={$id}";
			
			$page = $p->show();
			
			$this->assign ('page', $page);
		}
		
		// 获取顾问信息
		$ainfo = M("Adviser")->where("adviser_id=".$id)->find();
		$adviser_name = "<a href='".u("Adviser/index",array("id"=>$ainfo['id']))."' target='_blank'>".$ainfo['name']."</a>";
		
		$this->assign ('adviser_name', $adviser_name);
		
		$this->assign ('list', $list);
		$this->display();
	}
			
}
?>