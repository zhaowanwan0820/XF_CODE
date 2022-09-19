<?php
/**
 * 合作保障机构类
 *
 */

class GuarantorNewClass {

    public function get($gid) {
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $guarantornewModel = new DwGuarantorNew();
        $criteria = new CDbCriteria;
        $attributes = array(
            "gid"    =>   $gid,   
        );  
        $guarantornewResult = $guarantornewModel->findByAttributes($attributes,$criteria);
        return $guarantornewResult;
    }
    public function getByPhone($phone)
    {
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $guarantornewModel = new DwGuarantorNew();
        $condition = ' username = :username or phone = :phone';
        $params = array(':username'=>$phone, ':phone'=>$phone);
        $guarantornewResult = $guarantornewModel->find($condition, $params);
        return $guarantornewResult;
    }

    public function getList($offset=0,$limit=10,$order='addtime desc',$condition='',$params=array(),$with_array=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $attributes = array();
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $CDbCriteria->order = $order;
        if($limit != 'All') $CDbCriteria->limit = $limit;
        $CDbCriteria->offset = $offset;
        $guarantornewModel = new DwGuarantorNew();
        $returnResult = $guarantornewModel->findAllByAttributes($attributes,$CDbCriteria);
        return $returnResult;
    }
    
    public function getCount($condition='',$params=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $attributes = array();
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $CDbCriteria->limit = 'All';
        $guarantornewModel = new DwGuarantorNew();
        $returnResult = $guarantornewModel->countByAttributes($attributes,$CDbCriteria);
        return $returnResult;
    }
    public function update($data, $attr_name){
        $guarantornewModel = new DwGuarantorNew();
        $result = $guarantornewModel->findByAttributes(array($attr_name=> $data[$attr_name]));
        if(null == $result){
            return false;
        }
        foreach($data as $key=>$value){
            if($key != $attr_name){
                $result->$key = $value;
            }
        }
        if($result->save()==false){
            Yii::log("guarantornewModel error: ".print_r($result->getErrors(),true),"error");
            return false;
        }else{
              return $result;
        }
    }
}
