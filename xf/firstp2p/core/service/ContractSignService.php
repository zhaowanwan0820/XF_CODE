<?php
/**
 * ContractSignService.pp
 *
 * @date 2014-04-01
 * @author wangfei5<wangfei5@ucfgroup.com>
 */
namespace core\service;

use core\service\ContractService;
use core\service\contract\ContractDtService;
use core\service\darkmoon\ContractService AS DarkmoonContractService;
use core\service\ContractNewService;
use core\service\DealService;
use core\service\AgencyImageService;
use core\service\ContractInvokerService;
use core\service\GoldService;

use libs\utils\Logger;
use core\dao\FastDfsModel;
use core\dao\ContractFilesModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\darkmoon\DarkmoonDealModel;
use core\dao\DealModel;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;

/**
 * Class ContractSignService
 * @package core\servic
 */

class ContractSignService extends BaseService {


    const NOTHING = 'nothing';

    /*
    * 查看合同。
    * @param $id 合同id，
    * @param $old 是否查看的是第一版本的老合同,否则就是最新版本的合同
    */
    public function readSignedPdf($id,$old=true,$adminVersion=-1,$dealId=null,$type = 0,$projectId = 0 ){
        if( empty($id) ){
            $this->writeLog(sprintf("PDF Generate Failed [contractId is null]"));
        }

        $contractService = new ContractNewService();

        if($type == 1){
            $contractInfo = $contractService->showContract($id,0,$projectId,$type);
        }elseif($type == 2){
            $contractInfo = $contractService->showContract($id,$dealId,0,100);
        }else{
            if($dealId){
                $contractInfo = $contractService->getContract($id,$dealId);
            }else{
                $contractService = new ContractService();
                $contractInfo = $contractService->getContract($id,true);
            }
        }

        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contractInfo['number']);

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
            $dfs = new FastDfsModel();
            $fileContent = $dfs->readTobuff($fileInfo['group_id'],$fileInfo['path']);
            if(!empty($fileContent)){
                header ( "Content-type: application/octet-stream" );
                header ( 'Content-Disposition: attachment; filename="'.$contractInfo['number'].'.pdf"');
                echo $fileContent;
            }else{
                $this->writeLog("signed contract file is lost [ contractId:".$id."]");
                // 是否第一时间加入打戳服务，等需求
                //echo "<div style='text-align:center;line-height:40px'><h2>合同正在加盖电子时间戳...请稍后下载！如有问题请致电400-890-9888</h2></div>";
                return false;
            }
            exit;
        }else{
            // 如果记录表中没有信息则
            $this->writeLog("contract file is signing [contractId:]".$id."]");
            return false;
        }
    }

    /**
    *  查看某个编号下所有记录
    */
    public function getSignedContractListByNum($number){
        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($number);
        if(!empty($ret)){
            return $ret;
        }
        return array();
    }


    /**
    * 签署一个没有时间戳的合同。只记录一个关系，其他都没有。
    */
    public function reSignWithoutTsa($id){
        if( empty($id) ){
            $this->writeLog(sprintf("PDF Generate Failed [contractId is null]"));
        }
        $contractService = new ContractService();
        $contractInfo = $contractService->showContract($id);
        $contractNum = $contractInfo['number'];
        if(empty($contractInfo['content'])){
            return -1;
        }
        // 重新签署的没有时间戳。只记录关系，
        $fileRet = ContractFilesWithNumModel::instance()->addNewRecord($id,$contractNum,self::NOTHING,self::NOTHING);
        if(empty($fileRet)){
             $this->writeLog(sprintf("insertRecord failed [contractId:%s]",$id));
             return -5;
        }
        $this->writeLog(sprintf("success [path:%s|%s] [createPdfCost:%s,signCost:%s] [contractInfo:%s|%s]",self::NOTHING,self::NOTHING,0,0,$contractInfo['id'],$contractNum));
        return true;
    }

    /*
    * 签署一个合同
    * @param $id 合同id
    */
    public function signOneContract($id,$async=true,$dealId = null,$type= 0,$projectId = 0){

        if($async == true){
            // 如果是异步队列执行，为了降低cpu负载，增加操作时间
            usleep(100);
        }
        if( empty($id) ){
            $this->writeLog(sprintf("PDF Generate Failed [contractId is null]"));
        }

        $contractService = new ContractService();
        if(empty($dealId)){
            $contractInfo = $contractService->showContract($id);
            $this->writeLog(sprintf("盖戳合同ID:%s",$id));
        }else{
            if($type == 1){
                $service_id = $projectId;
                $service_type = ContractServiceEnum::SERVICE_TYPE_PROJECT;
            }else if($type == 2){
                $service_id = $dealId;
                $service_type = ContractServiceEnum::SERVICE_TYPE_GOLD_DEAL;
            }else if($type == 4){
                $service_id = $dealId;
                $service_type = ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL;
            }else if($type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER){
                $service_id = $dealId;
                $service_type = ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER;
            }else{
                $service_id = $dealId;
                $service_type = ContractServiceEnum::SERVICE_TYPE_DEAL;
            }

            if ($type == 2) {
                $cs = new ContractNewService();
                $contractInfo = $cs->showContract($id,$dealId,0,100);
            } elseif ($type == 4){
                // 获取渲染后的合同内容
                $cs = new DarkmoonContractService();
                $contractInfo = $cs->getContract($id,$dealId);
            } elseif ($type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER){
                $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt', $id, $service_id, $service_type);
            } else{
                $contractInfo = ContractInvokerService::getOneFetchedContract('viewer', $id, $service_id, $service_type);
            }

            $this->writeLog(sprintf("盖戳合同ID:%s,合同num:%s",$id,$contractInfo['number']));
        }

        $contractNum = $contractInfo['number'];
        if(empty($contractInfo['content'])){
            throw new \Exception("合同内容获取失败");
        }
        // 除了随心约合同，其他的source_type为0
        $sourceType = ($type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER) ? $type : 0;
        // 如果队列执行过程中又进入了一条任务，这个时候最后一步更新状态一定是失败的。为了减少没必要的cpu消耗，任务执行过程中也进行状态的判定
        $exist = ContractFilesWithNumModel::instance()->getAllByContractNum($contractNum,$id,$sourceType);
        if(!empty($exist) && $exist[0]['status'] == ContractFilesWithNumModel::TSA_STATUS_DONE){
            $this->afterHook($contractService,$dealId,$contractNum,$type,$projectId);
            $this->writeLog(sprintf("签署文件已经存在，任务ID:%s,合同编号:%s",$id,$contractNum));
            return true;
        }
        $start = microtime(true);
        // 生成PDF文件
        $tmpPdfPath = $this->createTmpPdf($contractInfo);
        if( empty($tmpPdfPath) ){
            throw new \Exception("生成pdf失败");
        }
        $createPdfCost = round(microtime(true) - $start, 4);
        // 给PDF签名,签完就直接删除临时文件
        $start = microtime(true);
        $dealService = new DealService();

        if($type == 2){
            $goldService = new GoldService();
            $dealInfo = $goldService->getDealForTsaById($dealId);
        }elseif($type == 4){
            //获取暗月标的信息
            $dealInfo = DarkmoonDealModel::instance()->find($dealId);
            $dealInfo['deal_type'] = DarkmoonDealModel::DEAL_TYPE_OFFLINE_EXCHANGE;
        }elseif ($type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER){
            // 因为智多新和随心约合同要打东方联合的时间戳，所以对象的deal_type要为0,并且暂时不支持电子签章
            $dealInfo = new ContractDtService();
        }else{
            $dealInfo = $dealService->getDeal($dealId);
        }

        $signedFileContent = $this->doSign($tmpPdfPath,$dealInfo,$contractInfo['tpl_indentifier_info']['contractType']);

        @unlink($tmpPdfPath);

        if( empty($signedFileContent) ){
            $this->writeLog(sprintf("tsa_connect 电子签章服务异常，合同ID:%s 标的id:%s 共耗时%s",$id,$dealId,round(microtime(true) - $start, 4)));
            \libs\utils\Alarm::push('tsa_connect', 'tsa_connect 电子签章服务异常', sprintf("签署失败，合同ID:%s 标的id:%s 共耗时%s",$id,$dealId,round(microtime(true) - $start, 4)));
            throw new \Exception("电子签章服务异常");
        }
        $signCost = round(microtime(true) - $start, 4);
        // 向文件系统写入文件
        $path = $this->writeToFileSystem($signedFileContent,$contractNum);
        if(empty($path)){
            $this->writeLog(sprintf("updateRecordStatus failed [contractId:%s sign_content: %s content:%s]",$id,$signedFileContent,$contractInfo['content']));
            throw new \Exception("向文件系统写入文件失败");
        }

        // 向文件版本记录表中插入该合同对应版本的groupid和文件内容
        //$fileRet = ContractFilesModel::instance()->addNewRecord($id,$path['group_name'],$path['file_name']);
        //$fileRet = ContractFilesWithNumModel::instance()->addNewRecord($id,$contractNum,$path['group_name'],$path['file_name']);
        $fileRet = ContractFilesWithNumModel::instance()->updatePathByContractId($id,$contractNum,$path['group_name'],$path['file_name'],null,null,$sourceType);
        if(empty($fileRet)){
             $this->writeLog(sprintf("updateRecordStatus failed [contractId:%s]",$id));
             throw new \Exception("写入盖戳db异常");
        }

        $ret = $this->afterHook($contractService,$dealId,$contractNum,$type,$projectId);
        if(empty($ret)){
            $this->writeLog(sprintf("callback failed [contractId:%s]",$id));
            throw new \Exception("回调失败");
        }

        $this->writeLog(sprintf("success [path:%s|%s] [createPdfCost:%s,signCost:%s] [contractInfo:%s|%s]",$path['group_name'],$path['file_name'],$createPdfCost,$signCost,$contractInfo['id'],$contractNum));
        return true;
    }

    // 回调建通更改合同状态
    public function afterHook($contractService,$dealId,$number,$type=0,$projectId=0){
        $contractService = new ContractService();
        return $contractService->signTsaCallback($dealId,$number,$type,$projectId);
    }

    /*
    * 在临时目录创建PDF文件
    * @param array $contractInfo 合同相关信息,包括合同版本，合同编号等信息
    */
    public function createTmpPdf($contractInfo){
        //本地生成pdf
        //临时文件名为合同编号+生成时间
        $fileName = md5 ( sprintf("%s_%s",$contractInfo['number'],time())).".pdf";
        $filePath = APP_ROOT_PATH.'runtime/';

        if(!is_dir( $filePath )) {
            if(!mkdir( $filePath )) {
                return false;
            }
        }

        $pdfTmpFilePath = $this->createTmpFilePath($filePath,$fileName);
        // 如果签名过后的合同文件已经存在~,就不用重新生成PDF，节省cpu
        if( file_exists($pdfTmpFilePath) ){
            $this->writeLog(sprintf("PDF already exist~! ,don't need to recreate pdf file[path:%s]",$pdfTmpFilePath));
            return  $pdfTmpFilePath;
        }
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $mkpdf = new \Mkpdf ();
        $mkpdf->mk ( $pdfTmpFilePath, $contractInfo['content'] );
        if( !file_exists($pdfTmpFilePath) ){
            $this->writeLog(sprintf("PDF Generate Failed [contractNum:%s,contractId:%s]",$contractInfo['number'],$contractInfo['id']));
            return false;
        }else{
            return $pdfTmpFilePath;
        }
    }


    private function createTmpFilePath($path,$name){
        return $path.$name;
    }

    private function createTmpSignedFilePath($path,$name){
        return $path.'signed_'.$name;
    }
    /*
    * 将签名过后的pdf转存到vfs上
    */
    public function writeToFileSystem($fileContent,$contractNum){
        // 把本地签名完成的合同文件上传至vfs
        $dfs = new FastDfsModel();
        $dfsRet = $dfs->writeFileContent($fileContent);
        if( $dfsRet ){
            return $dfsRet;
        }
        $this->writeLog(sprintf("writeToFileSystem Failed [contract id:%s] [%s]",$contractNum, $dfs->getError()));
        return false;
    }

    /**
     * 签名
     * @param string $inPath 文件路径
     * @param object $deal_obj
     * @return mix $ret false | result-set
     */
    public function doSign($inPath,$deal_obj,$contract_type){
        if (empty($deal_obj) && !is_object($deal_obj)) {
            return false;
        }

        //参数根据具体时间戳部署证书及图片名称另行确定
        if($deal_obj->deal_type == DealModel::DEAL_TYPE_EXCLUSIVE){
            $sign_img_name = AgencyImageService::getSignImgNameByAgencyId($deal_obj->entrust_agency_id);
            if (empty($sign_img_name)) {
                return false;
            }
            //晶讯时代打戳
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' => $sign_img_name,
                'certName' => 'jxsd.pfx',
                'certPassword' => 'ucfgroup',
            );
            $picType = 1;
        }else if($deal_obj->deal_type == DealModel::DEAL_TYPE_PETTYLOAN){
            //小贷打戳
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' => 'xiaodai.png',
                'certName' => 'jxsd.pfx',
                'certPassword' => 'ucfgroup',
            );
            $picType = 1;

        }else if($deal_obj->deal_type == 100){
            //黄金标的
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' => 'gold.png',
                'certName' => 'jxsd.pfx',
                'certPassword' => 'ucfgroup',
            );
        }else if($deal_obj->deal_type == DarkmoonDealModel::DEAL_TYPE_OFFLINE_EXCHANGE){
            // 使用众汇的时间戳公章
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' => 'wxzh.png', // TODO 改
                'certName' => 'wxzh.pfx',  // TODO 改
                'certPassword' => 'wx123456',
             );
            $picType = 1;
        }else if($deal_obj->deal_type == DealModel::DEAL_TYPE_EXCHANGE){
            //交易所打戳

            //募集说明书加盖机构章
            if(ContractTplIdentifierEnum::CONTRACT_TYPE_EXCHANGE_MUJISHUOMINGSHU == $contract_type){
                $img = AgencyImageService::getSignImgNameByAgencyId($deal_obj->jys_id);
            }

            $img = $img ? $img : 'seal3.png';
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' => $img,
                'certName' => 'jxsd.pfx',
                'certPassword' => 'ucfgroup',
            );
            $picType = 1;
        }elseif($deal_obj->deal_type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER ){
            // 随心约 尊享 经讯时代戳
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' =>  'seal3.png',
                'certName' => 'jxsd.pfx',
                'certPassword' => 'ucfgroup',
            );
            $picType = 1;
        }else{
            //东方联合打戳
            $signInfo = array(
                'reason' => 'UniTrust TimeStamp Authority',
                'sealName' => 'seal2.png',
                'certName' => 'ucfgroup.pfx',
                'certPassword' => 'ucfgroup',
            );
            $picType = 2;
        }
        // 签章信息，包括签名原因，签名图章，证书名称，证书密码 一组属性

        $param = $this->createTimeParams($inPath,$signInfo,$picType);

        $urls = $GLOBALS['sys_config']['CONTRACT_SIGN_SERVER'];
        $key = rand(0,count($urls)-1);
        $url = $urls[$key];
        //用过了，这个小任务就不用了-1
        unset($urls[$key]);
        shuffle($urls);
        $newPdfData = $this->post($param,$url);
        if(!empty($newPdfData)){
            return $newPdfData;
        }else{
            $this->writeLog(sprintf("PDF Signed Failed [filePath: %s],start retry!",$inPath));
            // 如果失败就换个ip重试一下
            if(count($urls) >= 1){
                //如果配置里面不止一个ip就可以试试，如果就1个ip。直接不用试了。
                $key = rand(0,count($urls)-1);
                $url = $urls[$key];
                $newPdfData = $this->post($param,$url);
                if(!empty($newPdfData)){
                    return $newPdfData;
                }
            }
            // 签名pdf失败，那么就把原来的文件删除
            $this->writeLog(sprintf("PDF Signed Failed [filePath: %s],retry faild",$inPath));
            return false;
        }
    }
    /*
    * 创建电子签章服务参数
    */
    private function createTimeParams($pdfPath,$signInfo,$picType){
        //待签署文件
        $pdfData = base64_encode(file_get_contents($pdfPath));
        //签署在第几页
        $pdfPage = 1;
        // 1 为显示图章签名  默认
        $signStyleType = 1;
        // 1 为ES-T签名格式   2 为BES签名格式
        $signFormatType = 1;
        // 签名原因
        $reason = $signInfo['reason'];
        // 定义图标位置类型   默认
        $positionType = 1;
        // 加载电子图章信息
        $sealName = $signInfo['sealName'];
        // 加载数字证书信息
        $certName = $signInfo['certName'];
        // 加载数字证书密码
        $certPassword = $signInfo['certPassword'];
        // 壓縮模式:  目前只支持gzip格式压缩
        $compressType = "gzip";
        //图章坐标定位 定位图片两个坐标点左下和右上      依次是 左下X坐标 、左下Y坐标、右上X坐标、右上Y坐标
//        if($picType == 1){
//            $coordinate = array( 450, 490, 550, 590 );
//        }else{
//            $coordinate = array( 450, 490, 550, 700 );
//        }

        $coordinate = array( 450, 490, 550, 700 );

        $params = array(
                "signStyleType"=>$signStyleType,
                "signFormatType"=>$signFormatType,
                "page"=>$pdfPage,
                "positionType"=>$positionType,
                "sealName"=>$sealName,
                "certName"=>$certName,
                "certPassword"=>$certPassword,
                "coordinate"=>$coordinate,
                "reason"=>$reason,
                "compressType"=>$compressType,
                "pdfData"=>$pdfData,
        );
        $json = json_encode($params);
        return $json;
    }

    /*
    * 电子签章专用post(无http_build_query)
    */
    private function post($params,$url){
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
        //毫秒超时一定要设置这个
        curl_setopt ( $ch, CURLOPT_NOSIGNAL,1 );
        // tsa那边建议设置为30秒超时
        curl_setopt ( $ch, CURLOPT_TIMEOUT_MS,30000 );
        if (substr($url, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $ret = curl_exec ( $ch );
        if(curl_errno($ch) != 0 ){
            $this->writeLog(sprintf("PDF Signature Service gone away [Curl error:%s]",curl_error($ch)));
            return false;
        }
        curl_close ( $ch );
        return $ret;
    }

    /*
     * 签署合同绝大场景是异步的，所以需要进行日志打印
     */
    private function writeLog( $str ){
        $str = sprintf("contract sign: %s",$str);
        Logger::wLog($str . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH. "/logger/" ."contractSign_" . date('Y_m_d') .'.log');
    }
}
