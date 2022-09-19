<?php
/**
 * auto_sign_and_loan_for_zhangzhong.php
 * 合同实时代签,定时放款脚本 掌众项目专用
 * 脚本部署例子
 * @author <wangchuanlu@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use core\dao\DealModel;
use core\dao\DealLoanTypeModel;
use core\service\DealService;
use core\service\ContractService;
use NCFGroup\Common\Library\Idworker;

set_time_limit(0);
ini_set('memory_limit', '2048M');

/**
 * 签署合同
 */
class ContractSign{
    public function run(){
        $contractService = new ContractService();
        $contractService->autoAgencySignContract();
        Logger::info('zhangzhong_contract_sign done');
    }
}

/**
 * 放款
 */
class DealLoansMake {
    public function run(){
        // 引入phalcon RPC相关，使jobs异步处理RPC请求
        \libs\utils\PhalconRPCInject::init();

        $typeIds = DealLoanTypeModel::instance()->getAutoLoanTypeId();

        if(count($typeIds) == 0){
            return ;
        }else{
            foreach($typeIds as $typeId){
                $ids[] = $typeId['id'];
            }
        }

        $typeIdsStr = implode(',',$ids);

        $condition = " `is_delete` = 0  AND `type_id` IN (".$typeIdsStr.") AND `deal_status` = ".DealModel::$DEAL_STATUS['full'];

        $dealList = DealModel::instance()->findAll($condition, true,'*');

        // 对标的列表执行放款操作
        $dealService = new DealService();
        foreach ($dealList as $dealInfo) {
            $GLOBALS['db']->startTrans();
            try {
                $dealService->isOKForMakingLoans($dealInfo); // 不符合条件抛出异常

                if ($dealService->saveServiceFeeExt($dealInfo) === false) {
                    throw new \Exception("Save deal ext fail. Error:deal id:" . $dealInfo['id']);
                }


                //放款添加到jobs
                if(!$dealService->isP2pPath(intval($dealInfo['id']))) {
                    // 添加jobs
                    $function = '\core\service\DealService::makeDealLoansJob';
                    $param = array('deal_id' => intval($dealInfo['id']), 'admin' => '', 'submit_uid' => 0);
                }else{
                    $grantOrderId = Idworker::instance()->getId();
                    $function = '\core\service\P2pDealGrantService::dealGrantRequest';
                    $param = array(
                        'orderId'=>$grantOrderId,
                        'dealId'=>$dealInfo['id'],
                        'param'=>array('deal_id' => $dealInfo['id'], 'admin' => '', 'submit_uid' => 0),
                    );
                    Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$grantOrderId." dealId:".$dealInfo['id']);
                }

                $job_model = new \core\dao\JobsModel();
                $job_model->priority = 99;
                //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
                if (false == $job_model->addJob($function, $param)) {
                    throw new \Exception('addJob fail. Error deal id:' . $dealInfo['id']);
                }

                $deal_model = new DealModel();
                //更新标放款状态
                if (false == $deal_model->changeLoansStatus(intval($dealInfo['id']), 2)) {
                    throw new \Exception('changeLoansStatus fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
                }

                //更新标的还款中状态
                if (false == $deal_model->changeDealStatus(intval($dealInfo['id']))) {
                    throw new \Exception('changeDealStatus fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
                }

                // 更新标还款时间
                if (false == $deal_model->changeRepayStartTime(intval($dealInfo['id']), to_timespan(date('Y-m-d 00:00:00')))) {
                    throw new \Exception('changeRepayStartTime fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
                }

                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                Logger::warn($e->getMessage());
                continue;
            }
            Logger::info('makeDealloans done:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
        }
    }
}

if (!isset($argv[1])) {
    exit("请指定执行的命令参数\n 0 同时签署合同并放款 \n 1 只签署合同 \n 2 只放款");
}

$param = intval($argv[1]);

if($param != 2) {
    //签署合同
    $contractSign = new ContractSign();
    $contractSign->run();
}
if($param != 1) {
    //放款
    $dealLoansMake = new DealLoansMake();
    $dealLoansMake->run();
}
