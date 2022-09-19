<?php
/**
 * DtRedeemService.php
 * 多投宝赎回服务
 * @date 2016-02-17
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
namespace core\service\duotou;

use core\service\duotou\DtBidService;
use core\service\user\UserService;
use core\service\duotou\DtMessageService;

class DtRedeemService extends DuotouService{
    /**
     * 赎回
     * @param unknown $id 投资记录id
     * @param unknown $user_id 用户id
     * @return multitype:number string
     */
    public function redeem($id,$user_id) {
        $request = array('id' => $id);
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoanById',$request));
        if(!$response) {
            return array(
                'errCode' => DtBidService::NET_ERROR,
                'errMsg' => "网络错误",
                'data' => ""
            );
        }
        if($response['errCode']) {
            return array(
                'errCode' => DtBidService::UNKNOW_ERROR,
                'errMsg' => "标的信息不存在",
                'data' => ""
            );
        }

        $request = array(
                'id' => $id,
                'userId' => $user_id,
                'isEnterprise' => UserService::isEnterprise($user_id),
        );

        $res = self::callByObject(array('NCFGroup\Duotou\Services\RedemptionApply','apply',$request));

        if ($res['errCode'] == 0) {

            //minLoanMoney 根据企业和个人
            if($request['isEnterprise']){
                $minLoanMoney = $response['data']['projectInfo']['singleEnterpriseMinLoanMoney'];
            }else{
                $minLoanMoney = $response['data']['projectInfo']['singleMinLoanMoney'];
            }
            //赎回申请发送邮件和短信等通知 智多鑫网贷拆分
            DtMessageService::sendMessage(DtMessageService::TYPE_REDEMPTION_APPLY, array(
            'id' => $response['data']['projectId'],
            'userId' => $user_id,
            'name' => $response['data']['projectInfo']['name'],
            'expiryInterest' => $response['data']['projectInfo']['expiryInterest'],
            'minLoanMoney' => $minLoanMoney,
            'siteId' => intval($response['data']['siteId']) ? intval($response['data']['siteId']) : 1,//站点信息
            'money' => $response['data']['money'],
            ));
        }
        $res['maxDayRedemption'] = $request['isEnterprise'] ? $response['data']['projectInfo']['enterpriseMaxDayRedemption'] : $response['data']['projectInfo']['maxDayRedemption'];
        $res['name'] = $response['data']['projectInfo']['name'];
        $res['expiryInterest'] = $response['data']['projectInfo']['expiryInterest'];
        return $res;
    }
}
