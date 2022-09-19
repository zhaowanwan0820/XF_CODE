<?php
/**
 * @file ReturnService.php
 * @author zlei
 * @date 2016/10/27
 *
 **/
class ReturnService extends ItzInstanceService {

    protected $expire1 = 180;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;

    public function __construct(  )
    {
        parent::__construct();
    }

    /**
     * 获取返回信息列表
     * @param unknown $nid
     * @param string $code
     * @return boolean|multitype:|unknown
     */
    public function getReturn($nid,$code=''){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
    	if(empty($nid)){
    		return false;
    	}
    	$list = array();
    	$id = $this->getReturnTypeId($nid);
    	if(!$id){
    		return $list;
    	}
    	$key = 'return_info_list_id_'.$id;
    	$list = $this->getJson($key);
    	if(empty($list)){
    		$expire = 600;
	    	$criteria = new CDbCriteria;
	    	//$criteria->select = 'id ';
	    	$criteria->condition = " status=1 AND return_type_id=:return_type_id ";
	    	$criteria->params[':return_type_id'] = $id;
	    	$info = ItzReturnInfo::model()->findAll($criteria);
	    	if($info){
	    		foreach ($info as $val){
	    			$val = $val->attributes;
	    			$list[$val['code']] = $val['content'];
	    		}
	    		$this->setJson($key, $list, $expire);
	    	}
    	}
    	if($code){
    		$list = isset($list[$code]) ? $list[$code] : '';
    	}
    	return $list;
    }
    /**
     * 获取类型 id
     * @param unknown $nid
     * @return string|unknown
     */
    public function getReturnTypeId($nid){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
    	if(empty($nid)){
    		return 0;
    	}
    	$key = 'return_type_id_'.$nid;
    	$id = RedisService::getInstance()->get($key);
    	if(empty($id)){
    		$expire = 9999;
	    	$criteria = new CDbCriteria;
	    	$criteria->select = 'id ';
	    	$criteria->condition = " trigger_point=:trigger_point ";
	    	$criteria->params[':trigger_point'] = $nid;
	    	$info = ItzReturnType::model()->find($criteria)->attributes;
	    	if($info){
	    		$id = $info['id'];
	    		RedisService::getInstance()->set($key, $id, $expire);
	    	}
    	}
    	return intval($id);
    }
    /**
     * [set set cache]
     * @param [type] $key    [description]
     * @param [type] $value  [description]
     * @param [type] $expire [description]
     */
    public function setJson($key, $value, $expire = 9999){
    	return RedisService::getInstance()->set($key, $value, $expire);
    }
    /**
     * [get get cache]
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function getJson($key){
    	$result = RedisService::getInstance()->get($key);
    	return $result;
    }
    
}