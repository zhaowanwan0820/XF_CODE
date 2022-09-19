<?php
/**
 *-------------------------------------------------------
 * VIP补发经验值
 *-------------------------------------------------------
 *-------------------------------------------------------
 */

namespace core\event;

use libs\utils\PaymentApi;
use core\event\BaseEvent;
use core\service\vip\VipService;
use core\dao\vip\VipPointResendModel;
use core\service\UserService;
use core\dao\UserModel;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

/**
 * VipPointResendEvent
 *
 */
class VipPointResendEvent extends BaseEvent
{
    public $resendTaskId;

    public function __construct($resendTaskId) {
        $this->resendTaskId = $resendTaskId;
    }


    /**
     * 执行补发
     */
    public function execute() {
        //支持一个任务里面给同一用户发多次券，按出现次数封装：taskId*100+index
        //用一个userId=>count数组暂存列表中每个用户发送的次数
        $userSendList = array();
        $resendTask = VipPointResendModel::instance()->getTask($this->resendTaskId);
        PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}|point|".$resendTask['point']);
        $vipService = new VipService();
        $userService = new UserService();
        $failList = array();
        try {
            if ($resendTask['type'] == VipPointResendModel::TYPE_USERID) {
                //直接发送用户id的，循环发送
                $userIds = explode(',', $resendTask['send_condition']);
                $point = $resendTask['point'];
                if (!$point) {
                    PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}|success:point=0");
                    return true;
                }
                if ($userIds) {
                    foreach($userIds as $userId) {
                        try{
                            $user = $userService->getUser($userId);
                            if (empty($user)) {
                                $failList[] = $userId;
                                continue;
                            }
                            $token = VipEnum::VIP_SOURCE_ADMIN.'_'. $this->resendTaskId.'_'.$userId;
                            $info = '后台补发.'.$this->resendTaskId;
                            PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}| userId|".$userId."|token|".$token);
                            $res = $vipService->updateVipPoint($userId, $point, VipEnum::VIP_SOURCE_ADMIN, $token, $info);
                            if(!$res) {
                                $failList[] = $userId;
                            }
                        } catch(\Exception $e) {
                            $failList[] = $userId;
                            PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}| error userId:$userId|".$e->getMessage());
                        }
                    }
                }
            } else {
                //用户组ID的处理
                $group = $resendTask['send_condition'];
                $userList = UserModel::instance()->getUserListByJob($group);
                $point = $resendTask['point'];
                if ($userList) {
                    foreach($userList as $item) {
                        try{
                            $userId = $item['id'];
                            $token = VipEnum::VIP_SOURCE_ADMIN.'_'. $this->resendTaskId.'_'.$userId;
                            $info = '后台补发.'.$this->resendTaskId;
                            PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}| userId|".$userId."|token|".$token);
                            $res = $vipService->updateVipPoint($userId, $point, VipEnum::VIP_SOURCE_ADMIN, $token, $info);
                            if(!$res) {
                                $failList[] = $userId;
                            }
                        } catch(\Exception $e) {
                            $failList[] = $userId;
                            PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}| error userId:$userId|".$e->getMessage());
                        }
                    }
                }
            }

            if($failList) {
                $taskData = array('id' => $this->resendTaskId, 'fail_list' => implode(",",$failList));
                $resendTask = VipPointResendModel::instance()->updateTask($taskData);
                PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}|error ids:".json_encode($failList));
            }
            $taskData = array('id' => $this->resendTaskId, 'send_status' => VipPointResendModel::SEND_STATUS_DONE);
            $resendTask = VipPointResendModel::instance()->updateTask($taskData);
        } catch(\Exception $e) {
            PaymentApi::log("VipPointResendEvent resendTaskId:{$this->resendTaskId}|failed, ".$e->getMessage());
            throw $e;
        }
        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com');
    }


}
