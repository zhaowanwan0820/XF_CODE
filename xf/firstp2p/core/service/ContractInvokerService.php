<?php
/**
 * 合同服务调用中间层，为 p2p 中的服务调用提供一致的调用接口
 * 实现方式：用 PHP 魔术方法实现 Facade 设计模式，对 p2p 服务屏蔽合同服务实现的组件
 * 原则：service\contract 下为合同具体模块功能实现，外部不应该直接调用，请用示例的方式调用
 *
 * 例如想要调用 ContractViewerService::getOneFetchedContract($contract_id, $deal_id)
 * 请用以下方式调用：
 * ContractInvokerService::getOneFetchedContract('viewer', $contract_id, $deal_id);
 * 第一个参数指定了具体要调用哪个服务类
 */

namespace core\service;

use libs\utils\Logger;

class ContractInvokerService
{
    const NAMESPACE_PREFIX = '\core\service\contract\\';
    static public $invoker_map = array(
        'viewer' => 'ContractViewerService',
        'filer' => 'ContractFilerService',
        'remoter' => 'ContractRemoterService',
        'signer' => 'ContractSignerService',
        'author' => 'ContractAuthorizeService',
        'dt' => 'ContractDtService',
    );

    public function __call($func, $args)
    {
        return self::__callStatic($func, $args);
    }

    /**
     * @param string $func
     * @param array $args
     * @return mix
     * @throw \Exception
     */
    public static function __callStatic($func, $args)
    {

        // 服务降级开关
        if (app_conf('CONTRACT_SERVICE_SWITCH') == 0) {
            Logger::info("Contract service is down");
            return false;
        }

        $class_name = self::NAMESPACE_PREFIX . self::$invoker_map[array_shift($args)];
        if (class_exists($class_name) && method_exists($class_name, $func)) {
            Logger::info(sprintf('success：调用合同方法！class_name：%s，function：%s，args：%s，file：%s，line：%s', $class_name, $func, json_encode($args), __FILE__, __LINE__));
            return call_user_func_array(array(new $class_name(), $func), $args);
        } else {
            $error_msg = sprintf('fail：调用方法不存在！class_name：%s，function：%s，args：%s，file：%s，line：%s', $class_name, $func, json_encode($args), __FILE__, __LINE__);
            Logger::error($error_msg);
            throw new \Exception($error_msg);
        }
    }
}
