<?php
/**
 * @file BDwActiveRecord.php
 * 后台使用的类
 * @author (kuangjun@xxx.com)
 * @date 2013/12/25
 *  
 **/
class BDwActiveRecord extends DwActiveRecord {

    protected $saveLog=true;
	
    //修改之前记录日志
    public function save($runValidation=true, $attributes=NULL){
        if($this->saveLog){
            $operation = $this->isNewRecord ? 'create' : 'update';
            if(parent::save()){
                $result = $this->log(array('operation'=>$operation));
                return true;
            }
            return false;
        }
        return parent::save($runValidation, $attributes);
    }
}
