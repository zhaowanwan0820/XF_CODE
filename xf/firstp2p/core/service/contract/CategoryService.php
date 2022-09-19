<?php

namespace core\service\contract;

use NCFGroup\Common\Library\ApiService;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractCategoryEnum;
/**
 * 合同分类相关接口
 */
class CategoryService extends ApiService {

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
        //获取分类
        //推荐使用默认值. eg CategoryService::getCategorys()
        'getCategorys' => array('isDelete','type','sourceType'),
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
        'isDelete' => 0,
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
