<?php
/*
 * debt
 */

class DebtClass {

    public function getDebt($id) {

    }

    public function getTender($id) {

    }

    public function getTenderByBorrowTenderId($borrowTenderId) {

    }
    
    public function getDebtListByAttr($attributes, $order="",$offset=0,$limit=10,$more_criteria=NULL){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DebtModel = new Debt();
        $criteria = new CDbCriteria;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        if($more_criteria) $criteria->mergeWith($more_criteria);
        $DebtResult = $DebtModel->findAllByAttributes($attributes,$criteria);
        return $DebtResult;
    }

    public function getDebtCountByAttr($attributes, $order="",$offset=0,$limit=10,$more_criteria=NULL){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DebtModel = new Debt();
        $criteria = new CDbCriteria;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        if($more_criteria) $criteria->mergeWith($more_criteria);
        $count = $DebtModel->countByAttributes($attributes,$criteria);
        return $count;
    }

    public function getDebtDetail($id){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DebtModel = new Debt();
        $criteria = new CDbCriteria;
        $attributes = array(
            'id'=>$id,
        );
        $DebtResult =$DebtModel->findByAttributes($attributes,$criteria);
        return $DebtResult;
    }
    
    public function getDebtTender($debtId,$offset=0,$limit=10,$order="t.id desc",$more_attributes=array(),$more_criteria=null){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DebtTender = new DebtTender();
        $criteria = new CDbCriteria;
        if($order)  $criteria->order = $order;
        $criteria->offset = $offset;
        if($limit!="ALL")  $criteria->limit = $limit;
        $attributes = array(
            'debt_id'=>$debtId,
        );
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $DebtTenderResult = $DebtTender->with("userInfo","borrowInfo")->findAllByAttributes($attributes,$criteria);
        return $DebtTenderResult;
    }
    //app 5.6.0
    public function getWiseDebtDetail($id){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DebtModel = new ItzWiseDebt();
        $criteria = new CDbCriteria;
        $attributes = array(
            'id'=>$id,
        );
        $DebtResult =$DebtModel->findByAttributes($attributes,$criteria);
        return $DebtResult;
    }
}
