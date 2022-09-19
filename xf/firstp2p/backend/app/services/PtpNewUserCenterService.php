<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\NewUserPageService;
use core\service\UserService;
use core\dao\ReservationEntraModel;
use core\service\ReservationEntraService;
use core\service\DealLoadService;
use libs\utils\Aes;
use libs\utils\Logger;

/**
 * PtpNewUserCenterService
 *
 * @uses ServiceBase
 * @package default
 */
class PtpNewUserCenterService extends ServiceBase {

    public function getNewUserCenterInfo(SimpleRequestBase $request) {
        $params = $request->getParamArray();

        $newUserService = new NewUserPageService();

        try {
            $response = new ResponseBase();
            $result['status'] = $newUserService->isNewUserSwitchOpen();//新手专区的开关
            $result['userStatus'] =  $newUserService->getNewUserProgress($params['userId']);//用户的状态
            $result['imgList'] =  $newUserService->getPageInfoByInviteCode($params['clientInviteCode']);//配置图片列表
            Logger::info(implode(' | ', array(__CLASS__,APP, json_encode(array('pageId' => $result['imgList']['id'],'inviteCode' => $params['clientInviteCode'], 'userId' => $params['userId'])))));
            $result['registerCount'] =  (new UserService())->getCountByDay($params['time']);//注册人数
            $result['loadUserCount'] =  (new DealLoadService())->getLoadUsersNumByTime();//注册人数


            $entraService = new ReservationEntraService();
            $cardList = $entraService->getReserveEntraDetailList(ReservationEntraModel::STATUS_VALID, 2);
            foreach($cardList['list'] as $k=>$v){
                $cardList['list'][$k]['rate'] = substr($v['rate'],0,-1);
            }
            $result['reservationCardList'] = $cardList['list'];
            $newUserDealsCount = 3 - count($result['reservationCardList']);

            $newUserDealsList =  $newUserService->getNewUserDeals($params['siteId'],$newUserDealsCount);//可投资列表
            $dealsList = array();
            foreach ($newUserDealsList as $key => $value){
                $dealsList[$key]['id'] = $value['id'];
                $dealsList[$key]['ecid'] = Aes::encryptForDeal($value['id']);
                $dealsList[$key]['name'] = $value['name'];
                $dealsList[$key]['repayTime'] = $value['repay_time'];
                $dealsList[$key]['loanType'] = $value['loantype'];
                $dealsList[$key]['dealType'] = $value['deal_type'];
                $dealsList[$key]['rate'] = number_format($value['rate'],2);
            }
            $result['dealsList'] = $dealsList;

            $response->code = 0;
            $response->res = $result;
        }catch (\Exception $e){
            $response->code = -1;
            $response->resMessage = $e->getMessage();
        }
        return $response;
    }

}
