<?php

namespace NCFGroup\Ptp\Apis;

use \core\service\vip\VipService;
use core\service\DealsListService;
use libs\rpc\Rpc;
use core\service\ApiConfService;
/**
 * 信仔列表信息接口
 */
class DealsListApi
{
    const DEALS_LIST_LIMIT = 3;
    private $params = [];

    private function init()
    {
        $di = getDI();
        $this->rpc = new Rpc();
        $this->params = $di->get('requestBody');
    }

    public function getList() {
        $result = array('errorCode' => 0, 'errorMsg' => '');
        try{
            $this->init();
        }catch (\Exception $ex){
            return array('errorCode' => -1, 'errorMsg' => $ex->getMessage(), 'data' => array());
        }
        $userId = $this->params['userId'];
        $limit = !empty($this->params['limit'])?$this->params['limit']:self::DEALS_LIST_LIMIT;
    
        $title_sxy = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ApiConfService\getApiAdvConf', array("title_sxy",1,0)), 300);
        $ratetext  = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ApiConfService\getApiAdvConf', array("ratetext",0,1)), 300);
        
        $dealsListService = new DealsListService();
        $dealsList = $dealsListService->getList($userId, $limit);
        foreach($dealsList as & $item){
            if($item['order_type'] == 1){
               $item['p2p_type'] = $item['type'];
            }
            $item['type'] = 1;
            $item['uri'] = '{"type":22}';
        }
        $result['data'] =   [
            'type' => 3,
            'title' => "信仔为您智能推荐如下产品",
            'content' => [
                'rowNum' => count($dealsList),
                'title_sxy' => $title_sxy[0]['value'],
                'ratetext'  => $ratetext[0]['value'],
                'actionList' => $dealsList
            ],
        ];
        return $result;
    }
}