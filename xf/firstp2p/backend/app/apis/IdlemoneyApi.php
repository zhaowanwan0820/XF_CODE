<?php

namespace NCFGroup\Ptp\Apis;
use core\dao\UserModel;
use core\service\UserMoneySnapshotService;
use core\service\UserThirdBalanceService;
use core\service\BonusService;

/**
 * 信仔机器人-限制资金接口
 */
class IdlemoneyApi {
    /**
     * 参数列表
     * @var array
     */
    private $params;

    /**
     * 闲置资金的提示配置
     * @var array
     */
    private static $idleMoneyConfig = [
        'bubble' => '您有%s元闲置资金哦', // 气泡提示
        'msg' => [ // 消息提示
            'tips0' => '您当前账户没有闲置资金哦~建议去充值投资赚取收益哦~',
            'tips1' => '您当前闲置资金<span>%s元</span>，快去投资赚取收益哦~',
            'tips2' => '您有<span>%s元</span>闲置资金，已闲置<span>%d天</span>，快去投资赚取收益哦~',
        ],
        'push' => '%s元闲置资金，已闲置%d天', // push消息
    ];

    /**
     * 每次处理的最大push消息数量
     * @var int
     */
    private static $maxPushCount = 100;

    private function init($isCheckUserId = true) {
        $di = getDI();
        $this->params = $di->get('requestBody');
        if ($isCheckUserId && empty($this->params['userId'])) {
            throw new \Exception('缺少参数userId');
        }
    }

    /**
     * 信仔-气泡接口
     * @return array
     */
    public function bubble() {
        try{
            $this->init();

            // 用户ID
            $userId = (int)$this->params['userId'];
            // 闲置天数
            $day = (int)$this->params['day'];
            // 闲置金额
            $amount = addslashes($this->params['amount']);

            if (!is_numeric($day) || (int)$day < 0 || bccomp($amount, '0.00', 2) <= 0) {
                throw new \Exception('参数不合法');
            }

            // 获取用户闲置资金，单位元
            $service = new UserMoneySnapshotService();
            $idleAmount = $service->getUserIdleMoney($userId, $day, $amount);
            // 用户当前总余额(超级账户余额+网贷余额+红包余额)
            $userCurrentMoneyInfo = UserMoneySnapshotService::getUserMoneyToday($userId, true);
            $userCurrentMoney = $userCurrentMoneyInfo['total_money'];

            $content = '';
            if (bccomp($userCurrentMoney, $amount, 2) >= 0 && bccomp($idleAmount, $amount, 2) >= 0) {
                $content = sprintf(self::$idleMoneyConfig['bubble'], number_format($userCurrentMoney, 2));
            }

            return ['errorCode'=>0, 'errorMsg'=>'success', 'data' => ['content'=>$content]];
        }catch (\Exception $ex){
            return ['errorCode'=>-1, 'errorMsg'=>$ex->getMessage(), 'data' => []];
        }
    }

    /**
     * 信仔-消息接口
     * type (integer, optional): 类型分三种类型, 1:跳转原生, 2:跳转h5, 3:再次给信仔发送关键词
     * uri里面的type：投资22, 充值25
     * @return array
     */
    public function msg() {
        try{
            $this->init();

            // 用户ID
            $userId = (int)$this->params['userId'];
            // 闲置天数
            $day = (int)$this->params['day'];
            // 闲置金额
            $amount = addslashes($this->params['amount']);

            if (!is_numeric($day) || (int)$day < 0 || bccomp($amount, '0.00', 2) <= 0) {
                throw new \Exception('参数不合法');
            }

            // 获取用户闲置资金，单位元
            $service = new UserMoneySnapshotService();
            $idleAmount = $service->getUserIdleMoney($userId, $day, $amount);

            // 用户当前总余额(超级账户余额+网贷余额+红包余额)
            $userCurrentMoneyInfo = UserMoneySnapshotService::getUserMoneyToday($userId, true);
            $userCurrentMoney = $userCurrentMoneyInfo['total_money'];

            if (bccomp($userCurrentMoney, $amount, 2) >= 0 && bccomp($idleAmount, $amount, 2) >= 0) {
                // 用户有闲置资金
                $msg = sprintf(self::$idleMoneyConfig['msg']['tips2'], number_format($userCurrentMoney, 2), $day);
                $buttonTitle = '立即投资';
                $title = '为您找到的闲置资金信息';
                $uri = '{"type":22}';
            } elseif ($userCurrentMoney > 0) {
                // 用户没有闲置资金，但是当前余额不为0
                $msg = sprintf(self::$idleMoneyConfig['msg']['tips1'], number_format($userCurrentMoney, 2));
                $buttonTitle = '立即投资';
                $title = '为您找到的闲置资金信息';
                $uri = '{"type":22}';
            } else {
                // 用户既没有闲置资金，也没有余额
                $msg = self::$idleMoneyConfig['msg']['tips0'];
                $buttonTitle = '去充值';
                $title = '为您找到的闲置资金信息';
                $uri = '{"type":25}';
            }

            return ['errorCode' => 0, 'errorMsg' => 'success', 'data' => [
                    'content' => [
                        'actionList' => [['imageUrl' => '', 'title' => $buttonTitle, 'type' => 1, 'uri' => $uri]],
                        'columnNum' => 1,
                        'title' => $msg,
                    ],
                    'title' => $title,
                    'type' => 1,
                    ]
            ];
        }catch (\Exception $ex){
            return ['errorCode'=>-1, 'errorMsg'=>$ex->getMessage(), 'data' => []];
        }
    }

    /**
     * 信仔-push接口
     * @return array
     */
    public function push() {
        try{
            $this->init(false);
            if (empty($this->params)) {
                throw new \Exception('缺少参数');
            }

            $list = [];
            // 获取用户参数数量
            $paramsCount = count($this->params);
            $loopCount = ceil($paramsCount / self::$maxPushCount);
            for ($page=1; $page<=$loopCount; $page++) {
                $tmpParams = array_slice($this->params, ($page-1)*self::$maxPushCount, self::$maxPushCount);
                if (empty($tmpParams)) {
                    continue;
                }

                $exists = [];
                foreach ($tmpParams as $tmpValue) {
                    // 用户ID
                    $userId = (int)$tmpValue['userId'];
                    // 闲置天数
                    $day = (int)$tmpValue['day'];
                    // 闲置金额
                    $amount = addslashes($tmpValue['amount']);

                    if ($userId <=0 || !is_numeric($day) || (int)$day < 0
                        || bccomp($amount, '0.00', 2) <= 0 || isset($exists[$userId])) {
                        continue;
                    }

                    $exists[$userId] = 1;
                    // 获取用户闲置资金，单位元
                    $service = new UserMoneySnapshotService();
                    $idleAmount = $service->getUserIdleMoney($userId, $day, $amount);

                    // 用户当前总余额(超级账户余额+网贷余额+红包余额)
                    $userCurrentMoneyInfo = UserMoneySnapshotService::getUserMoneyToday($userId, true);
                    $userCurrentMoney = $userCurrentMoneyInfo['total_money'];

                    if (bccomp($userCurrentMoney, $amount, 2) >= 0 && bccomp($idleAmount, $amount, 2) >= 0) {
                        // 用户有闲置资金
                        $list[] = ['userId'=>$userId, 'content'=>sprintf(self::$idleMoneyConfig['push'], number_format($userCurrentMoney, 2), $day)];
                    }
                }
            }

            return ['errorCode' => 0, 'errorMsg' => 'success', 'data' => $list];
        }catch (\Exception $ex){
            return ['errorCode'=>-1, 'errorMsg'=>$ex->getMessage(), 'data' => []];
        }
    }
}