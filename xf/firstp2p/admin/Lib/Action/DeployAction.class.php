<?php
/**
 * 贷款管理---基础数据配置
 * @author Liwei 2013-06-27
 *
 */
class DeployAction extends AuthAction{
	//首页
    public function index(){
    	$deployModule = M('Deploy');
    	$deployResult = $deployModule->select();
    	foreach ($deployResult as $val){
    		$deployList[$val['process']] = $val;
    	}
    	//dump($deployList);
		$this->assign('deployList',$deployList);
    	$this->display();
    }
 	//编辑
 	public function edit(){
 		if(empty($_POST['bc'])){
	 		$processName = !empty($_GET['process']) ? $_GET['process'] :'';
	 		if(empty($processName)) $this->error("非法操作！");
	 		$deployModule = M('Deploy');
	 		$deployResult = $deployModule->where("process = '$processName'")->find();  
	 		//dump($deployResult);	
	 		$this->assign('deployResult',$deployResult);
	 		$this->display();
 		}else{
 			$id = empty($_POST['id']) ? '' : intval($_POST['id']);
 			$data = $_POST['bc'];
 			if(empty($id)) $this->error("更新失败，请重试！");
 			$deployModule = M('Deploy');
			$status = $deployModule->where("id = ".$id)->save($data);
			if($status) $this->success("更新成功！");
			$this->error("更新失败，请重试！");
 		}	
 	}
}