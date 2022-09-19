<?php
/**
 * BwlistTypeModel.php
 *
 * 黑白名单类型信息
 * @date 2018-05-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

class BwlistTypeModel extends BaseModel {

    public function getAllList(){

        return $this->findAll('',true);
    }

    public function getOne($id){

        $id = intval($id);

        return $this->findViaSlave($id);
    }

    public function add($data){

        return $this->insert();
    }
}
