<?php

namespace core\service\contract;

use core\service\BaseService;
use core\dao\contract\DealContractModel;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractCategoryEnum;
/**
 * 合同分类相关接口
 */
class CategoryService extends BaseService {

    private static $funcMap = array(
        //设置标的模板分类ID
        //推荐使用默认值. eg CategoryService::setDealCId($dealId,$categoryId)
        'setDealCId' => array('dealId','categoryId','type','sourceType'),
        //更新标的模板分类ID
        //推荐使用默认值. eg CategoryService::updateDealCId($dealId,$categoryId)
        'updateDealCId' => array('dealId','categoryId','type','sourceType'),
        //获取标的模板分类ID
        //网贷推荐使用默认值. eg CategoryService::getDealCId($dealId)
        'getDealCId' => array('dealId', 'type', 'sourceType'),
        //获取某个产品(eg:智多鑫)的合同分类id
        //智多鑫第一页  CategoryService::getCategoryRecordsByDealId($dealId,1,1);
        'getCategoryRecordsByDealId' => array('dealId','type','page'),
        //获取分类
        //推荐使用默认值. eg CategoryService::getCategorys()
        'getCategorys' => array('isDelete','type','sourceType'),
        //获取分类(分页)
        //推荐使用默认值. eg CategoryService::getCategoryList($type,$sourceType,$pageNum,$typeName,$useStatus,$contractType)
        'getCategoryList' => array('type','sourceType','pageNum','typeName','useStatus','contractType','isDelete','pageSize'),
        //添加模板分类 插入成功返回id，失败则返回false
        'addCategory' => array('typeName', 'typeTag', 'contractType', 'isDelete', 'useStatus', 'contractVersion', 'type','sourceType'),
        //更新模板分类
        'updateCategoryById' => array('categoryId', 'typeName', 'typeTag', 'contractType', 'isDelete', 'useStatus', 'contractVersion', 'sourceType'),

        //根据ID批量/单个逻辑删除模板分类
        'delCategoryByIds' => array('ids'),

        //按照分类ID获取合同模板分类
        'getCategoryById' => array('categoryId'),
        //按照分类ID获取合同模板分类
        'getCategoryLikeTypeTag' => array('typeTag'),
    );

    private static $defaultFunc = array(
        'setDealCId',
        'updateDealCId',
        'getDealCId',
        'getCategorys',
        'getCategoryList',
    );

    private static $defaultParamValue = array(
        'type' =>  ContractServiceEnum::TYPE_P2P,
        'sourceType' => ContractServiceEnum::SOURCE_TYPE_PH,
        'isDelete' => ContractCategoryEnum::CATEGORY_IS_DLETE_NO,
        'pageSize' => 30,
        'typeName' => null,
        'useStatus' => null,
        'contractType' => null,
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (isset($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        // 对于方法中，后面几个未传，则会指定默认值
        if (in_array($name,self::$defaultFunc)) {
            foreach(self::$defaultParamValue as $key => $default){
                if(in_array($key,$argNames)){
                    $args[$key] = !isset($args[$key]) ? $default : $args[$key];
                }
            }
        }
        return self::rpc('contract', 'category/'.$name, $args);
    }
}
