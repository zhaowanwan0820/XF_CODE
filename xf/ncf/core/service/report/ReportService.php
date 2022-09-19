<?php
/**
 * Created by PhpStorm.
 * User: wangjiantong
 * Date: 2019/5/7
 * Time: 17:01
 */
namespace core\service\report;

use core\dao\repay\DealRepayModel;
use libs\utils\Logger;

use core\dao\report\ReportRecordModel;
use core\dao\report\ReportDealModel;
use core\dao\report\ReportDealStatusModel;
use core\dao\report\ReportUserModel;
use core\dao\report\ReportCompanyUserModel;
use core\dao\report\ReportRepayModel;
use core\dao\deal\DealModel;
use libs\utils\Aes;
use libs\utils\DBDes;

class ReportService extends ReportBase
{

    public static function reportIfaDeal($id){

        $record = ReportRecordModel::instance()->find($id);

        if(in_array($record['record_status'],array(self::REPORT_STATUS_NOTIFY_FAILD,self::REPORT_STATUS_SUCCESS,self::REPORT_STATUS_WAIT_NOTIFY,self::REPORT_STATUS_NOTIFY_SUCCESS))){
            return true;
        }

        //报备数据整理
        $report = new ReportBase();
        $reportData = array();

        $reportData = self::getReportData($record);
        //$reportData = $this->getReportData($record);

        $res = $report->ifaPush($reportData);

        if($res['msgCode'] === '20000'){
            Logger::error(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 推送成功! ");

            $record->record_status = 1;
            $record->update_time = time();

            $res = $record->save();
            return true;

        }else{
            Logger::error(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 推送失败 !".$res['msgContent']);
            throw new \Exception("推送失败!".$res['msgContent']);
        }

    }


    private static function getReportData($record){

        if(!empty($record)){
            $dealInfo = ReportDealModel::instance()->findBy('deal_id = '.$record['deal_id']);
            $deal = DealModel::instance()->getDealInfo($record['deal_id'],true);
            $minLoanMoney = $deal['min_loan_money'];

            //报备数据整理
            $report = new ReportBase();
            $reportData = array();

            if($dealInfo['borrower_type'] === self::IFA_REPORT_BORROWER_TYPE_PERSON){
                $borrowType = self::IFA_REPORT_BORROWER_TYPE_PERSON;
            }else if($dealInfo['borrower_type'] === self::IFA_REPORT_BORROWER_TYPE_ENTERPRISE){
                $borrowType = self::IFA_REPORT_BORROWER_TYPE_ENTERPRISE;
            }

            $dealProjectInfo = $report->getProjectInfo($borrowType,$dealInfo['approve_number']);

            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ",borrowType:".$borrowType."approve_number:".$dealInfo['approve_number']." 获取信贷数据: ".json_encode($dealProjectInfo,JSON_UNESCAPED_UNICODE));

            //项目简介
            $projectInfo = isset($dealProjectInfo['projectInfo'])?$dealProjectInfo['projectInfo']:'无';
            //借款用途
            $loanPurpose = isset($dealProjectInfo['loanPurpose'])?$dealProjectInfo['loanPurpose']:'未知';
            //还款保障措施
            $securityMeasure = isset($dealProjectInfo['securityMeasure'])?$dealProjectInfo['securityMeasure']:'担保公司';
            $securityMeasure = str_replace('。','',$securityMeasure);
            //征信报告
            //$creditReportTip = isset($dealProjectInfo['creditReportTip'])?$dealProjectInfo['creditReportTip']:'未知'; 暂时不传creditReportTip
            $creditReportTip = "当前逾期账户数：".$dealProjectInfo['numberOfOverdueAccount']."，当前逾期金额：".$dealProjectInfo['currentOverdueAmount'];
            //逾期信息
            $yqInfo = isset($dealProjectInfo['projectInfo'])?'本平台逾期次数:'.$dealProjectInfo['platformOverdueNumber'].';本平台逾期金额:'.$dealProjectInfo['platformOverdueAmount'].';其他平台借款金额:'.$dealProjectInfo['otherFinancingInstitutionBorrow']:'未知';
            //逾期次数
            $overdueTimes = isset($dealProjectInfo['platformOverdueNumber'])?$dealProjectInfo['platformOverdueNumber']:0;
            //逾期金额
            $overdueMoney = isset($dealProjectInfo['platformOverdueAmount'])?$dealProjectInfo['platformOverdueAmount']:0.00;

            $industryType = isset($dealProjectInfo['industryType'])?$dealProjectInfo['industryType']:'所属行业暂未获取';

            //还款来源
            //$repaymentSafeguard = isset($dealProjectInfo['repaymentSafeguard'])?$dealProjectInfo['repaymentSafeguard']:'未知';
            $repaymentSafeguard = "借款人本人,第2还款来源:".$securityMeasure;
            //借款人收入负债情况
            if($dealInfo['borrower_type'] === self::IFA_REPORT_BORROWER_TYPE_PERSON){
                $incomeDebt = isset($dealProjectInfo['projectInfo'])?'月收入:'.$dealProjectInfo['monthIncome']." 负债:".$dealProjectInfo['debtSituation'].'元':'未知';
            }else if($dealInfo['borrower_type'] === self::IFA_REPORT_BORROWER_TYPE_ENTERPRISE){
                $incomeDebt = isset($dealProjectInfo['projectInfo'])?'年收入:'.$dealProjectInfo['yearIncome']." 负债:".$dealProjectInfo['debtSituation'].'元':'未知';
            }
            //工作性质
            $workInfo = isset($dealProjectInfo['workNature'])?$dealProjectInfo['workNature']:'未知';

            //项目链接
            $dealUrl = 'https://'.app_conf("FIRSTP2P_CN_DOMAIN").'/d/'.Aes::encryptForDeal(intval($dealInfo['deal_id']));

            //出借人适当性管理提示
            $tips = '1) 政策风险</br>因国家法律、法规、行政规章或政策发生重大调整、变化或其他不可预知的意外事件，可能导致出借人无法实现全部利息乃至本金遭受损失。</br>2) 信用风险</br>无论何种原因，当承担偿还责任的借款人不能按时偿付本金和利息，将导致出借人无法实现全部利息，甚至本金遭受损失。</br>3) 操作风险</br>a)不可预测或无法控制的系统故障、设备故障、通讯故障、停电等突发事故将有可能给出借人造成一定损失；</br>b)由于存在互联网和移动通讯网络的黑客恶意攻击可能性，出借人可能会遭受损失；</br>c)出借人的账号及密码信息有可能被盗，客户身份可能被仿冒，出借人可能遭受因此导致的损失；</br>d)出借人的网络终端设备及软件系统可能会受到非法攻击或病毒感染，导致电子签名合同数据无法传输或传输失败，从而遭受损失；</br>e)网上交易、热键操作完毕，未及时退出，他人进行恶意操作将可能造成出借人损失；</br>f)由于通信故障、系统故障以及其他不可抗力等因素的影响，可能导致出借人无法及时做出合理的决策，造成损失。</br>4) 其他风险</br>a) 由于自然灾害、战争、法律法规或者政策等无法避免或无法控制的因素出现，将影响市场的正常运行，从而导致出借标的损失；</br>b) 金融市场危机、行业竞争、代理商违约等超出借款人自身直接控制能力之外的风险，也可能导致出借人利益受损；</br>c) 因其他意外因素和不可抗力而导致的风险。</br>5）项目放款后7-30个工作日进行项目贷后检查，披露借款人资金使用情况，依据重要性及可操作性原则，若项目金额低于1万元（含），将不再对资金用途进行复核。';

            if(empty($dealInfo)){
                Logger::error(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 未查询到报备标的信息 ".json_encode($record['deal_id']));
                throw new \Exception("未查询到报备标的信息");
            }


            //社会信用代码 sbankcode
            $reportData['sbankcode'] = $report->getIfaSBankCode();
            //报文体代码 sdatacode
            $reportData['sdatacode'] = $record['ifa_sdata_sn'];
            $sdata = array();
            $dealDataArray = array();

            //判断报送类型
            if($record['record_type'] === self::IFA_REPORT_TYPE_DEAL){
                $sdata['REPAYPROJECT'] = '';
                $dealDataArray = array(
                    $dealInfo['name'],$dealInfo['deal_id'],$reportData['sbankcode'],$projectInfo,
                    $dealUrl,$loanPurpose,$dealInfo['borrow_amount'],
                    $dealInfo['period_type'],$dealInfo['period'],$dealInfo['rate'],
                    '不晚于项目满标后下一个工作日',$dealInfo['repay_type'],$dealInfo['repay_type_explain'],
                    '02',date('YmdHis'),$securityMeasure,$repaymentSafeguard,
                    $dealInfo['risk_level'],'相关费用无',$reportData['sbankcode'].$dealInfo['contract_template'],
                    $tips,$borrowType
                );

                $dealDataStr = implode('|',$dealDataArray);
                $sdata['REPAYPROJECT'][] = $dealDataStr;

                if($dealInfo['borrower_type'] === self::IFA_REPORT_BORROWER_TYPE_PERSON){
                    //个人
                    $userInfo = ReportUserModel::instance()->findBy("deal_id =".$record['deal_id']);
                    $userDataArray = array();

                    $userDataArray = array(
                        $dealInfo['deal_id'],$reportData['sbankcode'],$userInfo['name'],
                        $userInfo['id_type'],$userInfo['id_num'],$workInfo,
                        $userInfo['extra_info'],$creditReportTip,$overdueTimes,
                        number_format($overdueMoney,2,'.',''),$incomeDebt
                    );

                    $userDataStr = implode('|',$userDataArray);
                    $sdata['REPAYMENINFO'][] = $userDataStr;


                }else if($dealInfo['borrower_type'] === self::IFA_REPORT_BORROWER_TYPE_ENTERPRISE){
                    //企业
                    $sdata['REPAYMEN_LEGAL'] = '';
                    $companyInfo = ReportCompanyUserModel::instance()->findBy("deal_id =".$record['deal_id']);
                    $registeredCapital = !empty($companyInfo['registered_capital'])?$companyInfo['registered_capital']:'0.00';
                    $registeredAddress = !empty($companyInfo['registered_address'])?$companyInfo['registered_address']:'公司注册地址暂未获取';
                    $creditReportTip = '暂时无法提供';

                    $companyDataArray = array(
                        $dealInfo['deal_id'],$reportData['sbankcode'],$companyInfo['name'],
                        number_format($registeredCapital,2,'.',''),$registeredAddress,
                        $companyInfo['start_time'],$companyInfo['corporate'],'',$industryType,
                        $incomeDebt,$creditReportTip,$yqInfo,
                        '','','','',$companyInfo['id_num']
                    );

                    $companyDataStr = implode('|',$companyDataArray);
                    $sdata['REPAYMEN_LEGAL'][] = $companyDataStr;

                }else{
                    throw new \Exception("未知标的borrower_type");
                }
            }else if($record['record_type'] === self::IFA_REPORT_TYPE_DEAL_STATUS){
                $sdata['PLATFORMDEBTTRANSFERPROJECT'] = '';
                $dealStatusInfo = ReportDealStatusModel::instance()->find($record['record_id']);

                $dealStatusDataArray = array(
                    $dealInfo['deal_id'],$reportData['sbankcode'],$dealStatusInfo['deal_status'],
                    '','','','','','',date('Ymd'),date('YmdHis')
                );

                $dealStatusDataStr = implode('|',$dealStatusDataArray);
                $sdata['PLATFORMDEBTTRANSFERPROJECT'][] = $dealStatusDataStr;

            }else{
                throw new \Exception("未知record type");
            }

            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 报备数据: ".json_encode($sdata,JSON_UNESCAPED_UNICODE));
            
            $encryptSdata = $report->ifaEncrypt(json_encode($sdata,JSON_UNESCAPED_UNICODE));

            $decryptSdata = $report->ifaDecrypt(strval($encryptSdata));

            //报文体
            $reportData['sdata'] = strval($encryptSdata);

            //消息认证码 scode
            $reportData['scode'] = md5($reportData['sdata']);
            //数据签名 sign
            $reportData['sign'] = strval($report->ifaSign($reportData['sbankcode'].$reportData['sdatacode'].$reportData['scode']));
            //报文体

            return $reportData;

        }else{
            Logger::error(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 未查询到报备记录 ".json_encode($record['record_id']));
            throw new \Exception("未查询到报备记录");
        }
    }
    public static function reportBaihang($id){

        $record = ReportRecordModel::instance()->find($id);

        if(in_array($record['record_status'],array(self::REPORT_STATUS_NOTIFY_FAILD,self::REPORT_STATUS_SUCCESS,self::REPORT_STATUS_WAIT_NOTIFY,self::REPORT_STATUS_NOTIFY_SUCCESS))){
            return true;
        }

        //报备数据整理
        $report = new ReportBase();
        $reportData = self::getBaihangReportData($record);
        if (!$reportData){
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ",获取百行上报数据失败 !".json_encode($record));
            throw new \Exception("获取百行上报数据失败 !".json_encode($record));
        }
        $reportData['sdata'] = $report->baihangEncrypt($reportData['sdata']);
        //数据报送
        $res = $report->baihangPush($reportData);

        if(isset($res['status']) && ($res['status'] === 'success')){
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 推送成功! ");

            $record->record_status = 1;
            $record->update_time = time();

            $recordSave = $record->save();
            return true;

        }else{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 推送失败 !".json_encode($res));
            throw new \Exception("推送失败!".json_encode($res));
        }

    }

    public static function getBaihangReportData($record){

        if(!empty($record)){
            $dealInfo = ReportDealModel::instance()->findBy('deal_id = '.$record['deal_id']);

            if(empty($dealInfo)){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 未查询到报备标的信息 ".json_encode($record['deal_id']));
                return false;
            }
            $userInfo = ReportUserModel::instance()->findBy('deal_id = '.$record['deal_id']);
            if(empty($userInfo)){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 未查询到报备标的借款人信息 ".json_encode($record['deal_id']));
                return false;
            }

            $condition = "deal_id=:deal_id ORDER BY repay_time ASC";
            $repayInfo = DealRepayModel::instance()->findAll($condition, true, '*', array(':deal_id' => $record['deal_id']));
            if(empty($repayInfo)){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 未查询到报备标的还款计划 ".json_encode($record['deal_id']));
                return false;
            }
            $list = array();
            $repayDateList = array();
            foreach($repayInfo as $key=>$value){
                $list[] = $value['repay_time']+28800;
                $repayDateList[] = date('Y-m-d',$value['repay_time']+28800);
            }

            //报备数据整理
            $reportData = array();
            $report = new ReportBase();
            $reportData['authorization'] = $report->getBaihangAuthorization();

            //判断报送类型
            if($record['record_type'] == self::BAIHANG_REPORT_TYPE_LOAN){
                $data = array(
                    'reqID' => $record['deal_id'],
                    'opCode' => 'A',   //新增数据
                    'uploadTs' => date('Y-m-d\TH:i:s', $repayInfo[0]['create_time'] + 28800), // 还款计划表生成时间
                    'name' => $userInfo['name'],
                    'pid' => $userInfo['id_num'],
                    'mobile' => $userInfo['mobile'],
                    'loanId' => $record['deal_id'],
//                        'originalLoanId' => '',
                    'guaranteeType' => 1,   //信用
                    'loanPurpose' => $report->getBaihangLoanPurpose($dealInfo['purpose']),
                    'applyDate' => date('Y-m-d\TH:i:s',$dealInfo['apply_date']),  //贷款申请时间
                    'accountOpenDate' => date('Y-m-d\TH:i:s',$repayInfo[0]['create_time']+28800),     //还款计划表生成时间
                    'issueDate' => date('Y-m-d\TH:i:s',$repayInfo[0]['create_time']+28800),     //还款计划表生成时间
                    'dueDate' => end($repayDateList),
                    'loanAmount' => $dealInfo['borrow_amount'],
                    'totalTerm' => count($repayDateList),
                    'targetRepayDateType' => (count($repayDateList)==1) ? 1 : 2,
                    'termPeriod' => (count($repayDateList)==1) ? intval(($list[0]-$dealInfo['repay_start_time'])/86400) : -1,
                    'targetRepayDateList' =>(count($repayDateList) == 1) ? '' : implode(',',$repayDateList),
                    'firstRepaymentDate' => $repayDateList[0],
                    'gracePeriod' => 0,
                );
            }else if($record['record_type'] == self::BAIHANG_REPORT_TYPE_REPAY){
                $repayInfo = ReportRepayModel::instance()->find($record['record_id']);
                $termStatus = (date('Y-m-d',$repayInfo['target_repayment_time'])>=date('Y-m-d',$repayInfo['real_repayment_time'])) ? self::BAIHANG_TERM_STATUS_NORMAL : self::BAIHANG_TERM_STATUS_OVERDUE;
                $data = array(
                    'reqID' => $repayInfo['repay_id'],
                    'opCode' => 'A',
                    'uploadTs' => date('Y-m-d\TH:i:s',$repayInfo['real_repayment_time']),
                    'loanId' => $record['deal_id'],
                    'name' => $userInfo['name'],
                    'pid' => $userInfo['id_num'],
                    'mobile' => $userInfo['mobile'],
                    'termNo' => $repayInfo['term_no'],
                    'termStatus' => $termStatus,
                    'targetRepaymentDate' => date('Y-m-d',$repayInfo['target_repayment_time']),
                    'realRepaymentDate' => date('Y-m-d\TH:i:s',$repayInfo['real_repayment_time']),
                    'plannedPayment' => $repayInfo['planned_payment'],
                    'targetRepayment' => $repayInfo['real_repayment'],
                    'realRepayment' => $repayInfo['real_repayment'],
                    'overdueStatus' => ($termStatus==self::BAIHANG_TERM_STATUS_NORMAL) ? '' : 'D' . floor(($repayInfo['real_repayment_time'] - $repayInfo['target_repayment_time']) / 86400),
                    'statusConfirmAt' => date('Y-m-d\TH:i:s',$repayInfo['real_repayment_time']),
                    'overdueAmount' => 0,
                    'remainingAmount' => $repayInfo['remaining_amount'],
                    'loanStatus' => ($repayInfo['loan_status'] == self::LOAN_STATUS_CLEARED) ? self::BAIHANG_LOAN_STATUS_CLEARED : self::BAIHANG_LOAN_STATUS_NORMAL,
                );

            }else{
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ",未知record type ".json_encode($record['deal_id']));
                return false;
            }
//            $data['pid'] = DBDes::decryptOneValue($data['pid']);
//            $data['mobile'] = DBDes::decryptOneValue($data['mobile']);
            $reportData['sdata'] = $data;
            $reportData['reportUrl'] = $report->getBaihangReportUrl($record['record_type']);

            return $reportData;

        }else{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 未查询到报备记录 ".json_encode($record['record_id']));
            return false;
        }
    }
}
