<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\DealTypeGradeService;
use core\dao\DealTypeGradeModel;

/**
 * 银行卡相关接口
 */
class DealTypeGradeApi extends ApiBackend {
    /**
     * 获取所有级别名称和分数,根据二级分类和三级分类.
     */
    public function getAllLevelByName() {
        $level2= $this->getParam('level2');
        $level3= $this->getParam('level3');
        $deal_type_grade_service = new DealTypeGradeService();
        $res = $deal_type_grade_service->getAllLevelByName($level2,$level3);
        return $this->formatResult($res);
    }
    /**
     * 获取产品名称
     */
    public function getGradeList() {
        $layer= $this->getParam('layer');
        $deal_type_grade_service = new DealTypeGradeService();
        $res = $deal_type_grade_service->getGradeList($layer);
        return $this->formatResult($res);
    }
    /**
     * 获取第三级产品名称.
     */
    public function getThirdLayerGradeList()
    {
        $deal_type_grade_service = new DealTypeGradeService();
        $res = $deal_type_grade_service->getThirdLayerGradeList();
        return $this->formatResult($res);
    }

    /**
     * 获取所有P2p的二级分类.
     *
     * @param string $name
     * @param string $sortCond 排序条件
     *
     * @return array
     */
    public function getAllSecondLayersByName()
    {
        $name= $this->getParam('name');
        $sortCond= $this->getParam('sortCond');
        $deal_type_grade_service = new DealTypeGradeService();
        $res = $deal_type_grade_service->getAllSecondLayersByName($name,$sortCond);
        return $this->formatResult($res);
    }

    /**
     * 获取name数组下所有的三级名称
     */
    public function getSubThirdGradeByNameArray()
    {
        $nameArray= $this->getParam('nameArray');
        $deal_type_grade_service = new DealTypeGradeService();
        $res = $deal_type_grade_service->getSubThirdGradeByNameArray($nameArray);
        return $this->formatResult($res);
    }

    /**
     * 通过id获取分类.
     *
     * @param $id
     *
     * @return array
     */
    public function getbyId()
    {
        $id= $this->getParam('id');
        if (empty($id)) {
            return $this->formatResult(array());
        }
        $deal_type_grade_model = new DealTypeGradeModel();
        $res = $deal_type_grade_model->getbyId($id);
        return $this->formatResult($res->_row);
    }

    /**
     * 通过ids获取分类.
     *
     * @param $ids
     *
     * @return array
     */
    public function getbyIds()
    {
        $ids= $this->getParam('ids');
        if (empty($ids)) {
            return $this->formatResult(array());
        }
        $deal_type_grade_model = new DealTypeGradeModel();
        $res = $deal_type_grade_model->getbyIds($ids);
        return $this->formatResult($res);
    }

    /**
     * 获取所有P2p的二级分类.
     *
     * @param string $name
     * @param string $sortCond 排序条件
     *
     * @return array
     */
    public function getAllThirdLayersByName(){
        $name= $this->getParam('name');
        $deal_type_grade_service = new DealTypeGradeService();
        $res = $deal_type_grade_service->getAllThirdLayersByName($name);
        return $this->formatResult($res);
    }

}
