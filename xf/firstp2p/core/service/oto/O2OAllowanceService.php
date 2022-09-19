<?php

namespace core\service\oto;

use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\dao\OtoBonusAccountModel;
use core\dao\BonusModel;
use core\service\O2OService;
use core\service\BonusService;
use core\exception\O2OException;
use libs\utils\Monitor;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Common\Library\Date\XDateTime;
use core\dao\UserModel;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use core\service\WXBonusService;
use libs\sms\SmsServer;
use core\service\SparowService;

/**
 * o2o补贴相关服务
 */
class O2OAllowanceService {
    /**
     * 新的返利补贴格式处理
     */
    public function subsidy($payoutUser, $payinUser, $allowance, $logId, $coupon) {
        $allowanceMode = $allowance['mode'];
        // 如果money不为空，则可能存在转账请求，记入相关日志备查
        PaymentApi::log('O2OService.subsidy'.CouponGroupEnum::$ALLOWANCE_MODES[$allowanceMode]
            . ", 券{$coupon['id']}, {$coupon['couponNumber']}, payout: " . json_encode($payoutUser, JSON_UNESCAPED_UNICODE)
            . ' payin: '. json_encode($payinUser, JSON_UNESCAPED_UNICODE)
            . ', allowance：' . json_encode($allowance, JSON_UNESCAPED_UNICODE), Logger::INFO);

        // 没有收入方
        if (empty($payinUser)) {
            throw new O2OException('O2O返利失败，收入方不能为空', O2OException::CODE_P2P_ERROR);
//            return false;
        }

        $bonusMode = OtoBonusAccountModel::MODE_CONFIRM;
        if ($allowanceMode == CouponGroupEnum::ACQUIRE_WX_INVITER
            || $allowanceMode == CouponGroupEnum::ACQUIRE_WX_OWNER
            || $allowanceMode == CouponGroupEnum::ACQUIRE_OWNER_PAYOUT) {
            $bonusMode = OtoBonusAccountModel::MODE_ACQUIRE;
        }

        // 返红包
        if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
            if (empty($allowance['money'])) {
                return false;
            }

            if (empty($payoutUser)) {
                // 如果配置了金额，说明存在相关转账逻辑，如果补贴现金或者红包，保证转出账户存在
                throw new O2OException('O2O返利失败，转出账户不能为空', O2OException::CODE_P2P_ERROR);
            }

            // 补贴投资红包
            $bonusType = BonusModel::BONUS_O2O_CONFIRMED_REBATE;
            // 券组id
            $couponGroupId = app_conf('COUPON_GROUP_ID_REFERER_REBATE');
            // 红包返利判断
            if (!empty($couponGroupId) && $couponGroupId == $coupon['couponGroupId']) {
                $bonusType = BonusModel::BONUS_COUPON;
            } else {
                if ($allowanceMode == CouponGroupEnum::ACQUIRE_WX_INVITER) {
                    $bonusType = BonusModel::BONUS_O2O_ACQUIRE_FOR_INVITER;
                } else if ($allowanceMode == CouponGroupEnum::ACQUIRE_WX_OWNER) {
                    $bonusType = BonusModel::BONUS_O2O_ACQUIRE_FOR_USER;
                }
            }

            // 返回红包id
            $remark = isset($coupon['remark']) ? $coupon['remark'] : '';
            return $this->rebateBonus(
                $payinUser['id'],
                $payoutUser['id'],
                $allowance['money'],
                $allowance['dayLimit'] * 86400,
                $bonusType,
                $bonusMode,
                $logId,
                $coupon['couponGroupId'],
                $remark
            );
        }

        // 返现金
        if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
            if (empty($allowance['money'])) {
                return false;
            }

            if (empty($payoutUser)) {
                // 如果配置了金额，说明存在相关转账逻辑，如果补贴现金或者红包，保证转出账户存在
                throw new O2OException('O2O返利失败，转出账户不能为空', O2OException::CODE_P2P_ERROR);
            }

            // 补贴现金
            $productName = $coupon['productName'];
            // 增加冻结金额的考虑,计算总转账金额和冻结金额，先转账后冻结
            $totalAmount = 0;
            $freezeAmount = 0;
            $allowanceAmount = explode(',', $allowance['money']);
            if (count($allowanceAmount) == 2) {
                $totalAmount = $allowanceAmount[0] + $allowanceAmount[1];
                $freezeAmount = $allowanceAmount[1];
            }

            switch ($allowanceMode) {
                case CouponGroupEnum::EXCHANGE_WX_OWNER:
                case CouponGroupEnum::EXCHANGE_CHANNEL_STORE:
                case CouponGroupEnum::EXCHANGE_SUPPLIER_STORE:
                case CouponGroupEnum::EXCHANGE_WX_SUPPLIER:
                case CouponGroupEnum::EXCHANGE_WX_STORE:
                case CouponGroupEnum::EXCHANGE_WX_CHANNEL:
                    $receiveType = '兑券收入';
                    $receiveNote = "券码{$coupon['couponNumber']},{$productName}兑换券,向{$payinUser['user_name']}转账";
                    $fromType = '兑券支出';
                    $fromNote = "券码{$coupon['couponNumber']},{$productName}兑换券,由{$payoutUser['user_name']}转账";
                    $freeType='兑券冻结';
                    $freezeNote = "券码{$coupon['couponNumber']},{$productName}兑换券,冻结";
                    $outOrderId = 'EXCHANGE_COUPON';
                    break;
                case CouponGroupEnum::EXCHANGE_WX_INVITER:
                    $receiveType = '被邀请人兑券收入';
                    $receiveNote = $productName .'，由会员'. $payoutUser['user_name']. '转入';
                    $fromType = '兑券支出';
                    $fromNote = $productName .'，向会员'. $payinUser['user_name'] .'转出' ;
                    $freeType='兑券冻结';
                    $freezeNote = "券码{$coupon['couponNumber']},{$productName}兑券,冻结";
                    $outOrderId = 'EXCHANGE_INVITER';
                    break;
                case CouponGroupEnum::ACQUIRE_WX_INVITER:
                    $receiveType = '被邀请人领券收入';
                    $receiveNote = $productName .'，由会员'. $payoutUser['user_name']. '转入';
                    $fromType = '领券支出';
                    $fromNote = $productName .'，向会员'. $payinUser['user_name'] .'转出' ;
                    $freeType='领券冻结';
                    $freezeNote = "券码{$coupon['couponNumber']},{$productName}领券,冻结";
                    $outOrderId = 'ACQUIRE_INVITER';
                    break;
                case CouponGroupEnum::ACQUIRE_WX_OWNER:
                    $receiveType = '领券收入';
                    $receiveNote = $productName .'，由会员'. $payoutUser['user_name']. '转入';
                    $fromType = '领券支出';
                    $fromNote = $productName .'，向会员'. $payinUser['user_name'] .'转出' ;
                    $freeType='领券冻结';
                    $freezeNote = "券码{$coupon['couponNumber']},{$productName}领券,冻结";
                    $outOrderId = 'ACQUIRE_OWNER';
                    break;
                case CouponGroupEnum::ACQUIRE_OWNER_PAYOUT:
                    $receiveType = '领券收入';
                    $receiveNote = $productName .'，由会员'. $payoutUser['user_name']. '转入';
                    $fromType = '领券支出';
                    $fromNote = $productName .'，向会员'. $payinUser['user_name'] .'转出' ;
                    $freeType='领券冻结';
                    $freezeNote = "券码{$coupon['couponNumber']},{$productName}领券,冻结";
                    $outOrderId = 'ACQUIRE_OWNER_PAYOUT';
                    break;
                default:
                    throw new O2OException('O2O返利失败，类型非法', O2OException::CODE_P2P_ERROR);
            }

            $transferService = new \core\service\TransferService();
            $transferService->transferAndFreeze($totalAmount, $allowance['money'], $freezeAmount, $payoutUser['id'],
                $payinUser['id'], $fromType, $fromNote, $receiveType, $receiveNote, $freeType, $freezeNote, $outOrderId);

            // 这里转账暂时没有返回值，先直接返回0，这里建议后期返回转账的订单id
            return 0;
        }

        // 返分享红包
        if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_LUCKYMONEY) {
            if (empty($allowance['money'])) {
                return false;
            }

            if (empty($payoutUser)) {
                // 如果配置了金额，说明存在相关转账逻辑，如果补贴现金或者红包，保证转出账户存在
                throw new O2OException('O2O返利失败，转出账户不能为空', O2OException::CODE_P2P_ERROR);
            }

            // 补贴分享红包
            $bonusType = BonusService::TYPE_O2O_CONFIRM;
            if ($allowanceMode == CouponGroupEnum::ACQUIRE_WX_INVITER) {
                $bonusType = BonusService::TYPE_O2O_ACQUIRE_FOR_INVITER;
            } else if ($allowanceMode == CouponGroupEnum::ACQUIRE_WX_OWNER) {
                $bonusType = BonusService::TYPE_O2O_ACQUIRE_FOR_USER;
            }

            $bonusAccountInfo = array(
                'account_id' => $payoutUser['id'],
                'trigger_mode' => $bonusMode,
                'log_id' => $logId,
            );

            $bonusService = new BonusService();
            // 这里应该红包组id
            return $bonusService->generateO2OBonus(
                $payinUser['id'], $allowance['money'], $allowance['count'],
                $allowance['dayLimit'] * 86400, $bonusAccountInfo, $coupon['id'],
                $bonusType, $coupon['couponGroupId']);
        }

        // 返礼券
        if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_COUPON) {
            if (empty($allowance['couponGroupId'])) {
                return false;
            }

            // 补贴礼券
            $taskIds = $this->rebateTimerCoupons($payinUser['id'], $allowance['couponGroupId'], $allowance['hourLimit'], $allowance['times'],
                $coupon['id'], $allowanceMode);

            // 返回定时的任务id，多个用逗号进行分割
            return implode(',', array_values($taskIds));
        }

        // 返投资券
        if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT) {
            // 返投资券
            if (empty($allowance['discountId'])) {
                return false;
            }

            // 补贴投资券
            $dealLoadId = isset($coupon['dealLoadId']) ? $coupon['dealLoadId'] : 0;
            $token = 'allowance_'.$coupon['id'].'_'.$allowanceMode;
            $rebateAmount = empty($allowance['money']) ? 0 : $allowance['money'];
            $rebateLimit = empty($allowance['dayLimit']) ? 0 : $allowance['dayLimit'] * 86400;
            $discounts = $this->rebateDiscounts($payinUser['id'], $allowance['discountId'], $token, $dealLoadId,
                '礼券'.$coupon['id'].'返利获得', $rebateAmount, $rebateLimit);

            $discountIds = array();
            if ($discounts) {
                foreach ($discounts as $discount) {
                    $discountIds[] = $discount['id'];
                }
            }

            // 返回返利的投资券id，多个用逗号进行分割
            return implode(',', $discountIds);
        }

        if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_GAME_CENTER) {
            // 返游戏中心的机会
            if (empty($allowance['gameId'])) {
                return false;
            }
            $gameCode = $allowance['gameId'];
            $sparowService = new SparowService($gameCode);
            $gameIndex = isset($allowance['gameRuleId']) ? trim($allowance['gameRuleId']) : '';
            return $sparowService->gameO2O($payinUser['id'], $gameIndex, $coupon['id']);
        }
    }

    /**
     * 礼券返利，返投资券的情况（同步或异步执行，其中异步支持gearman重试）
     *
     * @param $userId int 用户id
     * @param $discountGroupIds string 投资券组id，多个用“,”进行分割
     * @param $token string 唯一token码，用来进行幂等请求
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $rebateAmount float 返利金额
     * @param $rebateLimit int 返利期限
     * @param $isSync bool 是否同步请求返投资券，默认是同步请求，因为一般返利已经走异步了
     * @return array 同步为投资券信息，异步为gearman的任务id列表
     */
    public function rebateDiscounts($userId, $discountGroupIds, $token, $dealLoadId = 0, $remark = '',
                                    $rebateAmount = 0, $rebateLimit = 0, $isSync = true) {
        // 参数验证
        if (empty($discountGroupIds) || empty($userId) || empty($token)) {
            PaymentApi::log("rebateDiscounts, empty params: " .implode('|', array($userId, $discountGroupIds, $token)), Logger::WARN);
            return false;
        }

        $groupIds = explode(',', $discountGroupIds);
        $groupSets = array();
        $taskObj = new GTaskService();
        $discountService = new \core\service\oto\O2ODiscountService();
        $res = array();
        foreach ($groupIds as $groupId) {
            if (empty($groupId)) {
                continue;
            }

            if (isset($groupSets[$groupId])) {
                $groupSets[$groupId] += 1;
            } else {
                $groupSets[$groupId] = 1;
            }

            $couponToken = $userId.'_'.$groupId.'_'.$token;
            if ($groupSets[$groupId] > 1) {
                $couponToken .= '_'.$groupSets[$groupId];
            }

            if ($isSync) {
                // 同步返投资券，接口已经通过token保证了幂等
                $res[] = $discountService->acquireDiscount($userId, $groupId, $couponToken, $dealLoadId, $remark,
                    $rebateAmount, $rebateLimit);
            } else {
                // 发送投资券
                $event = new \core\event\O2ORebateDiscountEvent($userId, $groupId, $couponToken, $dealLoadId,
                    $remark, $rebateAmount, $rebateLimit);

                $taskId = $taskObj->doBackground($event, 3);
                // 必须保证所有任务的插入成功，否则抛出异常，保证数据的一致性
                if (!$taskId) {
                    throw new O2OException('O2O礼券返利失败, 插入O2ORebateDiscountEvent任务失败', O2OException::CODE_P2P_ERROR);
                }
                $res[] = $taskId;
            }
        }

        return $res;
    }

    /**
     * 红包发放
     * 这里没有使用事务，因为这个函数的调用方用到了事务
     *
     * @param $userId int 用户id
     * @param $accountId int 红包出资方id
     * @param $money float 投资红包金额
     * @param $bonusLimit int 投资红包期限
     * @param $bonusType int 红包类型
     * @param $mode int 红包类别
     * @param $logId int 日志id
     * @param $taskId int 红包的任务id，用来区分红包的来源
     * @param $remark string 红包来源文案（备注）
     * @return int 投资红包id
     */
    public function rebateBonus($userId, $accountId, $money, $bonusLimit, $itemType, $mode, $logId, $itemId = 0, $remark = '') {
        $createTime = time();
        $expireTime = $createTime + $bonusLimit;
        $bonusService = new \core\service\bonus\RpcService();
        $token = $userId.'_'.$logId.'_'.$itemId;
        $res = $bonusService->acquireBonus(
            $userId,
            $money,
            $token,
            $itemId,
            $itemType,
            $createTime,
            $expireTime,
            $remark,
            $accountId
        );

        if (!$res) {
            throw new O2OException('O2O给邀请人返红包失败', O2OException::CODE_P2P_ERROR);
        }

        return $res;
    }

    /**
     * 返礼券，支持异步或同步执行
     * @param $userId int 用户id
     * @param $groupId string 礼券券组id，多个用“,”进行分隔
     * @param $token string 领取token唯一码
     * @param $dealLoadId int 交易id
     * @param $rebateAmount float 返利金额
     * @param $rebateLimit int 返利期限
     * @param $isSync bool 是否同步请求返礼券，默认是同步
     * @param $logId int acquireLogId记录id
     * @return array 领取成功的礼券id
     */
    public function rebateCoupons($userId, $groupId, $token, $dealLoadId = 0, $rebateAmount = 0, $rebateLimit = 0, $isSync = true, $logId = 0) {
        // 参数验证
        if (empty($groupId) || empty($userId) || empty($token)) {
            PaymentApi::log("rebateCoupons, empty params: " .implode('|', array($userId, $groupId, $token)), Logger::WARN);
            return false;
        }

        $res = array();
        $groupIds = explode(',', $groupId);
        $taskObj = new GTaskService();
        $o2oService = new O2OService();
        foreach ($groupIds as $id) {
            if (empty($id)) {
                continue;
            }

            $token = $token.'_'.$id;
            if ($isSync) {
                // 同步请求
                // 需要保证acquireAllowanceCoupon操作的幂等
                $res[] = $o2oService->acquireAllowanceCoupon(
                    $id,
                    $userId,
                    $token,
                    '',
                    $dealLoadId,
                    $rebateAmount,
                    $rebateLimit,
                    $logId
                );
            } else {
                // 异步请求
                $event = new \core\event\O2ORebateCouponEvent(
                    $userId,
                    $id,
                    $token,
                    $dealLoadId,
                    $rebateAmount,
                    $rebateLimit
                );
                $taskId = $taskObj->doBackground($event, 3);
                // 必须保证所有任务的插入成功，否则抛出异常，保证数据的一致性
                if (!$taskId) {
                    throw new O2OException('O2O礼券返利失败, 插入O2ORebateCouponEvent任务失败', O2OException::CODE_P2P_ERROR);
                }
                $res[] = $taskId;
            }
        }
        return $res;
    }

    /**
     * 补贴定时礼券（异步执行，gearman重试）
     *
     * @param $userId int 用户id
     * @param $groupId int 礼券券组id
     * @param $interval int 间隔时间，以小时为单位
     * @param $count int 发送次数
     * @param $couponId int 礼券来源id
     * @param $action int 触发动作
     * @return array gearman的任务id列表
     */
    public function rebateTimerCoupons($userId, $groupId, $interval, $count, $couponId, $action) {
        $res = array();
        // 这里的action先按100进行偏移，防止和现有的值冲突
        $executeTime = XDateTime::now();
        $taskObj = new GTaskService();
        for ($i=1; $i<=$count; $i++) {
            // 执行时间
            $executeTime = $executeTime->addHour($interval);
            // 定时发礼券任务
            $event = new \core\event\O2ORebateCouponEvent($userId, $groupId, $couponId.'_'.$i.'_'.$action);
            $taskId = $taskObj->doBackground($event, 3, Task::PRIORITY_NORMAL, $executeTime);
            // 必须保证所有任务的插入成功，否则抛出异常，保证数据的一致性
            if ($taskId === false) {
                throw new O2OException('O2O礼券返利失败, 插入O2ORebateCouponEvent任务失败', O2OException::CODE_P2P_ERROR);
            }
            $res[$i] = $taskId;
        }

        // 如果成功，则记录相应的日志记录
        $params = array('userId'=>$userId, 'groupId'=>$groupId, 'couponId'=>$couponId,
            'action'=>$action, 'task'=>$res, 'count'=>$count, 'interval'=>$interval);
        PaymentApi::log('O2OService.rebateTimerCoupons, params: '.json_encode($params), Logger::INFO);
        return $res;
    }

    /**
     * 返利的消息推送
     */
    public function pushMsg($pushUsers, $pushData, $isExchange, $pushUsersName, $couponId = 0) {
        if (empty($pushData) || empty($pushUsers)) {
            return;
        }

        // 补贴类型映射
        $allowanceModeMap = array(
            CouponGroupEnum::EXCHANGE_SUPPLIER_STORE => array('payin'=>'storeId', 'payout'=>'supplierUserId'), // 兑券后供应商补贴零售店
            CouponGroupEnum::EXCHANGE_CHANNEL_STORE => array('payin'=>'storeId', 'payout'=>'channelId'), // 兑券后渠道补贴零售店
            CouponGroupEnum::EXCHANGE_WX_OWNER => array('payin'=>'ownerUserId', 'payout'=>'wxUserId'), // 兑券后网信补贴兑换人
            CouponGroupEnum::EXCHANGE_WX_SUPPLIER => array('payin'=>'supplierUserId', 'payout'=>'wxUserId'), // 兑券后网信补贴供应商
            CouponGroupEnum::EXCHANGE_WX_CHANNEL => array('payin'=>'channelId', 'payout'=>'wxUserId'), // 兑券后网信补贴渠道
            CouponGroupEnum::EXCHANGE_WX_INVITER => array('payin'=>'referUserId', 'payout'=>'wxUserId'), // 兑券后网信补贴邀请人
            CouponGroupEnum::ACQUIRE_WX_INVITER => array('payin'=>'referUserId', 'payout'=>'wxUserId'), // 领券后网信补贴邀请人
            CouponGroupEnum::EXCHANGE_WX_STORE => array('payin'=>'storeId', 'payout'=>'wxUserId'), // 兑券后网信补贴零售店
            CouponGroupEnum::ACQUIRE_WX_OWNER => array('payin'=>'ownerUserId', 'payout'=>'wxUserId'), // 领券后网信补贴投资人
            CouponGroupEnum::ACQUIRE_OWNER_PAYOUT => array('payin'=>'', 'payout'=>'ownerUserId'), // 领券后投资人支出
        );

        $acquiredModes = array(
            CouponGroupEnum::ACQUIRE_WX_INVITER,
            CouponGroupEnum::ACQUIRE_WX_OWNER,
            CouponGroupEnum::ACQUIRE_OWNER_PAYOUT
        );

        $msgBoxService = new \core\service\MsgBoxService();
        foreach ($pushData as $item) {
            $mode = $item['mode'];
            if (!array_key_exists($mode, $allowanceModeMap)) {
                continue;
            };

            if (($isExchange && in_array($mode, $acquiredModes)) ||
                (!$isExchange && !in_array($mode, $acquiredModes))) {
                continue;
            }

            if ($item['type'] == CouponGroupEnum::PUSH_TYPE_PAYIN) {
                // 收入方
                $userKey = $allowanceModeMap[$mode]['payin'];
            } else if ($item['type'] == CouponGroupEnum::PUSH_TYPE_PAYOUT) {
                // 支出方
                $userKey = $allowanceModeMap[$mode]['payout'];
            }

            if (!empty($userKey) && !empty($pushUsers[$userKey])) {
                $userId = $pushUsers[$userKey];
                //增加投资人和邀请人的变量替换
                $searchName = array('{ownerName}', '{inviterName}');
                $replaceName = array($pushUsersName['ownerUserId'], $pushUsersName['referUserId']);
                $item['msgBoxBody'] = str_replace($searchName, $replaceName, $item['msgBoxBody']);
                $item['sms'] = str_replace($searchName, $replaceName, $item['sms']);
                if (!empty($item['msgBoxTitle']) && !empty($item['msgBoxBody'])) {
                    //增加礼券推送app消息的跳转类型
                    $turnUrl = '';
                    if (isset($item['msgBoxUrl']) && $item['msgBoxUrl']) {
                        $turnUrl = trim($item['msgBoxUrl']);
                    } else if ($couponId) {
                        $turnUrl = app_conf('API_HOST').'/gift/MineDetail?couponId='.$couponId;
                    }
                    $extraContent = [
                        'turn_type' => MsgBoxEnum::TURN_TYPE_URL,
                        'url' => $turnUrl,
                    ];

                    $msgBoxService->create($userId, MsgBoxEnum::TYPE_O2O_COUPON, $item['msgBoxTitle'], $item['msgBoxBody'], $extraContent);
                }

                if (!empty($item['sms'])) {
                    // 发送短信
                    $userInfo = UserModel::instance()->findViaSlave($userId, 'mobile');
                    SmsServer::instance()->send($userInfo['mobile'], 'TPL_SMS_O2O_COMMON', array($item['sms']), $userId);
                }

                PaymentApi::log("O2OService.pushMsg success, userId:".$pushUsers[$userKey].' mobile: '.$userInfo['mobile'].' data:'
                    .json_encode($item, JSON_UNESCAPED_UNICODE), Logger::INFO);
            }
        }
    }

    public function rebateGoldBonusLogCallback($param) {
        $wxBonusService = new WXBonusService();
        $expireTime = strtotime(date('Y-m-d 23:59:59'));
        $wxBonusService->goldAcquireAndConsumeLogHidden($param['userId'], $param['money'], $param['orderId'], time(), $expireTime, $param['rebateConf']['wxUserId'], '平台奖励', '买金抵扣');
        return true;
    }
}
