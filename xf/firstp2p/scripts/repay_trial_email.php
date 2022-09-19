<?php
/**
 * @desc  每日待还列表统计更新
 * User: jinhaidong
 * Date: 2017-9-27 15:54:07
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

use core\service\DealProjectService;
use core\service\DealService;
use core\service\DealRepayAccountService;
use libs\utils\Logger;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealRepayModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealAgencyModel;
use libs\vfs\Vfs;

class RepayTrialEmail {

    private $csvPath;
    private $pageSize = 2000;
    private $expireTime = 0;

    private $flush = false;

    private $maxRepayId = 0;

    public function getEmailAddrs(){
        $emails = app_conf('DAIKOU_EMAIL_LIST');
        return explode(",",$emails);
    }

    public function run($flush=false){
        $startTime = to_timespan(date("Y-m-d"));
        $endDate = $this->getEndDate(date('Y-m-d'));
        $endTime = to_timespan($endDate);

        $cache = \SiteApp::init()->dataCache->getRedisInstance();
        $redisKey = "repay_trial_email_".date('Ymd');

        $csv_name = sprintf("repay_trial_%s.csv", date("Ymd"));
        $this->csvPath = sprintf("%s/%s", APP_ROOT_PATH.'runtime', $csv_name);

        if($flush){
            $this->flush = true;
            $res = $cache->del($redisKey);
            @unlink($this->csvPath);
        }
//$page = $cache->get($redisKey);
//$page = !$page ? 1 : $page;


        $this->genAttachmentTitle();


        while(true){
            $repayData = $this->getRepayData($startTime,$endTime,$this->maxRepayId);
            if(!$repayData){
                Logger::info(__CLASS__ . "," . __FUNCTION__ . ",还款数据已跑完 当前 repayId:".$this->maxRepayId);
                break;
            }
            try{
                $res = $this->genEmailData($repayData);
                if(!$res){
                    throw new \Exception("还款数据写入失败 repayId:".$this->maxRepayId);
                }
                Logger::info(__CLASS__ . "," . __FUNCTION__ . ",还款数据写入成功 repayId:".$this->maxRepayId);
                // $cache->set($redisKey,$page);
            }catch (\Exception $ex){
                Logger::error(__CLASS__ . "," . __FUNCTION__ . ",".$ex->getMessage());
                break;
            }
        }

        $sendRes = $this->sendEmail();
        if($sendRes){
            $redisKey = "repay_trial_email_finish_".date('Ymd');
            $cache->set($redisKey,time());
        }
    }

    public function genEmailData($repayData){
        $dealService = new DealService();
        $deal = new DealModel();
        $dealProjectService = new DealProjectService();

        // 消费贷ID
        $xfdId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XFD);
        $emailData = array();
        foreach ($repayData as $row) {
            $dealInfo = $deal->find($row['deal_id']);

            //过滤专享1.75标的
            if ($dealProjectService->isProjectEntrustZX($dealInfo['project_id'])) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "deal_id:" . $row['deal_id'] . " 专享1.75标的不进行统计")));
                $this->maxRepayId = $row['id'];
                continue;
            }

            $isP2pPath = $dealService->isP2pPath($dealInfo);

            $repayInfo = DealRepayModel::instance()->find($row['id']);
            $repayAccountType = DealRepayAccountService::instance($dealInfo)->setRepay($repayInfo)->getRepayAccount();

            if ($repayAccountType === false) {
                Logger::error(__CLASS__ . "," . __FUNCTION__ . ",标的唯一标识不存在 dealId:" . $row['deal_id']);
                $this->maxRepayId = $row['id'];
                continue;
            }

            $reportStatus = $isP2pPath ? '已报备' : '未报备';
            $loanTypeName = $this->getLoanTypeName($dealInfo->type_id);

            // 暂时屏蔽，以后新方法有问题再用
            // $repayAccount = $this->getRepayAccountName($repayAccountType,$isP2pPath,$dealInfo->deal_type,$dealInfo->type_id, $dealInfo->repay_start_time);

            $repayAccount = $this->getRepayAccountNameNew($repayAccountType,$isP2pPath,$dealInfo->deal_type);
            $assetManager = DealAgencyModel::instance()->findViaSlave($dealInfo['advisory_id'], 'name');

            // 还款形式
            $repayTypeName = $this->getEspecialRepayTypeName($repayInfo, $repayAccountType);

            $userInfo =  UserModel::instance()->find($dealInfo['user_id'],'real_name');

            $data = array(
                $dealInfo->id,
                iconv("utf-8", "gbk", $dealInfo['name']),
                iconv("utf-8", "gbk", $userInfo['real_name']),
                iconv("utf-8", "gbk", $dealInfo['user_id']),
                iconv("utf-8", "gbk", $loanTypeName),
                to_date($row['repay_time'],'Y-m-d'),
                $row['repay_money'],
                iconv("utf-8", "gbk", $reportStatus),
                iconv("utf-8", "gbk", $repayTypeName),
                iconv("utf-8", "gbk", $repayAccount),
                iconv("utf-8", "gbk", $assetManager['name']),
                iconv("utf-8", "gbk", $dealInfo['approve_number']),
            );
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "deal_id:" . $dealInfo->id . " 加入写队列成功")));
            $emailData[]=implode(",",$data);

            $this->maxRepayId = $row['id'];
        }

        if(empty($emailData)){
            return true;
        }
        $fres = $this->genAttachmentBody($emailData);
        if(!$fres){
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",邮件数据写入失败");
        }
        return $fres;
    }

    //运营人员又提了新需求
    public function getEspecialRepayTypeName($repayInfo, $repayAccountType){
        //1 本期还款行为为代扣的，还款方式为代扣
        if($repayInfo['repay_type'] == DealRepayModel::DEAL_REPAY_TYPE_DAIKOU){
            return DealRepayModel::$repayTypeMsg[DealRepayModel::DEAL_REPAY_TYPE_DAIKOU];
        }
        //2 其他形式，按照就对应DealRepayModel的还款方式
        return DealRepayModel::$repayTypeMsg[$repayAccountType];
    }

    public function getRepayData($startTime,$endTime,$page){
        //$limit = ($page - 1) * $this->pageSize . "," . $this->pageSize;
        $limit = $this->pageSize;
        $sql = "SELECT t1.`id`,t1.`repay_time`, t1.`repay_money`, t1.`user_id`,t1.deal_id,t1.`repay_type`
                 FROM firstp2p_deal_repay t1
                 LEFT JOIN firstp2p_deal t2
                 ON t1.`deal_id` = t2.`id`
                 AND t1.`id` > ".$this->maxRepayId."  AND t1.`repay_time` <= {$endTime}  AND t1.repay_time >={$startTime} AND t1.`status` = 0
                 WHERE t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4  ORDER by t1.`id` ASC limit ". $limit;

        $res = $GLOBALS['db']->get_slave()->getAll($sql);
        if(empty($res)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"待还款列表为空 startTime:{$startTime},endTime:{$endTime},limit:".$limit)));
            return false;
        }
        return $res;
    }

    // 代垫网信账户,代垫存管账户,借款人存管账户,借款人超级账户,代充值存管户,
    public function getRepayAccountName($repayType,$isP2pPath,$dealType,$typeId,$repay_start_time){
        $accountName = '';

        // 网贷、已报备、且还款形式为“空”的标的(代垫网贷账户)
        if($isP2pPath && $repayType == 0){
            $accountName = '代垫网贷账户';
        }

        // 代垫网信账户(网贷未报备)
        if($dealType == DealModel::DEAL_TYPE_GENERAL && $isP2pPath === false){
            $accountName = '代垫网信账户';
        }

        // 代充值网贷账户(已报备且还款形式为“代充值还款”)
        if($isP2pPath && $repayType == DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI){
            $accountName = '代充值网贷账户';
        }

        $specificTypeIds = $this->getLoanTypeIdByTag(array(DealLoanTypeModel::TYPE_CR, DealLoanTypeModel::TYPE_FD, DealLoanTypeModel::TYPE_YSD, DealLoanTypeModel::TYPE_ARTD, DealLoanTypeModel::TYPE_GRXF));
        // 代垫网信账户(网贷未报备且产品类别不为“产融贷、房贷、应收贷、融艺贷、个人消费”)
        if($dealType == DealModel::DEAL_TYPE_GENERAL && $isP2pPath === false && !isset($specificTypeIds[$typeId])){
            $accountName = '代垫网信账户';
        }

        // 代垫网贷账户(已报备且还款形式为“代垫还款、已报备且还款形式为“代扣还款”的标、已报备且还款形式为“空”的)
        if($isP2pPath && in_array($repayType,array(DealRepayModel::DEAL_REPAY_TYPE_DAIKOU,DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN))){
            $accountName = '代垫网贷账户';
        }

        // 借款人网信账户 (网贷未报备且产品类别为“产融贷、房贷、应收贷、融艺贷、个人消费” 、专享、交易所、小贷的标的)
        if(($dealType == DealModel::DEAL_TYPE_GENERAL && isset($specificTypeIds[$typeId])) || in_array($dealType,array(DealModel::DEAL_TYPE_EXCLUSIVE,DealModel::DEAL_TYPE_EXCHANGE,DealModel::DEAL_TYPE_PETTYLOAN))){
            $accountName = '借款人网信账户';
        }

        // 借款人网贷账户 (网贷已报备、且产品类别为“产融贷”的标的)
        $specificTypeIds = $this->getLoanTypeIdByTag(array(DealLoanTypeModel::TYPE_CR));
        if($isP2pPath && in_array($typeId,$specificTypeIds)){
            $accountName = '借款人网贷账户';
        }

        // 担保机构网贷账户
        if($isP2pPath && !in_array($typeId, $specificTypeIds) && $repay_start_time >= to_timespan(app_conf("BATCH_REPAY_ON_LINE_TIME"))){
            $accountName = '担保机构网贷账户';
        }
        // 消费贷

        return $accountName;
    }

    public function getRepayAccountNameNew($repayType,$isP2pPath,$dealType,$typeId){

        // 专享、交易所、小贷的标的
        if(in_array($dealType,array(DealModel::DEAL_TYPE_EXCLUSIVE,DealModel::DEAL_TYPE_EXCHANGE,DealModel::DEAL_TYPE_PETTYLOAN))){
            return '借款人网信账户';
        }

        // 网贷未报备
        if($dealType == DealModel::DEAL_TYPE_GENERAL && $isP2pPath === false){
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_SELF){
                return '借款人网信账户';
            }
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN){
                return '代垫网信账户';
            }
        }

        // 网贷报备
        if($dealType == DealModel::DEAL_TYPE_GENERAL && $isP2pPath){
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_SELF){
                return '借款人网贷账户';
            }
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN){
                return '代垫网贷账户';
            }
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_DAICHANG){
                return '担保机构网贷账户';
            }
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI){
                return '代充值网贷账户';
            }
            if($repayType == DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){
                return '担保机构网贷账户';
            }
        }
    }

    public function getLoanTypeIdByTag($tags){
        static $idKeys = null;
        if(!isset($idKeys)){
            $arr = \core\dao\DealLoanTypeModel::instance()->findAll("`is_effect`='1' AND `is_delete`='0' ",true);
            foreach($arr as $val){
                $idKeys[$val['type_tag']] = $val['id'];
            }
        }
        $ids = array();
        foreach($tags as $val){
            if(isset($idKeys[$val])){
                $ids[]=$idKeys[$val];
            }
        }
        return $ids;
    }


    public function getLoanTypeName($typeId){
        static $loanTypes = null;
        if(isset($loanTypes)){
            return $loanTypes[$typeId];
        }
        $arr = \core\dao\DealLoanTypeModel::instance()->findAll("`is_effect`='1' AND `is_delete`='0' ",true);
        foreach($arr as $val){
            $loanTypes[$val['id']] = $val['name'];
        }
        return $loanTypes[$typeId];
    }

    public function genAttachmentTitle(){
        if(!file_exists($this->csvPath)){
            $title = array("标的ID","项目名称","姓名","借款人ID","产品类别","还款时间","还款金额","报备状态","还款类型","充值账户", "资产管理方","进件编号");
            $title = iconv("utf-8", "gbk", implode(',', $title)) . "\n";
            return file_put_contents($this->csvPath,$title);
        }
        return true;
    }

    public function genAttachmentBody($data){
        $emailDataStr = implode("\n",$data)."\n";
        if($this->flush){
            $this->genAttachmentTitle();
        }
        $res =file_put_contents($this->csvPath,$emailDataStr,FILE_APPEND);
        return $res;
    }

    public function getEndDate($beginDate){
        $endDate = $beginDate;
        $i = 1;
        while(true){
            $date = date('Y-m-d',mktime(0,0,0,date('m'),date('d')+$i,date('Y')));
            if(!$this->isHoliday($date)){
                break;
            }
            $endDate = $date;
            $i++;
        }
        return $endDate;
    }

    public function isHoliday($date) {
        \FP::import("libs.common.dict");
        $holidays = \dict::get('REDEEM_HOLIDAYS');
        $holidays = array_flip($holidays);
        return isset($holidays[$date]);
    }


    public function sendEmail(){
        //$attachId = add_file($this->csvPath);
        $attachment = $this->csvPath;

        $upDir = 'uploads'; // 上传文件的上级目录
        $savepath = $upDir . '/' . date('Ymd') . '/'; // 本次文件所存的文件夹
        Vfs::createDir($savepath); // 创建文件夹
        $remote_filename = $savepath . "repay_trial.csv";

        $source_filename = $attachment;
        $res = Vfs::write($remote_filename,$source_filename);
        if(!$res){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, " vfs 写入失败")));
            return false;
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "vfs 写入成功")));
            return true;
        }


// 邮件发送时附件总是有问题，所以不在发送邮件，直接把文件放到vfs上
//        if($attachment){
//            $title = sprintf("网信理财 %s 还款试算表", date("Y年m月d日"));
//            $content = sprintf("您好，附件是%s，请查收。", $title);
//
//            $msgcenter = new msgcenter();
//
//            foreach ($this->getEmailAddrs() as $email) {
//                $msgCount = $msgcenter->setMsg($email, 0, $content, false, $title, $attachment);
//            }
//            $msgSave = $msgcenter->save();
//            if($msgCount == 0 || $msgSave == 0){
//                Logger::error(__CLASS__ . "," . __FUNCTION__ . ",setMsg 返回结果是0");
//            }
//        }else{
//            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",csv文件生成失败");
//        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$flush = isset($argv[1]) ? 1 : false;
$obj = new RepayTrialEmail();
$obj->run($flush);
