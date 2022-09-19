<?php
/*
 * borrow
 */

class BorrowUploadClass  {
        
    /**
     * 获取企业 抵押物图片
     **/
    public function getBorrowDiya($id){
        $BorrowuploadModel = new Borrowupload();
        $criteria = new CDbCriteria; 
        $criteria->order = "`order` desc";
        $attributes = array(
          "article_id"    =>   $id,  
          "is_visible"    =>   1,
        );
        
        $BorrowuploadResult =$BorrowuploadModel->findAllByAttributes($attributes,$criteria);
        return $BorrowuploadResult;
     }
    
    /**
     * 项目标志图片
     **/
    public function getBorrowLogo($id){
        $BorrowuploadModel = new Borrowupload();
        $criteria = new CDbCriteria; 
        $criteria->order = "`order` desc";
        $attributes = array(
          "article_id"    =>   $id,  
          "type_id"    =>   14,
        );
        $BorrowuploadResult =$BorrowuploadModel->findByAttributes($attributes,$criteria);
        return $BorrowuploadResult;
     }
    
     /**
     * 项目标志图片  列表
     **/
    public function getBorrowLogos($id_array){
        $BorrowuploadModel = new Borrowupload();
        $criteria = new CDbCriteria; 
        $criteria->order = "`order` desc";
        $criteria->addInCondition("article_id", $id_array);
        $criteria->group = "article_id";
        $attributes = array(
          "type_id"    =>   14,
        );
        $BorrowuploadResult =$BorrowuploadModel->findAllByAttributes($attributes,$criteria);
        return $BorrowuploadResult;
     }

}
