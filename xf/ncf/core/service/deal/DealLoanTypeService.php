<?php
/**
 * 借款类型
 *
 *
 **/
namespace core\service\deal;

use core\service\BaseService;
use core\dao\deal\DealLoanTypeModel;

class DealLoanTypeService extends BaseService {
    /**
     * 通过tag 获取 typeId
     * @param $tag
     * @return typeId
     */
    public function getIdByTag($tag){
        if(empty($tag)){
            return false;
        }
        return DealLoanTypeModel::instance()->getIdByTag($tag);
    }

    /**
     * 根据产品类型获取借款类型标识
     * @param $type_id int
     * @return string
     */
    public function getLoanTagByTypeId($typeId) {
        if(empty($typeId) || !is_numeric($typeId) || ($typeId < 0)){
            return false;
        }
        return DealLoanTypeModel::instance()->getLoanTagByTypeId($typeId);
    }


    /**
     *查询产品类型，支持模糊查询
     * @param $name
     * @param $page_num
     * @param $page_size
     * @return mixed
     */
    public function getListByTypeName($name, $page_num, $page_size) {
        $pageSize = empty($page_size) ? 5 : intval($page_size);
        $pageNum = empty($page_num) ? 1 : intval($page_num);
        $List = DealLoanTypeModel::instance()->getListByTypeName(htmlentities($name), $pageNum, $pageSize);
        return $List;
    }
}