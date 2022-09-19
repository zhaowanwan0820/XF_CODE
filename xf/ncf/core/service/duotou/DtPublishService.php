<?php
/**
 * DtPublishService.php
 * 多投信息披露服务
 * @date 2018-01-07
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
namespace core\service\duotou;

use core\service\deal\DealService;
use core\service\duotou\DtCancelService;

class DtPublishService extends DuotouService{

    /**
     * 获取信息披露的所有P2p标的信息
     * @param unknown $userId 用户Id
     * @param unknown $pageNum 第几页
     * @param unknown $pageSize 每页条数
     */
    public function getPublishP2pDeals($userId,$pageNum=1,$pageSize=20) {
        $p2pDeals = array();
        $request = array(
            'userId' => $userId,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        );

        $return =array();

        $response = self::callByObject(array('NCFGroup\Duotou\Services\P2pDeal', 'getPublishP2pDeals', $request));

        $totalPage = 1;
        $totalNum = 0;
        if(!empty($response)){
            $datas = $response['data']['data'];
            $totalNum = $response['data']['totalNum'];
            $totalPage = $response['data']['totalPage'];
            $dealService = new DealService();
            foreach ($datas as $data) {
                $deal = $dealService->getDeal($data['dealId'], true, false);
                if(!empty($deal)) {
                    $dealInfo = $deal->getRow();
                    $dealInfo['loantype_show'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
                    $dealInfo['point_percent_show'] = bcmul(strval($dealInfo['point_percent']),'100.00',2);
                    $p2pDeals[] = $dealInfo;
                }
            }
        }

        $return['list'] = $p2pDeals;
        $return['totalNum'] = $totalNum;
        $return['totalPage'] = $totalPage;
        return $return;
    }

    /**
     * 获取是否可以取消
     * @param unknown $userId 用户Id
     */
    public function getCanCancelToday($userId) {
        $dtCancelService = new DtCancelService();
        $canCancel = $dtCancelService->canCancelToday($userId) ? 1 : 0;
        return $canCancel;
    }

}
