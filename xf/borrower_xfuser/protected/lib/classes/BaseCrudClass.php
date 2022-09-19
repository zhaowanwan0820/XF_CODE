<?php
/*
 * 基础增删查改类
 */

class BaseCrudClass  {
        
    public function add($modelName,$data){
        Yii::log ( "BaseCrudClass ".__FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $model = new $modelName;
        foreach($data as $key=>$value){
            $model->$key = $value;
        }
        if($model->save()==false){
            Yii::log($modelName." error: ".print_r($model->getErrors(),true),"error");
            return false;
        }else{
            return $model;
        }
    }
    
    public function update($modelName,$data,$attr_name){
        Yii::log ( "BaseCrudClass ".__FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $model = new $modelName;
        $result = $model->findByAttributes(array($attr_name=> $data[$attr_name]));
        if($result== null){
            Yii::log("update error : no such result ".$modelName,"error");
            return false;
        }else{
            foreach($data as $key=>$value){
                if($key != $attr_name){
                    $result->$key = $value;
                }
            }
            if($result->save()==false){
                Yii::log($modelName." error: ".print_r($result->getErrors(),true),"error");
                return false;
            }else{
                return $result;
            }
        }
        
    }
    

}
