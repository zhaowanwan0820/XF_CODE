<?php
/*
 * 担保机构类
 *
 *
 */


class GuarantorClass {

    public function get($gid) {
        $guarantorModel = new Guarantor();
        $criteria = new CDbCriteria;
        $attributes = array(
            "gid"    =>   $gid,   
        );
        $guarantorResult = $guarantorModel->findByAttributes($attributes,$criteria);
        return $guarantorResult;
    }

    public function getByLinkageId($linkage_id) {
        $guarantorModel = new Guarantor();
        $criteria = new CDbCriteria;
        $attributes = array(
            "linkage_id"    =>   $linkage_id,
        );
        $guarantorResult = $guarantorModel->findByAttributes($attributes,$criteria);
        return $guarantorResult;
    }

    public function getByUserId($user_id) {
        return self::getByField('user_id', $user_id);
    }

    public function getByField($fieldkey, $value) {
        $guarantorModel = new Guarantor();
        $criteria = new CDbCriteria;
        $attributes = array(
            $fieldkey    =>   $value,
        );
        $guarantorResult = $guarantorModel->findByAttributes($attributes,$criteria);
        return $guarantorResult;
    }

}
