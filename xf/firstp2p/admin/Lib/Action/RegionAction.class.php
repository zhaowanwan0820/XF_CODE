<?php
class RegionAction extends CommonAction{
	
	/**
	 * 对应模块 方便切表
	 * @var unknown
	 */
	private $_model = "DeliveryRegion";
	
	public function index()
	{
		//国家列表
		$coutry_list = M($this->_model)->where("region_level = 1")->order("id asc")->findAll();//getField('id,name',true);
		$tree = array();
		foreach($coutry_list as $coutry){
			$arr = array();
			$coutry_id = $coutry['id'];
			$arr['id'] = $coutry_id;
			$arr['isParent'] = true;
			$arr['name'] = $coutry['name'];
			$arr['t'] = $coutry['name'];
			$arr['open'] = true;
			$arr['get'] = 'coutry_id='.$coutry_id;
			$tree[] = $arr;
			$arr = array();
			//省
			$province_list = M($this->_model)->where("region_level = 2 AND pid =".$coutry['id'])->order("id asc")->findAll();
			foreach($province_list as $province){
				$arr['isParent'] = true;
				$arr['id'] = $province['id'];
				$arr['pId'] = $coutry_id;
				$arr['name'] = $province['name'];
				$arr['t'] = $province['name'];
				$arr['get'] = 'coutry_id='.$coutry_id.'&province_id='.$province['id'];
				$tree[] = $arr;
				$arr = array();
				$city_list = M($this->_model)->where("region_level = 3 AND pid =".$province['id'])->order("id asc")->findAll();
				foreach($city_list as $city){
					$arr = array();
					$arr['isParent'] = true;
					$arr['id'] = $city['id'];
// 					$arr['id'] = $coutry_id.$province['id'].$city['id'];
					$arr['pId'] = $city['pid'];
					$arr['name'] = $city['name'];
					$arr['t'] = $city['name'];
					$arr['get'] = 'coutry_id='.$coutry_id.'&province_id='.$province['id'].'&city_id='.$city['id'];
					$tree[] = $arr;
					$arr = array();
					$region_list = M($this->_model)->where("region_level = 4 AND pid =".$city['id'])->order("id asc")->findAll();
					foreach($region_list as $region){
						$arr = array();
						$arr['id'] = $region['id'];
						// 					$arr['id'] = $coutry_id.$province['id'].$city['id'];
						$arr['pId'] = $region['pid'];
						$arr['name'] = $region['name'];
						$arr['t'] = $region['name'];
						$arr['get'] = 'coutry_id='.$coutry_id.'&province_id='.$province['id'].'&city_id='.$city['id'].'&region_id='.$region['id'];
						$tree[] = $arr;
						$arr = array();
					}
				}
			}
		}
		
		$this->assign("tree",json_encode($tree));
		$this->display();
	}
	/**
	 * 显示节点信息
	 */
	public function nodeshow(){
		$id = getRequestInt('id');
		$coutry_id   = getRequestInt('coutry_id');
		$province_id = getRequestInt('province_id');
		$city_id     = getRequestInt('city_id');
		$region_id   = getRequestInt('region_id');
		
		if($id){
			$node_info = M($this->_model)->where("id=".$id)->find();
		}
		
		if(!$region_id){
			$this->assign("is_add",true);
		}
		
		$child = M($this->_model)->where("pid=".$id)->find();
		if(!$child){
			$this->assign("is_del",true);
		}
		
		$this->assign("node",$node_info);
		$html = $this->fetch();
		echo $html;
		exit;
	}
	/**
	 * 节点修改 添加
	 */
	public function nodeedit(){
		$id = getRequestInt('id');
		$type = getRequestString('type');
		$name = getRequestString('name');
		
		if(!$id || !$type || !$name){
			$this->error("参数错误！",1);
			exit;
		}
		
		$data = array();
		if($type == 'add'){
			$data = M($this->_model)->where("id=".$id)->find();
			$data['name'] = $name;
			$data['pid'] = $id;
			$data['region_level'] = $data['region_level'] + 1;
			unset($data['id']);
			M($this->_model)->add($data);
		}
		
		if($type == 'edit'){
			$data['name'] = $name;
			M($this->_model)->where("id=".$id)->save($data);
		}
		make_delivery_region_js(true);
		$this->ajaxReturn()	;
		exit;
	}
	/**
	 * 删除节点
	 */
	public function nodedel(){
		$id = getRequestInt('id');
		$child = M($this->_model)->where("pid=".$id)->find();
		if(!$child){
			M($this->_model)->where("id=".$id)->delete();
		}else{
			$this->error("删除失败！",1);
			exit;
		}
		make_delivery_region_js(true);
		$this->ajaxReturn()	;
		exit;
	}
	
}
?>