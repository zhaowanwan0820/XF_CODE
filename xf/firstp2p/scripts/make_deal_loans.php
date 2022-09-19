<?php
/**
 * [定时放款脚本，传入参数为标的类型标识]
 * @author <fanjingwen@ucfgroup.com>
 */

require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
use core\dao\DealModel;
use core\service\DealService;
use NCFGroup\Common\Library\Idworker;

set_time_limit(0);
ini_set('memory_limit', '2048M');

// 引入phalcon RPC相关，使jobs异步处理RPC请求
\libs\utils\PhalconRPCInject::init();

// 获取对应类型满标的标的
if (!isset($argv[1])) {
    exit("请指定标的类型\n");
}

$dealType = intval($argv[1]);
$condition = " `is_delete` = 0  AND `type_id` = $dealType  AND `deal_status` = ".DealModel::$DEAL_STATUS['full'];

$dealList = DealModel::instance()->findAll($condition, true,'*');
// 对标的列表执行放款操作
$dealService = new DealService();
foreach ($dealList as $dealInfo) {

    $GLOBALS['db']->startTrans();
    try {
        $dealService->isOKForMakingLoans($dealInfo); // 不符合条件抛出异常

        $dealService = new DealService;

        if ($dealService->saveServiceFeeExt($dealInfo) === false) {
            throw new \Exception("auto save deal ext fail. Error:deal id:" . $dealInfo['id']);
        }

        if(!$dealService->isP2pPath(intval($dealInfo['id']))) {
            $function = '\core\service\DealService::makeDealLoansJob';
            $param = array('deal_id' => intval($dealInfo['id']), 'admin' => '', 'submit_uid' => 0);
        }else{
            $orderId = Idworker::instance()->getId();
            $function = '\core\service\P2pDealGrantService::dealGrantRequest';
            $param = array(
                'orderId' => $orderId,
                'dealId'=>intval($dealInfo['id']),
                $param = array('deal_id' => intval($dealInfo['id']), 'admin' => '', 'submit_uid' => 0),
            );
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$dealInfo['id']);
        }


        $job_model = new \core\dao\JobsModel();
        $job_model->priority = 99;
        //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
        if (false == $job_model->addJob($function, $param, get_gmtime() + 180)) {
            throw new \Exception('addJob fail. Error:deal type:' . $dealType . ",deal id:" . $dealInfo['id']);
        }

        $deal_model = new DealModel();
        //更新标放款状态
        if (false == $deal_model->changeLoansStatus(intval($dealInfo['id']), 2)) {
            throw new \Exception('changeLoansStatus fail. Error:deal type:' . $dealType . ",deal id:" . $dealInfo['id']);
        }

        //更新标的还款中状态
        if (false == $deal_model->changeDealStatus(intval($dealInfo['id']))) {
            throw new \Exception('changeDealStatus fail. Error:deal type:' . $dealType . ",deal id:" . $dealInfo['id']);
        }

        // 更新标还款时间
        if (false == $deal_model->changeRepayStartTime(intval($dealInfo['id']), to_timespan(date('Y-m-d 00:00:00')))) {
            throw new \Exception('changeRepayStartTime fail. Error:deal type:' . $dealType . ",deal id:" . $dealInfo['id']);
        }

        $GLOBALS['db']->commit();
    } catch (\Exception $e) {
        $GLOBALS['db']->rollback();
        Logger::warn($e->getMessage());
        continue;
    }

    Logger::info('makeDealloans done:deal type:' . $dealType . ",deal id:" . $dealInfo['id']);
}
