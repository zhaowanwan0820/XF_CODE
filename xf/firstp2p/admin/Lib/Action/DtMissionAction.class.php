<?php
/**
 * 多投宝每日任务监控
 * User: wangyiming@ucfgroup.com
 */


use core\service\UserService;
use libs\utils\Logger;
use NCFGroup\Protos\Duotou\RequestCommon;

class DtMissionAction extends CommonAction {

    public function index() {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = 5;

/*
        $request = new RequestCommon();
        $request->setVars(array("pageNum"=>$pageNum,"pageSize"=>$pageSize));

        $rpcResponse = $this->getRpc('duotouRpc')->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Deal',
            'method' => 'listDeal',
            'args' => $request,
        ));

        $p = new Page ($rpcResponse['totalNum'], 5);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );

        $this->assign("data",$rpcResponse['data']);
*/
        $this->display ('index');
    }

}
