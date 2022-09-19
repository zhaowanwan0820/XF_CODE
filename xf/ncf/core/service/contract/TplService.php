<?php

namespace core\service\contract;

use core\service\BaseService;
use core\dao\contract\DealContractModel;
use core\dao\contract\ContractModel;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractCategoryEnum;
/**
 * 合同模板相关接口
 */
class TplService extends BaseService {
    private static $funcMap = array(
        /**
         * 按照分类ID获取一套模板
         * @param
         * @return array
         */
        'getTplsByCid' => array('categoryId','contractVersion'),
        /**
         * 按照分模板ID获取一个模板
         * @param
         * @return array
         */
        'getTplById' => array('id'),
        /**
         * 按照合同分类ID,dealId,prefix获取一个合同模板
         * @param
         * @return array
         */
        'getTplByName' => array('dealId', 'tplPrefix', 'type', 'sourceType'),
        /**
         * 按照合同分类ID,dealId,prefix,用户投资时间获取一个合同模板
         * @param
         * @return array
         */
        'getTplByTime' => array('dealId', 'time', 'tplPrefix', 'type'),
        /**
         * 添加模板
         * @param
         * @return array
         */
        'addTpl' => array('contractTitle', 'name', 'contractCid', 'content', 'type', 'isHtml', 'version', 'tplIdentifierId'),
        /**
         * 根据模板ID更新模板信息
         * @param
         * @return array
         */
        'updateTplById' => array('id', 'contractTitle', 'name', 'contractCid', 'content', 'type', 'isHtml', 'version', 'tplIdentifierId'),
        /**
         * 根据标的信息获取一套模板
         * @param
         * @return array
         */
        'getTplsByDealId' => array('dealId', 'time', 'type', 'sourceType'),
        /**
         * 根据模板标识判断模板是否存在
         * @param
         * @return array
         */
        'checkTplName' => array('tplName', 'version', 'tplIdentifierId'),
        /**
         * 按照合同分类ID,dealId,prefix,用户投资时间获取一个合同模板
         * @param
         * @return array
         */
        'saveContractAttachment' => array('dealId', 'jsonData', 'sourceType'),
        /**
         * 按照合同分类ID,dealId,prefix,用户投资时间获取一个合同模板
         * @param
         * @return array
         */
        'getContractAttachmentByDealId' => array('dealId', 'sourceType'),
    );

    private static $defaultFunc = array(
        'getTplByName',
        'getTplByTime',
        'getTplsByDealId',
        'saveContractAttachment',
        'getContractAttachmentByDealId',
    );

    private static $defaultParamValue = array(
        'type' =>  ContractServiceEnum::TYPE_P2P,
        'sourceType' => ContractServiceEnum::SOURCE_TYPE_PH,
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
        return self::rpc('contract', 'tpl/'.$name, $args);
    }

}
