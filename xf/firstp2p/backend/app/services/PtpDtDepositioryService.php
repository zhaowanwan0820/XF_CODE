<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\service\DtDepositoryService;
use libs\utils\Logger;

/**
 * 多投存管服务
 * Class PtpDtStatsService
 * @package NCFGroup\Ptp\services
 */
class PtpDtDepositioryService extends ServiceBase {

    /**
     * 多投还款回调，通知P2p拉取数据
     * @param RequestCommon $request
     * @return array
     */
    public function dtRepayCallBack(RequestCommon $request) {
        $vars = $request->getVars();
        $orderId = intval($vars['orderId']);//订单id
        $manageId = intval($vars['manageId']);// 管理机构ID
        $ds = new DtDepositoryService();
        $res = array(
            'code' => 0,
            'msg' => '',
            'data' => '',
        );
        try{
            $res['data']  = $ds->dtRepayCallBack($orderId,$manageId);
        }catch (\Exception $ex) {
            $res['code'] = 1;
            $res['msg'] = $ex->getMessage();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
        }
        return $res;
    }

    /**
     * 判断智多鑫相关的存管是否都做完了
     * @param RequestCommon $request
     * @return array
     */
    public function dtIsAllDepositioryHandleFinished(RequestCommon $request) {
        $vars = $request->getVars();
        $ds = new DtDepositoryService();
        $res = array(
            'code' => 0,
            'msg' => '',
            'data' => '',
        );

        try{
            $service = new DtDepositoryService();
            $res = $service->isFinishDtTask();
            if(!$res){
                throw new \Exception("存管有任务未处理完成");
            }
        }catch (\Exception $ex) {
            $res['code'] = 1;
            $res['msg'] = $ex->getMessage();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
        }
        return $res;
    }

    public function dtMappingFinishedNotify(RequestCommon $request){
        $vars = $request->getVars();
        $orderId = intval($vars['token']);//订单id
        $tableNum = intval($vars['tableNum']);//分表数量
        $date = intval($vars['date']);//匹配日期
        $ds = new DtDepositoryService();
        $res = array(
            'code' => 0,
            'msg' => '',
            'data' => '',
        );
        try{
            $res['data']  = $ds->dtMappingFinishCallBack($orderId,$tableNum,$date);
        }catch (\Exception $ex) {
            $res['code'] = 1;
            $res['msg'] = $ex->getMessage();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
        }
        return $res;
    }
}