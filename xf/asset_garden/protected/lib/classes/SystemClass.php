<?php
/*
 * 获取系统配置
 */

class SystemClass  {
        
    /**
     * 获取系统配置
     **/
    public function getSystem(){
        $SystemModel = new System();
        $SystemResult =$SystemModel->findAll();
        return $SystemResult;
    }
    
    /**
     * 获取文章列表
    **/
    public function getArticles($offset=0,$limit=10,$order="",$more_attributes=array(),$more_criteria=null,$condition=''){
        $ArticlerModel = new Article();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if(!empty($condition)){
			$criteria->condition = $condition;
		}
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        $attributes = array();
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $ArticlerResult =$ArticlerModel->findAllByAttributes($attributes,$criteria);
        return $ArticlerResult;
     }
    
    /*
      * 获取site信息
      * */
     public function getSiteInfo($attribute = array()){
         $siteModel = new Site();
         return $siteModel->findByAttributes($attribute);
     }
        

}
