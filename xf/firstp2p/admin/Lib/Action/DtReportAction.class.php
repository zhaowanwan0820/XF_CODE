<?php
/**
 * 多投宝每日资金报告
 * User: wangyiming@ucfgroup.com
 */

use core\service\DtDealService;
use NCFGroup\Protos\Duotou\RequestCommon;

class DtReportAction extends CommonAction {

    /**
     * 每日资金报告列表
     */
    public function index() {
        $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;

        $vars = array(
            'projectId' => $project_id,
        );

        $request = new RequestCommon();
        $request->setVars($vars);

        $rpc = new \libs\utils\Rpc('duotouRpc');

        $response = $this->getRpc('duotouRpc')->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\DailyReport',
            'method' => 'getReport',
            'args' => $request,
        ));
        if(!$response) {
            $this->error("rpc请求失败");
        }

        if($response['errCode'] != 0) {
            //$this->assign("jumpUrl",u(MODULE_NAME."/index"));
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $this->assign("data", $response['data']);
        $this->assign("time", date("Y-m-d H:i:s"));

        //测试调用p2p还款多投 待删除
//         $dtDealService = new DtDealService();
//         $dtDealService ->repayDeal(100 , 200, 3, 2000,100);
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }

}
