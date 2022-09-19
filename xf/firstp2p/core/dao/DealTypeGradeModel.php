<?php
/**
 * DealTypeGradModel.class.php
 *
 * @date 2017-2-16
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */

namespace core\dao;


class DealTypeGradeModel extends BaseModel {

    /**
     * 获取分类list
     */
    public function getDealTypeList(){
        return $this->findAllViaSlave('is_del = 0',true);
    }

    /**
     * 保存分类
     * @param $data
     * @return bool|resource
     */
    public function save($data = []){
        return $this->db->autoReplace($this->tableName(),$data,$data);
    }

    /**
     * 通过id获取分类
     * @param $id
     * @return array
     */
    public function getbyId($id){
        return $this->findViaSlave($id,'*');
    }

    /**
     * 通过父id获取分类
     * @param $id
     * @return array
     */
    public function getbyParentId($parentId){
        return $this->findAllViaSlave("parent_id = {$parentId} AND is_del = 0 ",'*');
    }


    /**
     * 通过父id数组获取分类
     * @param $id
     * @return array
     */
    public function getbyParentIdArray($parentIdArray){
        return $this->findAllViaSlave(sprintf("parent_id in (%s) AND is_del = 0 ", implode(',', $parentIdArray)),'*');
    }

    /**
     * 通过name获取分类，不包含主键id等于$id的值
     * @param $id
     * @return array
     */
    public function getbyNameExceptId($name,$id = 0,$parent_id = 0){
        return $this->findByViaSlave("name = '{$name}' AND id != '{$id}' AND parent_id = '{$parent_id}' AND is_del = 0 ","*");
    }

    /**
     * 删除分类
     * @param $id
     */
    public function del($id){
        return $this->updateBy(array('is_del'=>1)," id = {$id} ");
    }

    /**
     * 根据姓名查找分类
     * @param $name
     * @return \libs\db\Model
     */
    public function findByName($name, $layer = null){
        $name = $this->escape($name);
        $where = "name = '{$name}' AND is_del = 0";
        if ($layer !== null) {
            $where .= ' AND layer = '.intval($layer);
        }
        return $this->findByViaSlave($where, "*");
    }

    public function getByNameArray($nameArray) {
        return $this->findAllViaSlave(sprintf("name in ('%s') AND is_del = 0 ", implode("','", $nameArray)),'*');
    }

    /**
     * 根据层级获取产品名称
     * @param $name
     * @return \libs\db\Model
     */
    public function getListByLayer($layer = 0){
        return $this->findAllViaSlave("layer = '{$layer}' AND is_del = 0",true);
    }

    /**
     * 根据名称获取id
     * @return int
     */
    public function findIdByName($name){
        $result = $this->findByViaSlave("name = '{$name}' AND is_del = 0","id");
        return empty($result)?false:$result['id'];
    }

    /**
     * 获取所有P2p的二级分类
     * @param string $name
     * @param string $sortCond 排序条件
     * @return array
     */
    public function getAllSecondLayersByName($name,$sortCond='') {
        $sql = "SELECT * FROM firstp2p_deal_type_grade WHERE parent_id = (SELECT id FROM firstp2p_deal_type_grade WHERE name = '" . $name ."') AND is_del = 0 AND status = 1 " .$sortCond;
        $grade_list = $this->findAllBySql($sql,true,array(),true);
        return isset($grade_list) ? $grade_list : array();
    }

    /**
     * 获取所有级别名称和分数,根据二级分类和三级分类
     */
    public function getAllLevelByName($level2, $level3) {
        $sql = sprintf('SELECT t1.id as id3, t1.name as level3,t1.score as score3, t2.id as id2, t2.name as level2, t2.score as score2, t3.id as id1, t3.name as level1, t3.score as score1 FROM firstp2p_deal_type_grade t1 left join firstp2p_deal_type_grade t2 ON t1.parent_id = t2.id  left join firstp2p_deal_type_grade t3 ON t2.parent_id = t3.id where t1.name = "%s" and t2.name = "%s"', $level3, $level2);
        $result = $this->findBySqlViaSlave($sql);
        return $result ? $result->getRow() : [];
    }

    /**
     * 
     * @param string $parentId
     * @return array
     */
    public function getThirdLevelByFirstLevelId($id) {
        $sql = "SELECT * FROM firstp2p_deal_type_grade WHERE parent_id in (SELECT id FROM firstp2p_deal_type_grade WHERE parent_id = '" . $id ."') AND is_del = 0 AND status = 1 ";
        return $this->findAllBySqlViaSlave($sql,true);
    }

    /**
     * 通过id获取分类
     * @param $id
     * @return array
     */
    public function getbyIds($ids){
        if (is_array($ids)) {
            $ids  = implode(',',$ids);
        }else{
            return false;
        }
        return $this->findAllViaSlave("id  in ({$ids})",'*');
    }

}

