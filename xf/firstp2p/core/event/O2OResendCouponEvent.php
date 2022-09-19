<?php
/**
 *-------------------------------------------------------
 * O2O异步补发
 *-------------------------------------------------------
 * 2016-09-06
 *-------------------------------------------------------
 */

namespace core\event;

use libs\utils\PaymentApi;
use core\event\BaseEvent;
use core\service\O2OService;
use core\dao\OtoCouponResendModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\UserService;

/**
 * O2OResendCouponEvent
 * O2O补发任务
 *
 */
class O2OResendCouponEvent extends BaseEvent
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
        $resendTask = OtoCouponResendModel::instance()->getTask($this->resendTaskId);
        $o2oService = new O2OService();
        $userService = new UserService();
        $triggerMode = CouponGroupEnum::TRIGGER_RESEND_COUPON;
        $failList = array();
        try {
            if ($resendTask['type'] == OtoCouponResendModel::TYPE_USERID) {
                //直接发送用户id的，循环发送
                $userIds = explode(',', $resendTask['user_id_list']);
                if ($userIds) {
                    foreach($userIds as $userId) {
                        try{
                            $user = $userService->getUser($userId);
                            if (empty($user)) {
                                $failList[] = $userId;
                                continue;
                            }
                            if (!isset($userSendList[$userId])) {
                                $userSendList[$userId] = 0;
                            }
                            $dealLoadId = $resendTask['id'] * 100 + $userSendList[$userId];
                            PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}| userId|".$userId."|dealLoadId|".$dealLoadId);
                            $res = $o2oService->resend($resendTask['coupon_group_id'], $userId, $triggerMode, $dealLoadId, $resendTask['verify_time']);
                            if(!$res) {
                                $failList[] = $userId;
                            }
                            //发送完后，更新已发送用户列表的发送次数
                            $userSendList[$userId] = $userSendList[$userId] + 1;
                        } catch(\Exception $e) {
                            $failList[] = $userId;
                            PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}| error userId:$userId|".$e->getMessage());
                        }
                    }
                }
            } else {
                //导入csv的处理
                $static_host = app_conf('STATIC_HOST');
                $taskUrl = $resendTask['user_id_list'];
                $result = array();
                if (($handle = fopen($taskUrl, "r")) !== false ) {
                    while(($data = fgetcsv($handle)) !== false) {
                        $result[] = $data;
                    }
                }
                if (count($result) > 1) {
                    PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}|result|".json_encode($result,JSON_UNESCAPED_UNICODE));
                    array_shift($result);
                    foreach($result as $tmp) {
                        $item = $tmp[0];
                        if ($resendTask['import_type'] == 1) {
                            $mobileRule = '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#';
                            if (preg_match($mobileRule, $item)) {
                                $failList[] = $item;
                                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}| wrong userId|".$item);
                                continue;
                            }
                            try{
                                $user = $userService->getUser($item);
                                if (empty($user)) {
                                    $failList[] = $item;
                                    continue;
                                }
                                if (!isset($userSendList[$item])) {
                                    $userSendList[$item] = 0;
                                }
                                $dealLoadId = $resendTask['id'] * 100 + $userSendList[$item];
                                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}| right userId|".$item."|dealLoadId|".$dealLoadId);
                                $res = $o2oService->resend($resendTask['coupon_group_id'], $item, $triggerMode, $dealLoadId, $resendTask['verify_time']);
                                if(!$res) {
                                    $failList[] = $item;
                                }
                                //发送完后，更新已发送用户列表的发送次数
                                $userSendList[$item] = $userSendList[$item] + 1;
                            } catch(\Exception $e) {
                                $failList[] = $item;
                                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}| error userId:$item|".$e->getMessage());
                            }
                        } else {
                            $mobileRule = '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#';
                            if (!preg_match($mobileRule, $item)) {
                                $failList[] = $item;
                                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}| wrong mobile|".$item);
                                continue;
                            }
                            $userId = $userService->getUserIdByMobile($item);
                            if ($userId) {
                                if (!isset($userSendList[$userId])) {
                                    $userSendList[$userId] = 0;
                                }
                                $dealLoadId = $resendTask['id'] * 100 + $userSendList[$userId];
                                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}|right mobile |$item|userId|".$userId."|dealLoadId|".$dealLoadId);
                                try{
                                    $res = $o2oService->resend($resendTask['coupon_group_id'], $userId, $triggerMode, $dealLoadId, $resendTask['verify_time']);
                                    if(!$res) {
                                        $failList[] = $userId;
                                    }
                                    //发送完后，更新已发送用户列表的发送次数
                                    $userSendList[$userId] = $userSendList[$userId] + 1;
                                } catch(\Exception $e) {
                                    $failList[] = $item;
                                    PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}|error userId:$userId|".$e->getMessage());
                                }
                            } else {
                                $failList[] = $item;
                                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}|error mobile:$item");
                            }
                        }
                    }
                }
            }

            if($failList) {
                $taskData = array('id' => $this->resendTaskId, 'fail_list' => implode(",",$failList));
                $resendTask = OtoCouponResendModel::instance()->updateTask($taskData);
                PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}|error ids:".json_encode($failList));
            }
        } catch(\Exception $e) {
            PaymentApi::log("O2OResendCouponEvent resendTaskId:{$this->resendTaskId}|failed, ".$e->getMessage());
            throw $e;
        }
        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com');
    }
}
