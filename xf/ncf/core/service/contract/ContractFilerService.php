<?php
/**
 * 提供合同文件服务 eg.download
 */

namespace core\service\contract;

use core\service\contract\ContractViewerService;
use core\service\contract\ContractUtilsService;
use core\dao\contract\ContractFilesWithNumModel;
use libs\fastdfs\FastDfsService;
use libs\tcpdf\Mkpdf;

class ContractFilerService{
    /**
     * 合同下载
     * @param int $contract_id 合同id
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param int $service_type 服务类型 1:标的；2:项目
     * @param array $user_info 用户信息，如果不为空，则对合同的所属人进行校验 ['id', 'user_name']
     * @return void
     */
    public function download($contract_id, $service_id, $service_type = 1, $user_info = array()){
        $cont = ContractViewerService::getOneFetchedContract($contract_id, $service_id, $service_type);
        if(empty($cont)){
            return false;
        }

        // 防刷：校验此份合同是否属于当前用户
        if (!empty($user_info) && !ContractUtilsService::checkContractOwnership($cont, $user_info)) {
            return false;
        }

        $file_name = $cont['number'] . ".pdf";
        $file_path = ROOT_PATH.'runtime/'.$file_name;
        if(!file_exists($file_path)){
            set_time_limit(300);
            $mkpdf = new Mkpdf ();
            $mkpdf->mk($file_path, $cont['content']);
        }
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        readfile($file_path);
        @unlink($file_path);
        exit;
    }

    /**
     * 下载 盖戳之后的合同
     * @param int $contract_id
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param int $service_type 服务类型 1:标的；2:项目
     * @param array $user_info 用户信息，如果不为空，则对合同的所属人进行校验 ['id', 'user_name']
     * @param boolean $old 是否查看的是第一版本的老合同,否则就是最新版本的合同
     * @param float $adminVersion 只是后台传入的参数。前台（一定不能传），第一次打开的是列表。传入具体的版本号后下载对应版本的合同
     * @return void | false
     */
    public function downloadTsa($contract_id, $service_id, $service_type = 1, $user_info = array(), $old=true, $adminVersion = -1){
        $contract_info = ContractViewerService::getOneFetchedContract($contract_id, $service_id, $service_type);
        if(empty($contract_info)){
            return false;
        }

        // 防刷：校验此份合同是否属于当前用户
        if (!empty($user_info) && !ContractUtilsService::checkContractOwnership($contract_info, $user_info)) {
            return false;
        }

        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contract_info['number']);

        if(!empty($ret) && !empty($ret[0])){
            $fileInfo = array();
            // 至少存在一份有戳的合同
            // adminVersion 只是后台传入的参数。前台（一定不能传），第一次打开的是列表。传入具体的版本号后下载对应版本的合同
            if($adminVersion === -1){
                // 前台进入，old 是标示查看的是最老的／最新的合同
                if($old === true){
                    $fileInfo = $ret[0];
                }else{
                    $fileInfo = end($ret);
                }
            }else{
                // 后台可以查看，如果存在传入的版本号的合同那么就下载，否则就祭出列表
                if(isset($ret[$adminVersion])){
                    $fileInfo = $ret[$adminVersion];
                }else{
                    return $ret;
                }
            }
            $dfs = new FastDfsService();
            $fileContent = $dfs->readTobuff($fileInfo['group_id'],$fileInfo['path']);
            if(!empty($fileContent)){
                header ( "Content-type: application/octet-stream" );
                header ( 'Content-Disposition: attachment; filename="'.$contract_info['number'].'.pdf"');
                echo $fileContent;
                exit;
            }else{
                ContractUtilsService::writeSignLog(sprintf('signed contract file is lost [contractId:%d]', $contract_id));
                return false;
            }
        }else{
            // 如果记录表中没有信息则
            ContractUtilsService::writeSignLog(sprintf('contract file is signing [contractId:%d]', $contract_id));
            return false;
        }
    }
}
