<?php
namespace core\service;

/**
 * 全站用户动态
 * @author longbo
 */
use core\service\DealLoadService;
use core\service\UserReservationService;
use core\dao\UserModel;
use libs\utils\Logger;
use core\service\BwlistService;

class TrendsService extends BaseService
{
    const TRENDS_KEY = 'user_trends_notice';

    const TRENDS_COUNT = 30;
    const TRENDS_MONEY = 5000;
    const TRENDS_LIMIT_TIME = 86400;

    const TRENDS_SWITCH = 'APP_TRENDS_SWITCH';
    const TRENDS_BLACK = 'TRENDS_BLACK';

    private $trendsType = [
        'p2p' => ['text' => '%s %s 出借P2P %s元'],
        /*'zx' => ['text' => '%s %s 投资专享 %s元'],  */
        'sxy' => ['text' => '%s %s 预约随心约 %s元'],
        'zdx' => ['text' => '%s %s 加入智多新 %s元'],
        /*'gold' => ['text' => '%s %s 购买黄金 %s克'],*/
        ];

    private $uids = [];


    /**
     * 获取全部动态列表
     * @return array
     */
    public function getAllTrends()
    {
        $res = [];
        $switch = app_conf(self::TRENDS_SWITCH);
        if ($switch === '0') {
            return ['trends' => []];
        }
        foreach ($this->trendsType as $type => $val) {
            $func = $type . 'Trends';
            if (method_exists($this, $func)) {
                try {
                    $res[$type] = $this->$func();
                } catch (\Exception $e) {
                    Logger::error("get {$type} Failed:".$e->getMessage());
                }
            } else {
                Logger::error("Get Trends Function {$func} is not exist.");
            }
        }
        $this->uids = array_unique($this->uids);
        return ['trends' => $this->sliceData($res)];
    }

    private function sliceData($data)
    {
        if (!($data && $this->uids)) return [];
        $resData = [];
        //过滤掉黑名单中的用户
        $userNotInBlack = $this->getUserIdNotInBlack(self::TRENDS_BLACK);
        $userInfoArr = UserModel::instance()->getUserInfoByIDs($this->uids, "`id`, `user_type`, `sex`, `real_name`");
        $userIdInfo = [];
        foreach ($userInfoArr as $user) {
            $lastName = mb_substr($user['real_name'], 0, 1);
            $name = ($user['user_type'] == 1) ? 'XX公司' : ($user['sex'] == 1 ? $lastName.'先生' : $lastName.'女士');
            $userIdInfo[$user['id']] = $name;
        }
        foreach ($data as $type => $val) {
            if (!$val) continue;
            foreach ($val as $v) {
                if (!$v) continue;
                if (!in_array($v['user_id'], $userNotInBlack)) continue;
                $v['text'] = $v['create_time'].sprintf($this->trendsType[$type]['text'], date("H:i",$v['create_time']), $userIdInfo[$v['user_id']], $v['money']);
                $resData[] = $v['text'];
            }
        }
        sort($resData);
        foreach ($resData as $k => $v) {
            $resData[$k] = substr($v,10,strlen($v)-10);
        }
        return $resData;
    }

    private function p2pTrends()
    {
        $dealLoadService = new DealLoadService();
        $res = $dealLoadService->getNewLoads(0, self::TRENDS_MONEY, 6, self::TRENDS_LIMIT_TIME);
        $data = [];
        if ($res) {
            foreach ($res as $val) {
                $data[] = ['user_id' => $val['user_id'], 'money' => $val['money'], 'create_time' => $val['create_time'] + 8*3600];
                $this->uids[] = $val['user_id'];
            }
        }
        return $data;
    }

    private function zxTrends()
    {
        $dealLoadService = new DealLoadService();
        $res = $dealLoadService->getNewLoads(3, self::TRENDS_MONEY, 6, self::TRENDS_LIMIT_TIME);
        $data = [];
        if ($res) {
            foreach ($res as $val) {
                $data[] = ['user_id' => $val['user_id'], 'money' => $val['money'], 'create_time' => $val['create_time'] + 8*3600];
                $this->uids[] = $val['user_id'];
            }
        }
        return $data;
    }

    private function sxyTrends()
    {
        $reservationService = new UserReservationService();
        $res = $reservationService->getNewReserve(self::TRENDS_MONEY, 6, self::TRENDS_LIMIT_TIME);
        $data = [];
        if ($res) {
            foreach ($res as $val) {
                $data[] = ['user_id' => $val['user_id'], 'money' => $val['reserve_amount']/100, 'create_time' => $val['start_time']];
                $this->uids[] = $val['user_id'];
            }
        }
        return $data;
    }

    private function zdxTrends()
    {
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $vars = array(
                'rowLimit' => 6,
                'minMoney' => self::TRENDS_MONEY,
                'latestSeconds' => self::TRENDS_LIMIT_TIME,
                );
        $request->setVars($vars);
        $response = $rpc->go('\NCFGroup\Duotou\Services\DealLoan', "getLatestLoans", $request);
        $data = [];
        if(isset($response['data'])) {
            foreach ($response['data'] as $val) {
                $data[] = ['user_id' => $val['userId'], 'money' => $val['money'], 'create_time' => $val['createTime']];
                $this->uids[] = $val['userId'];
            }
        }
        return $data;
    }

    private function goldTrends()
    {
        $gold = new \core\service\GoldService();
        $retGoldCurr = $gold->getAllDealLoadListByLimit(3, 0, self::TRENDS_LIMIT_TIME);
        $retGold = $gold->getAllDealLoadListByLimit(3, 1, self::TRENDS_LIMIT_TIME);
        $data = [];
        if ($goldData = array_merge($retGoldCurr, $retGold)) {
            foreach ($goldData as $val) {
                $data[] = ['user_id' => $val['userId'], 'money' => floatval($val['buyAmount']), 'create_time' => $val['create_time']];
                $this->uids[] = $val['userId'];
            }
        }
        return $data;
    }

    /**
     * 过滤掉黑名单中的用户
     * @param $typeKey
     *
     * @return array
     */
    private function getUserIdNotInBlack($typeKey)
    {
        $userNotInBlack = [];
        foreach ($this->uids as $uid) {
            if ((!BwlistService::inList($typeKey, $uid))) {
                $userNotInBlack[] = $uid;
            }
        }
        return $userNotInBlack;
    }
}


