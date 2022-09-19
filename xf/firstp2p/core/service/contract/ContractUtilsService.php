<?php
/**
 * 合同工具服务
 */

namespace core\service\contract;

use libs\utils\Logger;
use libs\utils\Rpc;
use core\dao\AgencyUserModel;

class ContractUtilsService
{
    static public function writeSignLog($log_msg, $level = Logger::INFO)
    {
        Logger::wLog(sprintf('log_msg:%s,backtrace:%s', $log_msg, json_encode(debug_backtrace())), $level, Logger::FILE, LOG_PATH. "/logger/contractSign_" . date('Y_m_d') .'.log');
    }

    /**
     * 用于渲染合同内容
     * @param array $notice [key => value] 合同中的变量 键值对
     * @param string $content 合同模板内容
     * @return string 渲染后的合同内容
     */
    static public function fetchContent($notice, $content)
    {
        $content = preg_replace('/\{\$.*?\./', '{', $content);
        foreach ($notice as $name => $value) {
            $content = str_replace('{'.$name.'}', $value, $content);
        }
        return $content;
    }

    /**
     * 调用传承的合同服务
     * @param string $service 服务的命名空间 eg."\NCFGroup\Contract\Services\Contract"
     * @param string $method 服务中的方法 eg."getContractInfoByContractId"
     * @param object $request 请求参数对象 protos
     * @return object $response || throw \Exception
     */
    static public function callRemote($service, $method, $request)
    {
        $contract_rpc = new Rpc('contractRpc');
        $ret = $contract_rpc->go($service,$method, $request);
        if (empty($ret)) {
            $err_msg = sprintf('contract rpc-callRemote return empty, params:%s', json_encode(func_get_args()));
            Logger::error($err_msg);
            throw new \Exception($err_msg);
        } else {
            return $ret;
        }
    }

    /**
     * 验证某个用户对于某个合同的拥有权限
     *
     * @param array $cont_info 对应合同库中 contract_* 表的合同信息
     * @param array $user_info 用户信息 ['id', 'user_name']
     * @return bool
     */
    static public function checkContractOwnership($cont_info, $user_info)
    {
        if (empty($cont_info) || empty($cont_info)) {
            return false;
        }

        if (!($cont_info['userId'] || $cont_info['borrowUserId'] || $cont_info['agencyId'] || $cont_info['advisoryId'] || $cont_info['entrustAgencyId']|| $cont_info['canalAgencyId'])) { // 如果各方id为0，说明这份合同是需要展示给用户的
            return true;
        } else if (($cont_info['borrowUserId'] == $user_info['id']) || ($cont_info['userId'] == $user_info['id'])) { // 用户为投资人或借款人
            return true;
        } else { // 用户为签署机构
            $agency_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['agencyId'], $user_info['user_name'], $user_info['id']);
            if(!empty($agency_user)) {
                return true;
            }

            $advisory_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['advisoryId'], $user_info['user_name'], $user_info['id']);
            if(!empty($advisory_user)) {
                return true;
            }

            $entrust_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['entrustAgencyId'], $user_info['user_name'], $user_info['id']);
            if(!empty($entrust_user)) {
                return true;
            }

            $canal_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['canalAgencyId'], $user_info['user_name'], $user_info['id']);
            if(!empty($canal_user)) {
                return true;
            }

            return false;
        }
    }
}
