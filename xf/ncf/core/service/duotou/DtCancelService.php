<?php
/**
 * DtCancelService.php
 * 多投取消投资服务
 * @date 2018-01-07
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
namespace core\service\duotou;

use core\service\duotou\DtEntranceService;

class DtCancelService extends DuotouService{
    const ERROR_CANCEL_BEYOND_TIME      = -1; //不在取消时间
    const ERROR_CANCEL_INVOKE_FAIL      = -2; //调用取消服务失败
    const ERROR_CAN_NOT_CANCEL_TODAY    = -3; //今天不允许取消

    /**
     * 获取用户可取消的投资记录
     * @param unknown $userId 用户Id
     */
    public function getCanCancelDealLoans($userId) {
        $return = array(
            'errCode' => 0,
            'errMsg' => '',
            'data' => array()
        );

        $isInCancelTimeInterval = $this->isInCancelTimeInterval();
        if(!$isInCancelTimeInterval) {
            $return['errCode'] = self::ERROR_CANCEL_BEYOND_TIME;
            $return['errMsg'] = '不在可取消时间内';
            return $return;
        }

        $request = array(
            'userId' => $userId,
            'date' =>date("Y-m-d") ,
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoanCancel', 'getCanCancelDealLoans', $request));
        if(empty($response)){
            return $return;
        }

        $activityInfos = array();
        $res = array();
        $dtEntranceService = new DtEntranceService();
        foreach ($response['data'] as $value) {
            $text = '';
            if($value['status'] == 3){
                $text = '成功转让后显示到账日';
            }elseif($value['status'] == 4 || $value['status'] == 5) {
                $text = !empty($value['redeemFinishTime']) ? $value['redeemFinishTime'].'本金到账' : '';
            }

            $tempData = array(
                'projectName' => $value['projectInfo']['name'],
                'hasRepayInterest' => number_format($value['hasRepayInterest'], 2),
                'noRepayInterest' => number_format($value['norepayInterest'], 2),
                'money' => number_format($value['money'], 2),
                'repayStartTime' => !empty($value['repayStartTime']) ? $value['repayStartTime'] : '',
                'redeemFinishTime' => !empty($value['redeemFinishTime']) ? $value['redeemFinishTime'] : '',
                'status' => $value['status'] == 0 ? 1 : $value['status'],
                'dealLoanId' => $value['id'],
                'activityId' => $value['activityId'],
                'ownDay' => $value['loadDays'],
                'hasRepayInterestTag' => '已到账(元)',
                'noRepayInterestTag' => '未到账(元)',
                'text' => $text,
            );
            if($value['activityId'] > 0) { //参与了活动
                $activityId = $value['activityId'];
                if(isset($activityInfos[$activityId])) {
                    $tempData['activityInfo'] = $activityInfos[$activityId];
                } else {
                    $activityInfo = $dtEntranceService->getEntranceInfo($activityId);
                    $tempData['activityInfo'] = $activityInfos[$activityId] = $activityInfo;
                }
            }
            $res[] = array_merge($tempData,$value);

        }
        $return['data'] = $res;
        return $return;
    }

    /**
     * 取消投资记录投向底层资产
     * @param RequestCommon $request
     * @return array
     */
    public function cancelDealLoan($userId,$loanId) {
        $return = array(
            'errCode' => 0,
            'errMsg' => '',
            'data' => array()
        );

        $isInCancelTimeInterval = $this->isInCancelTimeInterval();
        if(!$isInCancelTimeInterval) {
            $return['errCode'] = self::ERROR_CANCEL_BEYOND_TIME;
            $return['errMsg'] = '已超过操作时间，您已无法取消。';
            return $return;
        }

        if(!$this->canCancelToday($userId)) {
            $return['errCode'] = self::ERROR_CAN_NOT_CANCEL_TODAY;
            $return['errMsg'] = '当前无可取消项目。';
            return $return;
        }

        $request = array(
            'userId' => $userId,
            'loanId' => $loanId,
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoanCancel', 'cancelDealLoan', $request));
        if(empty($response)|| !$response['data']){
            $return['errCode'] = self::ERROR_CANCEL_INVOKE_FAIL;
            $return['errMsg'] = '取消失败，请稍后重试！';
            return $return;
        }
        $return['data'] = $response['data'];
        return $return;
    }

    /**
     * 判断用户是否今天可以取消
     * @param $userId
     * @return mixed
     */
    public function canCancelToday($userId) {
        if(!$this->isInCancelTimeInterval()) {
            return false;
        }
        $request = array(
            'userId' => $userId,
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoanCancel', 'canCancelToday', $request));
        if(!empty($response) && $response['data'] == true){
            return true;
        }
        return false;
    }

    /**
     * 是不是在可取消投资区间
     * @throws \Exception
     */
    public function isInCancelTimeInterval() {
        $duotouPublishStartTime = app_conf('DUOTOU_CANCEL_START_TIME');
        $duotouPublishEndTime = app_conf('DUOTOU_CANCEL_END_TIME');

        if(empty($duotouPublishStartTime) || empty($duotouPublishEndTime)) {
            return false;
        }

        $nowTime = strtotime(date('H:i:s'));
        if(!empty($duotouPublishStartTime)) {//起始时间配置不存在
            $redemptionStartTime = strtotime($duotouPublishStartTime);
            if($nowTime < $redemptionStartTime) {
                return false;
            }
        }
        if(!empty($duotouPublishEndTime)) {//结束时间配置不存在
            $redemptionEndTime = strtotime($duotouPublishEndTime);
            if($nowTime > $redemptionEndTime) {
                return false;
            }
        }
        return true;
    }

}
