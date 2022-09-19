<?php

namespace core\service\contract;

use NCFGroup\Common\Library\ApiService;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractCategoryEnum;
/**
 * 合同模板相关接口
 */
class ContractTplIdentifierService extends ApiService {
    private static $funcMap = array(
        /**
         * 新增 或者 更新 模板标识 ,有主键时更新，没有时新增
         * @param `name` '标识名，为模板标识前缀',
         * @param `title`  '标识的标题，一般为中文名，方便人辨识',
         * @param `sign_role`  '标识哪方签署，用二进制位标识，对应位为1，则此方需要签署',
         * @param `contract_send_node`  '生成合同节点，0：投资时生成；1：满标时生成',
         * @param `is_seen_when_bid`  '是否投资时（用户）可见，0：否；1：是',
         * @param `service_type` '服务类型：1：标的；2：项目',
         * @param `contract_type`  '合同类型，如：借款合同、委托协议','
         * @return array
         */
        'save' => array('name','title','signRole','contractSendNode','isSeenWhenBid','serviceType','contractType', 'id'),
        /**
         * 获取模板标识列表
         * @param
         * @return array
         */
        'getTplIdentifierList' => array(),
        /**
         * 根据 id 获取单个模板标识信息
         * @param
         * @return array
         */
        'getTplIdentifierInfoById' => array('id'),
    );

    private static $defaultFunc = array(
        'save',
    );

    private static $defaultParamValue = array(
        'id' =>  '',
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
        return self::rpc('contract', 'contractTplIdentifier/'.$name, $args);
    }

}
