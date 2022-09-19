<?php

class LinkageClass {

    /**
     * 获取联动列表
     **/
    public function getListByTypeId($typeid, $order="",$offset=0,$limit=10,$more_criteria=NULL){
        $LinkageModel = new Linkage();
        $criteria = new CDbCriteria; 
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        if($more_criteria) $criteria->mergeWith($more_criteria);
        $attributes = array(
          "type_id"    =>   $typeid,
        );
        $LinkageResult = $LinkageModel->findAllByAttributes($attributes,$criteria);
        return $LinkageResult;
     }

    /**
     * 根据Nid获取联动值，并分组
     * @param: array $nids
     * @return: array $returnValueRecords
     */
    public function getListByNids($nids) {
        $returnValueRecords = array();
        // 获取类型
        $LinkageTypeModel = new LinkageType();
        $typeRecords = $LinkageTypeModel->findAllByAttributes( array('nid' => $nids), array('index' => 'id') );
        $typeIds = array_keys($typeRecords);
        // 获取联动值
        if($typeIds) {
            $LinkageModel = new Linkage();
            $valueRecords = $LinkageModel->findAllByAttributes( array('type_id' => $typeIds), array('index' => 'id') );

            $tmpTypeNid = '';
            foreach($valueRecords as $key => $row) {
                $tmpTypeNid = $typeRecords[$row->getAttribute('type_id')]->getAttribute('nid');
                $returnValueRecords[$tmpTypeNid][$key] = $row;
            }
            unset($typeRecords);
            unset($valueRecords);
        }
        return $returnValueRecords; 
    }

     /**
     * 获取联动类型数据
     **/
    public function getTypeByNid($nid){
        $LinkageTypeModel = new LinkageType();
        $criteria = new CDbCriteria;
        $attributes = array(
          "nid"    =>   $nid,
        );
        $LinkageTypeResult = $LinkageTypeModel->findByAttributes($attributes,$criteria);
        return $LinkageTypeResult;
    } 
    
    /**
     * 获取联动类型数据
     **/
    public function getTypeListByNids($nids){
        $LinkageTypeModel = new LinkageType();
        $criteria = new CDbCriteria;
        $attributes = array(
          "nid"    =>   $nids,
        );
        $LinkageTypeResult = $LinkageTypeModel->findAllByAttributes($attributes,$criteria);
        return $BorrowResult;
    }

}
