<?php
/*
 * 用户账户类
 */

class AttestationClass  {
        
    /**
     * 获取企业相关图片
     **/
    public function getCorpPic($user_id){
        $AttestationModel = new Attestation();
        $criteria = new CDbCriteria;
        $criteria->order = "`order` desc";
        $attributes = array(
          "user_id"    =>   $user_id,  
          "is_visible" =>   1, 
        );
        $AttestationResult =$AttestationModel->findAllByAttributes($attributes,$criteria);
        return $AttestationResult;
        
    }
    
    
    

}
