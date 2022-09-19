<?php

namespace core\service;

use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Aes;
use core\dao\OtoAllowanceLogModel;
use core\dao\UserModel;
use core\service\oto\O2ORpcService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Protos\O2O\RequestGetPagableData;
use NCFGroup\Common\Extensions\Base\Pageable;
use core\service\UserService;
use core\event\O2ORetryEvent;
use core\service\oto\O2OUtils;
use core\service\O2OService;
use core\dao\DealModel;

/**
 * 游戏活动平台服务
 */
class GameService extends O2ORpcService {
    const SIGN_KEY = 'dVlhTXBEbWNNUnE4faj2offjdcUJOSnAyYnY';

    /**
     * The guard divider.
     *
     * @var float
     */
    const GUARD_DIV = 12;
    /**
     * The alphabet string.
     *
     * @var string
     */
    protected $alphabet = 'ghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    /**
     * The guards string.
     *
     * @var string
     */
    protected $guards = 'abcdef';
    /**
     * The minimum hash length.
     *
     * @var int
     */
    protected $minHashLength = 6;
    /**
     * The salt string.
     *
     * @var string
     */
    protected $salt = 'dfa3dfx';

    /**
     * Shuffle alphabet by given salt.
     *
     * @param string $alphabet
     * @param string $salt
     * @return string
     */
    public function shuffle($alphabet, $salt) {
        $saltLength = strlen($salt);
        if (!$saltLength) {
            return $alphabet;
        }
        for ($i = strlen($alphabet) - 1, $v = 0, $p = 0; $i > 0; $i--, $v++) {
            $v %= $saltLength;
            $p += $int = ord($salt[$v]);
            $j = ($int + $v + $p) % $i;
            $temp = $alphabet[$j];
            $alphabet[$j] = $alphabet[$i];
            $alphabet[$i] = $temp;
        }
        return $alphabet;
    }

    /**
     * Hash given input value.
     *
     * @param int $input
     * @param string $alphabet
     * @return string
     */
    public function hash($input, $alphabet) {
        $hash = '';
        $alphabetLength = strlen($alphabet);
        do {
            $hash = $alphabet[intval(bcmod($input, $alphabetLength))].$hash;
            $input = bcdiv($input, $alphabetLength, 0);
        } while (bccomp($input, 0, 0) > 0);
        return $hash;
    }

    /**
     * Unhash given input value.
     *
     * @param string $input
     * @param string $alphabet
     * @return int
     */
    public function unhash($input, $alphabet) {
        $number = 0;
        $inputLength = strlen($input);
        if ($inputLength && $alphabet) {
            $alphabetLength = strlen($alphabet);
            $inputChars = str_split($input);
            foreach ($inputChars as $char) {
                $position = strpos($alphabet, $char);
                $number = bcmul($number, $alphabetLength, 0);
                $number = bcadd($number, $position, 0);
            }
        }
        return $number;
    }

    /**
     * Encode parameters to generate a hash.
     * @param $number int 需要加密的整数
     * @return string 加密后的字符串
     */
    public function encode($number) {
        $ret = '';
        if (!$number || !is_numeric($number)) {
            return $ret;
        }

        $alphabet = $this->alphabet;
        $numbersHashInt = intval(bcmod($number, 100));
        $lottery = $ret = $alphabet[$numbersHashInt % strlen($alphabet)];
        $alphabet = $this->shuffle($alphabet, substr($lottery.$this->salt.$alphabet, 0, strlen($alphabet)));
        $ret .= $this->hash($number, $alphabet);

        if (strlen($ret) < $this->minHashLength) {
            $guardIndex = ($numbersHashInt + ord($ret[0])) % strlen($this->guards);
            $guard = $this->guards[$guardIndex];
            $ret = $guard.$ret;
            if (strlen($ret) < $this->minHashLength) {
                $guardIndex = ($numbersHashInt + ord($ret[2])) % strlen($this->guards);
                $guard = $this->guards[$guardIndex];
                $ret .= $guard;
            }
        }

        $halfLength = (int) (strlen($alphabet) / 2);
        while (strlen($ret) < $this->minHashLength) {
            $alphabet = $this->shuffle($alphabet, $alphabet);
            $ret = substr($alphabet, $halfLength).$ret.substr($alphabet, 0, $halfLength);
            $excess = strlen($ret) - $this->minHashLength;
            if ($excess > 0) {
                $ret = substr($ret, $excess / 2, $this->minHashLength);
            }
        }

        return $ret;
    }
    /**
     * Decode a hash to the original parameter values.
     *
     * @param string $hash
     * @return array
     */
    public function decode($hash) {
        $ret = '';
        if (!is_string($hash) || !($hash = trim($hash))) {
            return false;
        }

        $alphabet = $this->alphabet;
        $hashBreakdown = str_replace(str_split($this->guards), ' ', $hash);
        $hashArray = explode(' ', $hashBreakdown);
        $i = count($hashArray) == 3 || count($hashArray) == 2 ? 1 : 0;
        $hashBreakdown = $hashArray[$i];
        if (isset($hashBreakdown[0])) {
            $lottery = $hashBreakdown[0];
            $hashBreakdown = substr($hashBreakdown, 1);
            $alphabet = $this->shuffle($alphabet, substr($lottery.$this->salt.$alphabet, 0, strlen($alphabet)));
            $result = $this->unhash($hashBreakdown, $alphabet);
            $ret = intval($result);

            if ($this->encode($ret) != $hash) {
                return false;
            }
        }

        return $ret;
    }

    /**
     * 加密eventId
     * @param $eventId int 活动id
     * @return string 加密之后的串
     */
    public function encodeEventId($eventId) {
        return $this->encode($eventId);
    }

    /**
     * 解密eventId
     * @param $eventIdStr string encode串
     * @return int
     */
    public function decodeEventId($eventIdStr) {
        return $this->decode($eventIdStr);
    }

    /**
     * 获取参数加密字符串.
     *
     * @param array $data
     * @access public
     *
     * @return string
     */
    public function getSignature(array $data) {
        return Aes::signature($data, self::SIGN_KEY);
    }

    /**
     * 生产随机串
     * @param $length int 串的长度
     * @return string
     */
    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * 获取活动详情
     * @param $userId int 用户id
     * @param $eventId string encode的活动id
     * @param $isEncode bool $eventId是否被encode了，默认值为true
     * @return array
     */
    public function getEventDetail($userId, $eventId, $isEncode = true) {
        try {
            $params = array('userId'=>$userId, 'eventId'=>$eventId);
            if (empty($userId) || !is_numeric($userId)) {
                throw new \Exception('用户id参数不正确');
            }

            if ($isEncode) {
                $eventId = $this->decodeEventId($eventId);
                if ($eventId === false) {
                    throw new \Exception('非法活动');
                }

                $params['decodeEventId'] = $eventId;
            }

            if (empty($eventId) || !is_numeric($eventId)) {
                throw new \Exception('活动id参数不正确');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array('eventId'=>$eventId));
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameEvent', 'getEventDetail', $request);
            $event = $response['data'];
            // 处理prizeSettings参数
            $event['prizeSettings'] = $event['prizeSettings'] ? json_encode($event['prizeSettings'], JSON_UNESCAPED_UNICODE) : '[]';

            $timestamp = time();
            // 处理活动状态
            if ($event['startTime'] > $timestamp) {
                $event['status'] = GameEnum::EVENT_STATUS_DO;
            } else if ($event['endTime'] < $timestamp) {
                $event['status'] = GameEnum::EVENT_STATUS_DONE;
            } else {
                $event['status'] = GameEnum::EVENT_STATUS_DOGING;
            }

            $nonStr = $this->generateRandomString(6);
            $signParams = array(
                'userId' => $userId,
                'eventId' => $eventId,
                'timestamp' => $timestamp,
                'nonStr' => $nonStr
            );
            $sign = $this->getSignature($signParams);

            // 时间戳
            $event['timestamp'] = $timestamp;
            // 随机串
            $event['nonStr'] = $nonStr;
            // 签名
            $event['sign'] = $sign;
            // 游戏模板
            $templateId = $event['templateId'];
            $event['gameTemplate'] = isset(GameEnum::$TEMPLATES_FILES[$templateId]) ? GameEnum::$TEMPLATES_FILES[$templateId] : 'roulette';

            // 获取用户剩余的游戏次数
            $request->setParamArray(array('userId'=>$userId, 'eventId'=>$eventId));
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameUser', 'getUserLeftTimes', $request);
            $event['userLeftTimes'] = $response['data'];
            // 游戏是否可玩
            $event['isDisable'] = 0;
            if ($event['status'] != GameEnum::EVENT_STATUS_DOGING || $event['userLeftTimes'] < 1) {
                $event['isDisable'] = 1;
            }

            return $event;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 获取用户剩余的游戏次数
     * @param $userId int 用户id
     * @param $eventId int 活动id
     * @return int|false
     */
    public function getUserLeftTimes($userId, $eventId) {
        try {
            $params = array('userId'=>$userId, 'eventId'=>$eventId);
            if (empty($userId) || !is_numeric($userId)) {
                throw new \Exception('用户id参数不正确');
            }

            if (empty($eventId) || !is_numeric($eventId)) {
                throw new \Exception('活动id参数不正确');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameUser', 'getUserLeftTimes', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 用户抽奖
     * @param $userId int 用户id
     * @param $eventId int 活动id
     * @param $timestamp int 时间戳
     * @param $nonStr string 随机串
     * @param $token string 唯一token
     * @return array
     */
    public function lottery($userId, $eventId, $timestamp, $nonStr, $token) {
        try {
            $params = array(
                'userId'=>$userId,
                'eventId'=>$eventId,
                'timestamp' => $timestamp,
                'nonStr' => $nonStr,
                'token'=>$token
            );

            if (empty($userId) || !is_numeric($userId)) {
                throw new \Exception('用户id参数不正确');
            }

            if (empty($eventId) || !is_numeric($eventId)) {
                throw new \Exception('活动id参数不正确');
            }

            if (empty($timestamp) || !is_numeric($timestamp)) {
                throw new \Exception('时间戳参数不正确');
            }

            if (empty($nonStr)) {
                throw new \Exception('nonStr不能为空');
            }

            if (empty($token)) {
                throw new \Exception('token不能为空');
            }

            // 验证签名值的正确性
            $signParams = array(
                'userId' => $userId,
                'eventId' => $eventId,
                'timestamp' => $timestamp,
                'nonStr' => $nonStr
            );
            $sign = $this->getSignature($signParams);
            if ($sign != $token) {
                \libs\utils\Monitor::add('GAME_SIGN_FAILD');
                throw new \Exception('签名错误');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameUser', 'lottery', $request);

            // 中的奖品
            $lotteryPrize = $response['data'];
            // 处理奖品的返利，幂等
            if (!empty($lotteryPrize['allowanceGroupId'])) {
                // 先生成触发返利记录，返利gearman可能执行失败，不太方便追溯问题，这里必须记录taskId
                $event = new \core\event\O2ORebateGameEvent($userId, $eventId, $token, $lotteryPrize);
                $taskObj = new GTaskService();
                $taskId = $taskObj->doBackground($event, 10);
                PaymentApi::log("O2OService.O2ORebateGameEvent, userId: ".$userId.', eventId: '.$eventId
                    .', token: '.$token.', lotteryPrize: ' .json_encode($lotteryPrize, JSON_UNESCAPED_UNICODE)
                    .', taskId:'.$taskId, Logger::INFO);
            }

            // 返回奖品数据
            $prize = array();
            $prize['prizeId'] = $lotteryPrize['id'];
            $prize['prizeName'] = $lotteryPrize['prizeName'];
            $prize['isRepeat'] = $lotteryPrize['isRepeat'];
            $prize['prizePic'] = $lotteryPrize['prizePic'];
            $prize['allowanceGroupId'] = $lotteryPrize['allowanceGroupId'];
            $prize['userLeftTimes'] = $lotteryPrize['userLeftTimes'];

            $newTimestamp = $timestamp + 5;
            $prize['timestamp'] = $newTimestamp;

            $newNonStr = $this->generateRandomString(6);
            $prize['nonStr'] = $newNonStr;

            // 更新sign参数
            $signParams['nonStr'] = $newNonStr;
            $signParams['timestamp'] = $newTimestamp;

            $prize['sign'] = $this->getSignature($signParams);
            return $prize;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 跟踪用户游戏行为
     * @param $userId int 用户id
     * @param $eventId int 活动id
     * @param $type int 跟踪类型
     * @param $value string 跟踪对应的值
     * @return int
     */
    public function trace($userId, $eventId, $type, $value) {
        try {
            $params = array('userId'=>$userId, 'eventId'=>$eventId, 'type'=>$type, 'value'=>$value);
            if (empty($userId) || !is_numeric($userId)) {
                throw new \Exception('用户id参数不正确');
            }

            if (empty($eventId) || !is_numeric($eventId)) {
                throw new \Exception('活动id参数不正确');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameEvent', 'trace', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * getMatchList 获取活动列表
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @access public
     * @return void
     */
    public function getMatchList($userId, $pageNo=1, $pageSize = 30) {
        try {
            $params = array('userId' => $userId, 'pageNo' => $pageNo, 'pageSize' => $pageSize);
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getUserMatchList', $request);
            $result['list'] = $response['data'];
            $result['isGiven'] = $response['isGiven'];
            foreach($result['list'] as &$item) {
                $item['id'] = $this->encodeEventId($item['id']);
            }
            return $result;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 获取用户积分排名相关信息
     */
    public function getUserPointsRank($userId) {
        try {
            $params = array('userId' => $userId);
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getUserPointsRank', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * getUserMatchDetail 获取活动详情
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId
     * @param mixed $matchId
     * @access public
     * @return void
     */
    public function getUserMatchDetail($userId,$matchId) {
        try {
            $matchId = $this->decodeEventId($matchId);
            if ($matchId === false) {
                throw new \Exception('非法活动');
            }
            $params = array('userId' => $userId,'matchId' => $matchId);
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getMatchDetail', $request);
            $response['data']['id'] = $this->encodeEventId($matchId);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * getMatchEventDetail 获取活动详情
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId
     * @param mixed $matchId
     * @access public
     * @return void
     */
    public function getMatchEventDetail() {
        try {
            $params = array();
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getMatchEventDetail', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * guessMatch活动竞猜
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId
     * @param mixed $matchId
     * @param mixed $choice
     * @param mixed $points
     * @access public
     * @return void
     */
    public function guessMatch($userId, $matchId, $choice, $points) {
        try {
            $matchId = $this->decodeEventId($matchId);
            if ($matchId === false) {
                throw new \Exception('非法活动');
            }
            $guessToken = GameEnum::GUESS_SOURCE_GUESS. $userId. '_'.$matchId;
            $params = array('userId' => $userId, 'matchId' => $matchId, 'choice' => $choice, 'points' => $points, 'token' => $guessToken);
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'guess', $request);
            return $response;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * getUserLogList用户积分记录
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param int $userId 用户id
     * @param int $pageNo 页码
     * @param int $pageSize 每页个数
     * @access public
     * @return void
     */
    public function getUserPointsLogList($userId, $pageNo, $pageSize = 10) {
        try {
            $request = new SimpleRequestBase();
            $params = array(
                'userId'=>intval($userId),
                'pageNo'=>intval($pageNo),
                'pageSize'=>intval($pageSize)
            );
            $request->setParamArray($params);

            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getUserPointsLogList', $request);
            return $response['dataPage']['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 获取用户的竞猜记录
     * @param $userId int 用户id
     * @param $pageNo int 页码
     * @param $pageSize int 每页个数
     * @return array
     */
    public function getUserGuessLogList($userId, $pageNo, $pageSize = 10) {
        try {
            $request = new SimpleRequestBase();
            $params = array(
                'userId'=>intval($userId),
                'pageNo'=>intval($pageNo),
                'pageSize'=>intval($pageSize)
            );
            $request->setParamArray($params);

            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getUserGuessLogList', $request);
            $res = array();
            foreach ($response['dataPage']['data'] as $log) {
                $item = array();
                $item['date'] = date('Y-m-d', $log['createTime']);
                $item['time'] = date('H:i:s', $log['createTime']);
                $item['status'] = $log['status'];
                $item['statusDesc'] = $log['statusDesc'];
                $item['note'] = $log['name'];

                $res[] = $item;
            }
            return $res;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * getRankList世界杯排行榜
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $pageNo
     * @param int $pageSize
     * @access public
     * @return void
     */
    public function getRankList($pageNo, $pageSize = 10) {
        try {
            $request = new SimpleRequestBase();
            $params = array(
                'pageNo'=>intval($pageNo),
                'pageSize'=>intval($pageSize)
            );
            $request->setParamArray($params);

            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getPointsRankList', $request);
            $res = array();
            $start = ($pageNo - 1) * $pageSize;
            $dealModel = DealModel::instance();
            foreach ($response['data'] as $userId=>$score) {
                $start++;

                $user = UserModel::instance()->find($userId, 'real_name,mobile', true);
                $item = array();
                $item['rank'] = $start;
                $item['name'] = $user ? $dealModel->getDealUserName($userId): '';
                $item['mobile'] = $user ? format_mobile($user['mobile']) : '';
                $item['score'] = $score;
                $res[] = $item;
            }

            return $res;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * getRankScoreList获取用户积分数据[计算大转盘用]
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-06-29
     * @param mixed $pageNo
     * @param int $pageSize
     * @access public
     * @return void
     */
    public function getRankScoreList($pageNo, $pageSize = 100) {
        try {
            $request = new SimpleRequestBase();
            $params = array(
                'pageNo'=>intval($pageNo),
                'pageSize'=>intval($pageSize)
            );
            $request->setParamArray($params);

            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'getPointsRankList', $request);
            $res = array();
            foreach ($response['data'] as $userId=>$score) {
                $item['userId'] = $userId;
                $item['score'] = $score;
                $res[] = $item;
            }

            return $res;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }


    /**
     * addInviteScore邀请首投增加积分
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId 被邀请人
     * @param mixed $triggerMode首投
     * @param mixed $dealLoadId交易id
     * @access public
     * @return void
     */
    public static function addInviteScore($userId, $triggerMode, $dealLoadId) {
        // 邀请人id
        $referUserId = O2OUtils::getReferId($userId, $triggerMode, $dealLoadId);
        if (empty($referUserId)) {
            PaymentApi::log("GameService.addInviteScore, userId: ".$userId.', triggerMode: '.$triggerMode.',dealLoadId:'.$dealLoadId.'无邀请人');
            return true;
        } else {
            $userService = new UserService();
            $referUser = $userService->getUser($referUserId);
            if (in_array($referUser['group_id'], GameEnum::$WORLDCUP_FORBIDEN_USERGROUP) ) {
                PaymentApi::log("GameService.addInviteScore, referUserId: ".$referUserId.', groupId: '.$referUser['group_id'].'会员组被屏蔽邀请奖励');
                return true;
            }
            $token = GameEnum::GUESS_SOURCE_INVITE.$userId;
            //邀请积分配置
            $points = GameEnum::GUESS_INVITER_POINTS;
            $sourceType = GameEnum::SOURCE_TYPE_INVITE;
            $sourceValue = $userId;
            $note = '邀请好友';
            PaymentApi::log("GameService.addInviteScore, userId: ".$userId.', triggerMode: '.$triggerMode.',dealLoadId:'.$dealLoadId.',referUserId:'.$referUserId.',points'.$points);
            $taskObj = new GTaskService();
            $event = new O2ORetryEvent('changeUserGamePoints', array($referUserId, $token, $points, $sourceType, $sourceValue, $note));
            $taskObj->doBackground($event, 3);
            return true;
        }
    }

    /**
     * addBidScore投资增加积分
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId
     * @param mixed $dealLoadId
     * @param mixed $bidAmount
     * @access public
     * @return void
     */
    public static function addBidScore($userId, $dealLoadId, $bidAmount) {
        $token = GameEnum::GUESS_SOURCE_BID. $userId.'_'.$dealLoadId;
        $note = '投资';
        $points = self::getBidScoreForWorldcup($bidAmount);
        $sourceType = GameEnum::SOURCR_TYPE_INVEST;
        $sourceValue = $dealLoadId;
        PaymentApi::log("GameService.addBidScore, userId: ".$userId.',dealLoadId:'.$dealLoadId.',bidAmount:'.$bidAmount.',points'.$points);
        if (empty($points)) {
            return true;
        }
        $taskObj = new GTaskService();
        $event = new O2ORetryEvent('changeUserGamePoints', array($userId, $token, $points, $sourceType, $sourceValue, $note));
        $taskObj->doBackground($event, 3);
        return true;
    }

    /**
     * acquireScore领取初始积分
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public function acquireScore($userId) {
        $token = GameEnum::GUESS_SOURCE_INIT_GIVE. $userId;
        $note = '免费领取10积分';
        $points = GameEnum::GUESS_INIT_GIVEN_POINTS;
        $sourceType = GameEnum::SOURCE_TYPE_INIT_GIVE;
        $sourceValue = '';
        PaymentApi::log("GameService.acquireScore, userId: ".$userId.',points'.$points);
        $o2oService = new O2OService();
        return $o2oService->changeUserGamePoints($userId, $token, $points, $sourceType, $sourceValue, $note);
    }

    /**
     * getBidScoreForWorldcup
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-28
     * @param mixed $bidAmount
     * @static
     * @access public
     * @return void
     */
    public static function getBidScoreForWorldcup($bidAmount) {
        $score = 0;
        switch ($bidAmount) {
        case $bidAmount < 3000:
            $score = 0;
            break;
        case $bidAmount < 6000:
            $score = GameEnum::GUESS_INVEST_3000_6000_POINTS;
            break;
        case $bidAmount < 10000:
            $score = GameEnum::GUESS_INVEST_6000_10000_POINTS;
            break;
        case $bidAmount < 30000:
            $score = GameEnum::GUESS_INVEST_10000_30000_POINTS;
            break;
        case $bidAmount < 50000:
            $score = GameEnum::GUESS_INVEST_30000_50000_POINTS;
            break;
        case $bidAmount < 100000:
            $score = GameEnum::GUESS_INVEST_50000_100000_POINTS;
            break;
        case $bidAmount >= 100000:
            $score = GameEnum::GUESS_INVEST_100000_POINTS;
            break;
        }
        return $score;
    }
}
