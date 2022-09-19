<?php
/**
 * 即付宝 项目 回款服务
 *
 * @author jinhaidong
 * @date 2015-8-6 11:18:09
 *
 */

namespace core\service\jifu;

use libs\utils\Logger;
use libs\vfs\fds\FdsFTP;
use core\dao\JobsModel;
use core\service\DealService;
use core\dao\DealLoanRepayModel;
use core\dao\DealLoadModel;
use core\dao\ThirdpartyOrderModel;
use libs\utils\Aes;

class JfLoanRepayService {

    const FTP_LOAN_REPAY_DIR = 'loanrepay';

    private static $instance;
    public static function instance() {
        if(!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 同步提前还款的回款记录
     * @param integer $deal_id 已回款的标ID
     * @param integer $prepayId 提前还款的记录ID
     */
    public function syncPrepayToJf($deal_id,$prepayId) {
        if(!$this->isToSync($deal_id)) {
            return true;
        }
        //获取提前还款的回款记录
        try{
            $data = DealLoanRepayModel::instance()->getUserPrepayRecord($deal_id,$prepayId);
            $data = $this->formatData($data);
        }catch(\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $prepayId,"getUserPrepayRecord error")));
            return false;
        }
        return $this->syncToJf($deal_id,$data);
    }

    /**
     * 同步流标的处理记录
     * @param int $deal_id 流标的标的ID
     * @return bool
     */
    public function syncFailToJf($deal_id) {
        try {
            $data = DealLoadModel::instance()->getDealLoanList($deal_id);
            $data = $this->_formatFailData($data);
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, "getDealLoadRecord error")));
            return false;
        }
        return $this->syncToJf($deal_id, $data);
    }

    /**
     * 同步普通标的回款记录
     * @param integer $deal_id 已回款的标ID
     * @param integer $prepayId
     */
    public function syncNormalToJf($deal_id,$repayId) {
        if(!$this->isToSync($deal_id)) {
            return true;
        }

        //获取提前还款的回款记录
        try{
            $data = DealLoanRepayModel::instance()->getUserNormalRecord($deal_id,$repayId);
            $data = $this->formatData($data);
        }catch(\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $repayId,"getUserNormalRecord error")));
            return false;
        }
        return $this->syncToJf($deal_id,$data);
    }

    /**
     * 同步通知贷回款记录
     * @param integer $deal_id 已回款的标ID
     * @param integer $real_time 回款时间
     */
    public function syncCompoundToJf($deal_id,$real_time) {
        if(!$this->isToSync($deal_id)) {
            return true;
        }

        //获取通知贷回款记录
        try{
            $data = DealLoanRepayModel::instance()->getUserCompoundRecord($deal_id,$real_time);
            if (!$data) {
                return true;
            }
            $data = $this->formatData($data);
        }catch(\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $real_time,"loadRepayRecord error")));
            return false;
        }
        return $this->syncToJf($deal_id,$data);
    }

    /**
     * 即付可以投主站标的，所有标的都会向即付同步
     */
    private function isToSync($deal_id) {
        return true;
        /*
        $dealService = new DealService();

        //非即付宝类型的标不需要同步
        if(!$dealService->isDealJF(false,$deal_id)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,$deal_id,"is not jifu type")));
            return false;
        }
        return true;
        */
    }

    /**
     * 同步回款记录到即付宝
     * @param integer $deal_id 已回款的标ID
     * @param integer $real_time 回款时间
     */
    private function syncToJf($deal_id,$data) {
        if(empty($data)) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,"deal_id:".$deal_id." empty deal_loan_repay data")));
            return false;
        }
        Logger::info("Begin to sync to jf deal_id:".$deal_id);

        $txtData = '';
        $totalRecord = count($data); // 总的回款记录数
        $totalMoney = 0;        // 总的回款金额
        $totalPrincipal = 0;    // 总的本金
        $totalInterest = 0;     // 总的利息
        $totalPrepayClaim =0;   // 总的提前还款补偿金
        $totalOverDue = 0 ;     // 总的逾期罚息

        foreach ($data as $key=>$val) {
            $orderInfo = ThirdpartyOrderModel::instance()->getOrderByDealLoanId($val['deal_loan_id']);
            $orderId = $orderInfo['order_id'];
            $orderStatus = $orderInfo['order_status'];
            $userMobile = $orderInfo['mobile'];
            $userId = $orderInfo['user_id'];
            if(!$orderId) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id,"ThirdpartyOrderModel order_id is empty")));
                continue;
                //return false;
            }
            $data[$key]['order_id'] = $orderId;

            $tmpTotal = $val['money'] + $val['interest'] + $val['prepayClaim'] + $val['overDue'];
            $txtData.=implode("|", array($orderId,
                                        $orderStatus,
                                        Aes::encryptForJFB($userId),
                                        Aes::encryptForJFB($userMobile),
                                        $deal_id,
                                        $val['deal_loan_id'],
                                        $tmpTotal,
                                        date('Y-m-d H:i:s',$val['real_time']),
                                        $val['money'],
                                        $val['interest'],
                                        $val['prepayClaim'],
                                        $val['overDue'],
                                        $orderInfo['repay_transfer_id'],
                                        date('Y-m-d H:i:s', $orderInfo['create_time']),
                                    )). "\n";
            $totalPrincipal=bcadd($totalPrincipal,$val['money'],2);
            $totalInterest=bcadd($totalInterest,$val['interest'],2);
            $totalPrepayClaim=bcadd($totalPrepayClaim,$val['prepayClaim'],2);
            $totalOverDue=bcadd($totalOverDue,$val['overDue'],2);
        }

        /**
         * 同步到FTP服务器
         */
        //$totalMoney = $totalPrincipal + $totalInterest + $totalPrepayClaim + $totalOverDue;
        $totalMoney = bcadd(bcadd($totalPrincipal,$totalInterest,2),bcadd($totalPrepayClaim,$totalOverDue,2),2);

        if ($totalMoney <= 0) {
            return true;
        }

        // 自动提现开始
        $GLOBALS['db']->startTrans();
        try {
            $deal_service = new DealService();
            $deal_service->jfWithdrawal(app_conf('AGENCY_ID_JF_REPAY'), $totalMoney, $deal_id);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, "自动提现失败")));
            $content = "用户ID:" . app_conf('AGENCY_ID_JF_REPAY') . ",标的ID:{$deal_id},金额:{$totalMoney},自动提现失败,原因:" . $e->getMessage();
            \libs\utils\Alarm::push('deal', '即付回款账户自动提现失败', $content);
        }
        // 自动提现结束

        $txtData="Total|".$totalRecord."|".$totalMoney. "|" . $totalPrincipal . "|".$totalInterest."|".$totalPrepayClaim."|".$totalOverDue ."\n" . trim($txtData);

        $uploadRes = $this->uploadToFtp($deal_id,$txtData);

        if(!$uploadRes) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id,"uploadToFtp fail")));
            return false;
        }else{
            return $this->doubleCheck($uploadRes,$totalRecord,$totalMoney);
        }
        return true;
    }


    /**
     * 上传到ftp目录
     * @param integer $deal_id
     * @param string $data
     * @return boolean
     */
    private function uploadToFtp($deal_id,$data) {
        $fileName = $deal_id . "_" .time() . mt_rand(100,999) . ".txt";
        $localTmpFile = APP_ROOT_PATH.'runtime/' . $fileName;

        // 暂存到本地
        $res = file_put_contents($localTmpFile, $data);
        if($res===false) {
            Logger::error("uploadToFtp|save file to runtime error:".$localTmpFile);
            return false;
        }

        $ftpFilePath = $GLOBALS['components_config']['jifubao']['ftp_dir'] . self::FTP_LOAN_REPAY_DIR . "/" .date('Y-m-d') . '/';

        try {
            $ftp = new FdsFTP($GLOBALS['components_config']['jifubao']['ftp']);
            $ftpRes = $ftp->write($ftpFilePath, $fileName, $localTmpFile);
        }catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $ftpFilePath, $fileName,$localTmpFile)));
            return false;
        }

        return $ftpRes ? $ftpFilePath.$fileName : false;
    }

    /**
     * 对已经上传的文件进行doubleCheck
     * 考虑到读取文件解析对性能影响目前只判断文件是否存在
     * 后续有需求可以增加严格的文件比对
     *
     * @param string $ftpFile ftp文件
     * @param integer $totalCount 总记录数
     * @param float $totalMoney 总金额
     * @return boolean
     */
    private function doubleCheck($ftpFile,$totalCount,$totalMoney) {
        try{
            $ftp = new FdsFTP($GLOBALS['components_config']['jifubao']['ftp']);

            $size = ftp_size(FdsFTP::$_ftpHandler, $ftpFile);
            if($size==-1) {
                Logger::error(__CLASS__ ."|".__FUNCTION__. "|" ." Ftp doubleCheck file:".$ftpFile." is not exists");
                return false;
            }
        }catch (\Exception $e) {
            Logger::error(__CLASS__ ."|".__FUNCTION__. "|" ."ftp doubleCheck ftpFile:".$ftpFile ." " .$e->getMessage());
            fclose(FdsFTP::$_ftpHandler);
            return false;
        }
        Logger::info("ftp doubleCheck success ftpFile:".$ftpFile);
        return true;
    }

    /**
     * 处理流标逻辑数据
     * @param array $data
     * @return array
     */
    private function _formatFailData($data) {
        $now = time();
        $formatData = array();
        foreach ($data as $k => $v) {
            $formatData[$v['id']] = array(
                'loan_user_id' => $v['user_id'],
                'deal_loan_id' => $v['id'],
                'money' => $v['money'],
                'real_time' => $now,
            );
        }
        return $formatData;
    }

    private function formatData($data) {
        $formatData = array();
        foreach($data as $k=>$v) {
            $fieldMoney = $this->getMoneyByType($v['type'],$v['money']);
            if(!isset($formatData[$v['deal_loan_id']])) {
                $formatData[$v['deal_loan_id']] = array(
                    'loan_user_id' => $v['loan_user_id'],
                    'deal_loan_id' => $v['deal_loan_id'],
                    'money' => $fieldMoney['money'],
                    'interest' => $fieldMoney['interest'],
                    'prepayClaim' => $fieldMoney['prepayClaim'],
                    'overDue' => $fieldMoney['overDue'],
                    'real_time' => $v['real_time'],
                );
            }else{
                $formatData[$v['deal_loan_id']]['money']+=$fieldMoney['money'];
                $formatData[$v['deal_loan_id']]['interest']+=$fieldMoney['interest'];
                $formatData[$v['deal_loan_id']]['prepayClaim']+=$fieldMoney['prepayClaim'];
                $formatData[$v['deal_loan_id']]['overDue']+=$fieldMoney['overDue'];
            }
        }
        return $formatData;
    }

    /**
     * 1-本金 2-利息 3-提前还款 4-提前还款补偿金 5-逾期罚息 6-管理费 7-提前还款利息 8-利滚利赎回本金 9-利滚利赎回利息
     */
    private function getMoneyByType($type,$money) {
        $typeMoney = array(
            'money' => 0,  // 本金
            'interest' => 0, // 利息
            'prepayClaim' => 0, // 提前还款补偿金
            'overDue' => 0,  // 逾期罚息
        );

        switch($type) {
            case 1 :
            case 3 :
            case 8 :
                $typeMoney['money']+=$money;
                break;
            case 2 :
            case 7 :
            case 9 :
                $typeMoney["interest"]+=$money ;
                break;
            case 4:
                $typeMoney["prepayClaim"]+=$money;
                break;
            case 5 :
                $typeMoney["overDue"]+=$money;
                break;
        }
        return $typeMoney;
    }
}
