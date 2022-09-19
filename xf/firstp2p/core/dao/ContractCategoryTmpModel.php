<?php
namespace core\dao;

class ContractCategoryTmpModel extends BaseModel {

    public function updateCategory($category) {
        $condition = sprintf("`id` = %d", $category['id']);
        $row = $this->findBy($condition);
        $data = array(
            'type_name' => addslashes($category['typeName']),
            'type_tag' => $category['id'],
            'create_time' => $category['createTime'],
            'is_delete' => $category['isDelete'],
            'contract_type' => $category['contractType'],
            'use_status' => $category['useStatus'],
            'contract_version' => $category['contractVersion'],
            'type' => $category['type'],
            'source_type' => $category['sourceType'],
        );
        if(!$row->updateOne($data)){
            return false;
        }
        return true;


    }

    public function insertCategory($category) {
        $sql = "INSERT INTO `firstp2p_contract_category_tmp` (`id`, `type_name`, `type_tag`, `create_time`, `is_delete`, `contract_type`, `use_status`, `contract_version`, `type`, `source_type`) VALUES ('".$category['id']."', '".addslashes($category['typeName'])."', '".$category['id']."', '".$category['createTime']."', '".$category['isDelete']."', '".$category['contractType']."', '".$category['useStatus']."', '".$category['contractVersion']."', '".$category['type']."', '".$category['sourceType']."');";
        echo $sql;
        if ($this->execute($sql) === false) {
            return false;
        }
        return true;
    }

    /**
     * 查询合同信息
     * @param $name
     * @param $page_num
     * @param $page_size
     * @return mixed
     */
    public function getListByTypeName($name , $page_num, $page_size) {
        $limit = " LIMIT :prev_page , :curr_page";
        $params = array(
            ":prev_page" => ($page_num - 1) * $page_size,
            ":curr_page" => $page_size,
        );
        $condition = "`is_delete`='0' AND `use_status` = 1 AND `contract_type` = 0";
        if (!empty($name)) {
            $condition .= " AND `type_name` like " .'\'%'.htmlentities($name).'%\'';
        }
        $count = $this->findAllViaSlave($condition, true, 'count(*) as count',$params);
        $condition .= $limit;
        $list = $this->findAllViaSlave($condition, true, 'id, type_name',$params);
        $res['total_page'] = ceil(bcdiv($count[0]['count'],$page_size,2));
        $res['total_size'] = intval($count[0]['count']);
        $res['res_list'] = $list;
        return $res;
    }
}
