<?php

namespace core\service\candy;

use libs\db\Db;
use libs\utils\Logger;
use NCFGroup\Common\Library\Sms\Sms;
use core\dao\UserModel;
use core\service\AddressService;
use core\service\MsgBoxService;
use core\service\UserService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

/**
 * 信宝夺宝服务
 */
class CandySnatchService
{
    //期状态：正在热拍
    const PERIOD_STATUS_PROCESS = 1;
    //期状态：往期记录(未发货)
    const PERIOD_STATUS_PASSED = 2;
    //期状态：已发货
    const PERIOD_STATUS_HANDLE = 3;

    //商品状态：正常
    const PRODUCT_STATUS_PROCESS = 0;

    //商品状态：下线
    const PRODUCT_STATUS_FINISHED = 1;

    //上新开始时间
    const SNATCH_START_TIME = '00:00';
    //上新结束时间
    const SNATCH_END_TIME = '23:59';

    //夺宝码前缀
    const PREFIX_CODE = 10000000;

    //单个商品限制
    const SINGLE_PRODUCT_LIMIT = 0.6;

    //邀请专享商品类型
    const INVITE_PRODUCT_TYPE = 2;

    /**
     * 获得往期列表
     */
    public function getPastPeriodList($offset, $limit)
    {
        $periodList = Db::getInstance('candy')->getAll("SELECT * FROM snatch_period WHERE status>" . self::PERIOD_STATUS_PROCESS . " ORDER BY prize_time DESC LIMIT {$offset},{$limit}");
        if (empty($periodList)) {
            return [];
        }
        $periodList = $this->attachUserInfo($periodList);
        $periodList = $this->attachProductInfo($periodList);

        return $periodList;
    }

    /**
     * 获取首页正在热拍列表
     */
    public function getAuctionPeriodList()
    {
        $result = Db::getInstance('candy')->getAll('SELECT * FROM snatch_product WHERE status = ' . self::PRODUCT_STATUS_PROCESS);

        foreach ($result as $key => $value) {
            $period = Db::getInstance('candy')->getRow("SELECT * FROM snatch_period WHERE product_id='{$value['id']}' ORDER BY id DESC LIMIT 1");
            if (!empty($period)) {
                $result[$key]['lastPeriod'] = $this->parsePeriod($period, $value);
            }
        }

        usort($result, function ($a, $b) {
            return $this->calcPeriodSort($a['lastPeriod']['schedule'], $a['sort']) < $this->calcPeriodSort($b['lastPeriod']['schedule'], $b['sort']) ? 1 : -1;
        });

        return $result;
    }

    /**
     * 计算排序
     */
    private function calcPeriodSort($schedule, $sort)
    {
        if ($schedule == 100) {
            return -1;
        }
        return $schedule + $sort;
    }

    /**
     * 附加用户信息
     * @param $data 包含'user_id'的数组
     */
    public function attachUserInfo(array $data)
    {
        if (empty($data)) {
            return $data;
        }
        $userIds = array_column($data, 'user_id');
        $userIdString = implode($userIds, ',');

        $result = Db::getInstance('firstp2p')->getAll("SELECT id, real_name, sex, mobile FROM firstp2p_user WHERE id IN ({$userIdString})");
        $robotUser = CandyUtilService::getRobotUserInfo($userIds);
        $result = array_merge($result, $robotUser);

        $userInfo = array_column($result, NULL, 'id');

        foreach ($userInfo as $key => $value) {
            $userInfo[$key]['real_name'] = mb_substr($value['real_name'], 0, 1);
            $userInfo[$key]['real_mobile'] = $value['mobile'];
            $userInfo[$key]['mobile'] = moblieFormat($value['mobile']);
        }

        foreach ($data as $key => $value) {
            $data[$key]['userInfo'] = $userInfo[$value['user_id']];
        }

        return $data;
    }

    /**
     * 附加产品信息
     * @param $period 期信息
     */
    public function attachProductInfo(array $period)
    {
        if (empty($period)) {
            return $period;
        }
        $productId = array_column($period, 'product_id');
        $productIdString = implode($productId, ',');

        $result = Db::getInstance('candy')->getAll("SELECT * FROM snatch_product WHERE id IN (" . $productIdString . ")");
        $productList = array_column($result, NULL, 'id');

        foreach ($period as $key => $value) {
            $period[$key] = $this->parsePeriod($value, $productList[$value['product_id']]);
            $period[$key]['address'] = $this->getAddressInfo($value['user_id'], $value['address_id']);
            $period[$key]['productInfo'] = $productList[$value['product_id']];
        }

        return $period;
    }

    /**
     * 解析期信息和产品信息
     * @param $periodRow 期信息
     * @param $productRow 产品信息
     */
    public function parsePeriod(array $periodRow, array $productRow)
    {
        $periodRow['images'] = json_decode($productRow['images'], true);
        $periodRow['detailImage'] = json_decode($productRow['detail'], true);
        $periodRow['image_main'] = $periodRow['images'][0];
        $periodRow['schedule'] = intval($periodRow['code_used'] / $periodRow['code_total'] * 100);

        return $periodRow;
    }

    /**
     * 获得地址信息
     */
    public function getAddressInfo($userId, $addressId)
    {
        try{
            return (new AddressService())->getOne($userId, $addressId);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获得个人某一期夺宝码
     * @param $userId 用户ID
     * @param $periodId 期号
     */
    public function getUserPeriodCodes($userId, $periodId)
    {
        $result = Db::getInstance('candy')->getAll("SELECT code_detail FROM snatch_order WHERE user_id='{$userId}' AND period_id='{$periodId}'");
        foreach ($result as $key => $value) {
            $result[$key] = explode(',', $value['code_detail']);
        }
        return $result;
    }

    /**
     * 我的奖品
     * @param $userId 用户ID
     */
    public function getUserPrize($userId)
    {
        return Db::getInstance('candy')->getAll("SELECT * FROM snatch_period WHERE user_id='{$userId}' ORDER BY prize_time DESC LIMIT 100");
    }

    /**
     * 获得个人近期产品
     * @param $userId 用户ID
     */
    public function getUserRecentProducts($userId)
    {
        $endtime = strtotime('-30 days') * 1000;
        $result = Db::getInstance('candy')->getAll("SELECT DISTINCT period_id,code_detail FROM snatch_order WHERE user_id='{$userId}' AND create_time >= '{$endtime}'");
        if (empty($result)) {
            return [];
        }
        $periodIds = array_column($result, 'period_id');
        $period = $this->getPeriodInfo($periodIds);
        $codeDetail = array_column($result, NULL, 'period_id');
        foreach ($period as $key => $value) {
            $period[$key]['code_detail'] = $codeDetail[$value['id']]['code_detail'];
        }

        return $this->attachProductInfo($period);
    }

    /**
     * 获取用户邀请好友首投的夺宝奖励
     */
    public function getInviteAmount($userId)
    {
        $startTime = strtotime(date("Y-m-d"));
        $presentCount = Db::getInstance('candy')->getOne("SELECT sum(present_count) FROM snatch_invite WHERE user_id = '{$userId}' AND create_time >= '{$startTime}'");
        return empty($presentCount) ? 0 : $presentCount ;
    }

    /**
     * 获得当日可投信宝数最大值
     * @param $userId 用户ID
     */
    public function getUserCodeTotal($userId)
    {
        //获取年化投资额
        $userDealMoney = CandyUtilService::getUserInvestAmount($userId, CandyUtilService::LIMIT_DEAL_AMOUNT_ANNUALIZED, strtotime(date("Ymd")));
        return floor($userDealMoney / app_conf('CANDY_SNATCH_ANNUALIZED_AMOUNT_CODE_RATE')) + app_conf('CANDY_SNATCH_CODE_PRESENT') + $this->getInviteAmount($userId) + CandyUtilService::presentSnatchCodePerWeek();
    }

    /**
     * 获得用户已使用信宝数
     * @param $userId 用户ID
     */
    public function getUserCodeUsed($userId)
    {
        $startTime = strtotime(date('Ymd')) * 1000;
        return Db::getInstance('candy')->getOne("SELECT sum(code_count) FROM snatch_order WHERE user_id='{$userId}' AND create_time>='{$startTime}'");
    }

    /**
     * 获得用户当日可投信宝数
     * @param $userId 用户ID
     */
    public function getUserCodeAvailable($userId)
    {
        return $this->getUserCodeTotal($userId) - $this->getUserCodeUsed($userId);
    }

    /**
     * 获取用户可投邀请专享商品的有效机会
     * @param $userId
     */
    public function getAvailableInviteAmount($userId)
    {
        $db = Db::getInstance('candy');
        $startTime = strtotime(date('Ymd')) * 1000;
        $periodIds = $db->getAll("SELECT distinct period_id FROM snatch_order WHERE user_id = '{$userId}' AND create_time >= '{$startTime}'");
        $inviteUsedChance = 0;
        if(!empty($periodIds)){
            $periodIds = array_column($periodIds, 'period_id');
            $periodInfo = $this->getPeriodInfo($periodIds);
            $productInfo = $this->attachProductInfo($periodInfo);
            $invitePeriodIds = array();
            foreach ($productInfo as $item) {
                if($item['productInfo']['type'] == self::INVITE_PRODUCT_TYPE){
                    $invitePeriodIds[] = $item['id'];
                }
            }
            if(!empty($invitePeriodIds)){
                $inviteUsedChance = $db->getOne("SELECT sum(code_count) FROM snatch_order WHERE user_id = '{$userId}' AND create_time >= '{$startTime}' AND period_id IN (" . implode(',', $invitePeriodIds). ")");
            }
        }
        $inviteChance = $this->getInviteAmount($userId) - $inviteUsedChance;
        $availableChance = $this->getUserCodeAvailable($userId);
        return $inviteChance < $availableChance ? $inviteChance : $availableChance;
    }

    /**
     * 获得期信息
     */
    public function getPeriodInfo(array $ids)
    {
        return Db::getInstance('candy')->getAll("SELECT * FROM snatch_period WHERE id IN (" . implode(',', $ids) . ")");
    }

    /**
     * 获得某一期参与记录
     * @param $period 期号
     */
    public function getPeriodOrders($periodId, $offset, $limit)
    {
        $record = Db::getInstance('candy')->getAll("SELECT * FROM snatch_order WHERE period_id='{$periodId}' ORDER BY id DESC LIMIT {$offset},{$limit}");
        return $this->attachUserInfo($record);
    }

    /**
     * 获取近期获奖记录
     */
    public function getRecentPrizeInfo($limit)
    {
        $period = Db::getInstance('candy')->getAll("SELECT * FROM snatch_period WHERE status > " . self::PERIOD_STATUS_PROCESS . " ORDER BY id DESC LIMIT {$limit}");

        $period = $this->attachUserInfo($period);
        $period = $this->attachProductInfo($period);

        return $period;
    }

    /**
     * 创建订单
     */
    private function createOrder($periodId, $userId, $codeCount, $codeUsed)
    {
        $codes = array();
        for ($i = 1; $i <= $codeCount; $i++) {
            $codes[] = self::PREFIX_CODE + $codeUsed + $i;
        }

        $data = array(
            'period_id' => $periodId,
            'user_id' => $userId,
            'code_count' => $codeCount,
            'code_detail' => implode(',', $codes),
            'create_time' => intval(microtime(true) * 1000),
        );
        Db::getInstance('candy')->insert('snatch_order', $data);
        Logger::info("candySnatch create order. periodId:{$periodId}, userId:{$userId}");
        return $codes;
    }

    /**
     * 夺宝
     * @param $periodId 期号
     * @param $amount 信宝数量
     * @param $userId 用户ID
     */
    public function snatchAction($userId, $periodId, $amount)
    {
        $periodId = intval($periodId);
        $amount = intval($amount);
        $userId = intval($userId);

        $db = Db::getInstance('candy');

        $periodInfo = current($this->getPeriodInfo([$periodId]));

        if ($amount <= 0) {
            throw new \Exception('夺宝信宝数最小为1');
        }

        $periodUsed = $db->getOne("SELECT sum(code_count) FROM snatch_order WHERE user_id='{$userId}' AND period_id='{$periodId}'");
        if ($amount > $periodInfo['code_total'] * self::SINGLE_PRODUCT_LIMIT - $periodUsed) {
            throw new \Exception('每期最多投'.self::SINGLE_PRODUCT_LIMIT * 100 .'%');
        }

        $availableCode = 0;
        $type = $db->getOne("SELECT type FROM snatch_product WHERE id = '{$periodInfo['product_id']}'");

        if (CandyUtilService::isRobotUser($userId)) {
            $availableCode = 1000000;
        } elseif ($type == self::INVITE_PRODUCT_TYPE) {
            $availableCode = $this->getAvailableInviteAmount($userId);
        } else {
            $availableCode = $this->getUserCodeAvailable($userId);
        }

        if ($amount > $availableCode) {
            throw new \Exception('可投信宝数不足');
        }

        if (!CandyUtilService::isRobotUser($userId) && !CandyUtilService::hasLoan($userId)) {
            throw new \Exception('完成一次投资就可以参与夺宝啦！');
        }

        if ($amount > $periodInfo['code_total'] - $periodInfo['code_used']) {
            throw new \Exception('所投信宝数超出剩余可投数量');
        }

        $db->startTrans();
        try {
            //创建订单
            $codes = $this->createOrder($periodId, $userId, $amount, $periodInfo['code_used']);

            if (!CandyUtilService::isRobotUser($userId)) {
                //扣减信宝
                $candyAccountService = new CandyAccountService();
                $candyAccountService->changeAmount($userId, -$amount, "信宝夺宝", "期号：{$periodId}");
                Logger::info("candySnatch changeAmount. userId:{$userId}, amount:{$amount}");
            }

            //扣减库存
            $db->query("UPDATE snatch_period SET code_used = code_used + {$amount}, version_id = version_id + 1 WHERE id='{$periodId}' AND code_used+{$amount}<=code_total AND version_id='{$periodInfo['version_id']}'");
            Logger::info("candySnatch reduceStock. period:{$periodId}, amount:{$amount}");
            if ($db->affected_rows() < 1) {
                Logger::info("candySnatch reduceStock fail");
                throw new \Exception('系统繁忙，夺宝失败');
            }

            //开奖
            if ($periodInfo['code_total'] - $periodInfo['code_used'] - $amount == 0) {
                $this->openPrize($periodId);
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::info("candySnatch snatchAction fail:".$e->getMessage());
            throw $e;
        }

        return $codes;
    }

    /**
     * 开奖
     * @param $periodId 期号
     */
    public function openPrize($periodId)
    {
        $db = Db::getInstance('candy');
        $orders = $db->getAll("SELECT * FROM snatch_order WHERE period_id = '{$periodId}'");

        //计算开奖号码
        $prizeInfo = $this->calcPrizeCode($orders);
        $prizeCode = $prizeInfo['prizeCode'];
        $prizeTimeSum = $prizeInfo['timeSum'];
        //搜索中奖用户
        foreach ($orders as $value) {
            if (strpos($value['code_detail'], strval($prizeCode)) !== false) {
                $prizeUserId = $value['user_id'];
                break;
            }
        }

        //记录开奖结果
        $data = array(
            'prize_code' => $prizeCode,
            'user_id' => $prizeUserId,
            'prize_time' => time(),
            'status' => self::PERIOD_STATUS_PASSED,
            'prize_time_sum'=> $prizeTimeSum,
        );
        $db->update('snatch_period', $data, 'id =' . $periodId);
        $productId = $db->getOne("SELECT product_id FROM snatch_period WHERE id = '{$periodId}'");
        $productName = $db->getOne("SELECT short_title FROM snatch_product WHERE id = '{$productId}'");

        //发送开奖通知
        $userInfo = $this->attachUserInfo([['user_id' => $prizeUserId]]);
        $appSecret = $GLOBALS['sys_config']['SMS_SEND_CONFIG']['APP_SECRET'];
        Sms::send(APP_NAME, $appSecret, $userInfo[0]['userInfo']['real_mobile'], 'TPL_SMS_SNATCH', [$productName]);
        Logger::info("candySnatch openPrize. period:{$periodId}, mobile:".$userInfo['mobile']);
    }

    /**
     * 计算开奖号码
     */
    private function calcPrizeCode(array $orders)
    {
        $timeSum = 0;
        $codeTotal = 0;
        foreach ($orders as $value) {
            $codeTotal += $value['code_count'];
            $timeSum += date("His", $value['create_time'] / 1000) * 1000 + $value['create_time'] % 1000;
        }
        $prizeCode = $timeSum % $codeTotal + self::PREFIX_CODE + 1;

        return ['prizeCode' => $prizeCode, 'timeSum' => $timeSum];
    }

    /**
     * 获取开奖信息
     */
    public function getPrizeData($periodId)
    {
         return  Db::getInstance('candy')->getRow("SELECT code_total, prize_code, prize_time_sum FROM snatch_period WHERE id = '{$periodId}'");
    }

    /**
     * 上新
     */
    public function createPeriod()
    {
        if (time() < strtotime(self::SNATCH_START_TIME) || time() > strtotime(self::SNATCH_END_TIME)) {
            Logger::info("candySnatch time not within snatchTime");
            return false;
        }

        $startTime = strtotime(date('Ymd'));
        $db = Db::getInstance('candy');

        $productInfo = $db->getAll('SELECT * FROM snatch_product');
        foreach ($productInfo as $key => $value) {
            $process = $db->getRow("SELECT * FROM snatch_period WHERE product_id='{$value['id']}'  AND status=" . self::PERIOD_STATUS_PROCESS);
            if (!empty($process)) {
                Logger::info("candySnatch need not createPeriod. productId:" . $value['id']);
                continue;
            }

            $passed = $db->getOne("SELECT count(*) FROM snatch_period WHERE product_id='{$value['id']}' AND create_time>='{$startTime}'");
            if ($passed >= $value['stock']) {
                Logger::info("candySnatch has all createPeriod. productId:" . $value['id']);
                continue;
            }

            $data = array(
                'product_id' => $value['id'],
                'code_total' => $value['price'],
                'code_used' => 0,
                'create_time' => time(),
                'status' => self::PERIOD_STATUS_PROCESS,
            );

            $period = $db->insert("snatch_period", $data);
            if ($period) {
                Logger::info("candySnatch createPeriod success. productId:" . $value['id'] . ", periodId:" . $period);
            } else {
                Logger::info("candySnatch createPeriod fail. productId:" . $value['id']);
            }
        }
        return true;
    }

    /**
     * 保存收货地址
     */
    public function saveAddress($periodId, $addressId)
    {
        return Db::getInstance('candy')->update('snatch_period', ['address_id' => $addressId], "id = '{$periodId}'");
    }

    /**
     * 判断是否在拉新黑名单
     * @param $userId
     * @return bool
     */
    public function inInviteBlack($userId)
    {
        $blackGroups = app_conf('CANDY_SNATCH_INVITE_BLACK_GROUP');
        if (!empty($blackGroups)) {
            $blackGroups = explode(',', $blackGroups);
            $groupid = (new UserService())->getUser($userId, false, false, true)['group_id'];
            if (in_array($groupid, $blackGroups)) {
                Logger::info("candySnatchInviter inInviteBlack. userid:{$userId}, groupid:{$groupid}");
                return true;
            }
        }
        return false;
    }

    /**
     * 邀请首投增加夺宝机会
     * @param $token
     * @param $userId
     * @param $inviteeId
     * @param $sourceAmount
     */
    public function addSnatchChance($token, $userId, $inviteeId, $sourceAmount)
    {
        $presentCount = 0;
        if ($this->inInviteBlack($userId)) {
            Logger::info("candySnatch:{$userId} in the black group.");
            return $presentCount;
        }
        $firstInvestLimit = app_conf('CANDY_SNATCH_FIRST_INVEST_LIMIT');
        $invitePresent = app_conf('CANDY_SNATCH_INVITE_PRESENT');
        if (empty($firstInvestLimit) || empty($invitePresent) || ($sourceAmount < $firstInvestLimit)) {
            Logger::info("candySnatch addSnatchChance failed: firstInvestLimit:$firstInvestLimit, investPresent:$invitePresent, sourceAmount:$sourceAmount");
            return $presentCount;
        }
        $presentCount = $invitePresent;
        $data = array(
            'user_id' => $userId,
            'token' => $token,
            'present_count' => $presentCount,
            'create_time' => time()
        );
        try {
            $inviteInfo = Db::getInstance('candy')->getRow("SELECT * FROM snatch_invite WHERE token = '{$data['token']}'");
            if (!empty($inviteInfo)) {
                Logger::info("candySnatch addSnatchChance data has exist; token: $token has exist");
                return 0;
            }
            Db::getInstance('candy')->insert('snatch_invite', $data);
            Logger::info("candySnatch: $userId getSnatchChance $presentCount, token: $token");
            // 短信和推送
            $invitee = get_deal_username($inviteeId);
            $userInfo = UserModel::instance()->getMobileByIds($userId);
            $phone = $userInfo[0]['mobile'];
            $inviteeInfo = UserModel::instance()->getMobileByIds($inviteeId);
            $inviteePhone = moblieFormat($inviteeInfo[0]['mobile']);
            $smsData = array(
                $invitee,
                $inviteePhone,
                $presentCount
            );
            $appSecret = $GLOBALS['sys_config']['SMS_SEND_CONFIG']['APP_SECRET'];
            Sms::send(APP_NAME, $appSecret, $phone, 'TPL_SMS_INVITE_FIRST_INVEST', $smsData);
            $msgBoxService = new MsgBoxService();
            $content = '您的好友' . $invitee . '（' . $inviteePhone . '）投资助您获得网信APP-信宝夺宝-邀请专享夺宝机会' . $presentCount . '次，当日有效，快去参与吧。';
            $msgBoxService->create($userId, MsgBoxEnum::TYPE_NOTICE, '拉新首投赠夺宝机会', $content);
            return $presentCount;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'Duplicate') !== false) {
                Logger::info("candySnatch addSnatchChance data has exist: $message ; token: $token");
            } else {
                Logger::info("candySnatch addSnatchChance error: $message ; params: $token, $userId, $inviteeId, $sourceAmount");
            }
        }
        return 0;
    }
}
