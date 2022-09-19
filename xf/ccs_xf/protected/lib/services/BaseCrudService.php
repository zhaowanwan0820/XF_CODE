<?php
/**
 * @file BaseCrudService.php
 * @date 2013/10/25
 * 基础 增删查改service
 **/

class BaseCrudService extends  ItzInstanceService {
	
	public function __construct(  )
    {
        parent::__construct();
    }
	
	
	public function add($modelName,$data){
	    Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
	 	$BaseCurdClass= new BaseCrudClass();
		$result = $BaseCurdClass->add($modelName, $data);
		if($result!=false){
		    return $result->getAttributes();
		}else{
		    return false;
		}
	 }
	  
	
	public function  update($modelName,$data,$attr_name){
	    Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $BaseCurdClass= new BaseCrudClass();
        $result = $BaseCurdClass->update($modelName,$data,$attr_name);
        if($result!=false){
            return $result->getAttributes();
        }else{
            return false;
        }
	}
    
    /*
     * 通用的获取列表的方法
     * */
    public function get($modelName,$condition="",$offset=0,$limit=10,$order="",
    $attributes=array(),$more_criteria=null,$with_array=array(),$assoc=false,$select_array=NULL,$with_select_array=NULL){
        Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $criteria = new CDbCriteria; 
        if($condition) $criteria->condition = $condition;
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $Model = new $modelName;

        if(!empty($with_array)){
            $result = $Model->with($with_array)->findAllByAttributes($attributes,$criteria);
        }else{
            $result = $Model->findAllByAttributes($attributes,$criteria);
        }
        
        if(!empty($result)){
            if($assoc == true) return $result;  //以对象方式返回
            foreach ($result as $key=>$row) {
               $returnResult[$key] = $row->getAttributes($select_array);
               if(!empty($with_array)){
                   foreach($with_array as $item){
                       if(!empty($row->$item)){
                            $returnResult[$key][$item] = $row->$item->getAttributes($with_select_array);
                       }
                   }
               }
            }
            return $returnResult;
        }else{
            return array();
        }
    }
    /*
     * 通用的count方法
     * */
    public function count($modelName,$condition="",$offset=0,$limit=10,$order="",$attributes=array(),$more_criteria=null){
        Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $criteria = new CDbCriteria; 
        if($condition) $criteria->condition = $condition;
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        } 
        $Model = new $modelName;
     
        $result = $Model->countByAttributes($attributes,$criteria);
        return $result;
    }
    
    	
}
