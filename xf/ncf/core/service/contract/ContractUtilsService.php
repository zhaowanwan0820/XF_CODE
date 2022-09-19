<?php
/**
 * 合同工具服务
 */

namespace core\service\contract;

use libs\utils\Logger;
use core\dao\deal\DealAgencyModel;

class ContractUtilsService
{
    static public function writeSignLog($log_msg, $level = Logger::INFO)
    {
        Logger::wLog(sprintf('log_msg:%s,backtrace:%s', $log_msg, json_encode(debug_backtrace())), $level, Logger::FILE, ROOT_PATH.Logger::LOG_DIR."contractSign_" . date('Y_m_d') .'.log');
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
            $agency = DealAgencyModel::instance()->find($cont_info['agencyId']);
            if(!empty($agency) && ($agency['agency_user_id'] == $user_info['id'])){
                return true;
            }

            $advisory = DealAgencyModel::instance()->find($cont_info['advisoryId']);
            if(!empty($advisory) && ($advisory['agency_user_id'] == $user_info['id'])){
                return true;
            }

            $entrustAgency = DealAgencyModel::instance()->find($cont_info['entrustAgencyId']);
            if(!empty($entrustAgency) && ($entrustAgency['agency_user_id'] == $user_info['id'])){
                return true;
            }

            $canalAgency = DealAgencyModel::instance()->find($cont_info['canalAgencyId']);
            if(!empty($canalAgency) && ($canalAgency['agency_user_id'] == $user_info['id'])){
                return true;
            }
            return false;
        }
    }
}
