<?php
/**
 * BankModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

/**
 * 银行卡
 *
 * @author wenyanlei@ucfgroup.com
 **/
class BankModel extends BaseModel
{

    /**
     * 获取银行列表
     * @param string $where
     */
    public function getBankList($condition='') {
        $result = $this->findAllViaSlave($condition, true);
        return $result;
    }

    /**
     * 根据推荐度，排序位置和ID来获取银行列表
     */
    public function getAllOrderByRecSortId(){
        $condition = ' 1 ORDER BY is_rec DESC,sort DESC,id ASC';
        return $this->findAll($condition);
    }

    /**
     * 根据状态获取银行列表，排序顺序为推荐度、排序位置和ID
     */
    public function getAllByStatusOrderByRecSortId($status='0',$is_arrray = false){
       $condition = 'status=:status ORDER BY is_rec DESC,sort DESC,id ASC';
       return  $this->findAll($condition, $is_arrray, "*", array(':status'=>$status));
    }

    /**
     * getBankByName
     * 获取根据银行名字银行卡信息
     *
     * @param mixed $name
     * @access public
     * @return void
     */
    public function getBankByName($name) {
        $condition = "`name`=':name'";
        return $this->findBy($condition, 'id, name, img', array(':name' => $name));
    }

    public function getBankByCode($shortName) {
        $condition = "`short_name`=':short_name'";
        return $this->findBy($condition, 'id,name,abbreviate_name,short_name,img,logo_id,bg_id,icon_id,mask2x,mask3x', array(':short_name' => $shortName));
    }

} // END class BankModel extends BaseModel