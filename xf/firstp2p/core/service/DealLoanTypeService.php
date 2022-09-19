<?php
/**
 * DealLoanTypeService class file.
 *
 * @author chagnlu<pengchanglu@ucfgroup.com>
 **/

namespace core\service;

use core\dao\DealLoanTypeModel;

/**
 * DealLoanTypeService
 */
class DealLoanTypeService extends BaseService {

    /**
     * 通过tag 获取 type id
     * @param $tag
     */
    public function getIdByTag($tag){
        return DealLoanTypeModel::instance()->getIdByTag($tag);
    }

    /**
     * @获取类型名称
     * @param int $typeId
     * @return string
     */
    public function getDealLoanType($typeId)
    {
        return DealLoanTypeModel::instance()->getLoanNameByTypeId($typeId);
    }
    /**
     * 通过tag 获取 tag对应的贷款类型所对应的信息
     * @param $tag
     */
    public function getInfoByTag($tag){
        return DealLoanTypeModel::instance()->getInfoByTag($tag);
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
