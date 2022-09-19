<?php


namespace core\service\candy;


use core\service\UserService;
use libs\db\Db;
use core\service\candy\CandyAccountService;
use core\service\candy\CandyPayService;
use libs\cre\Cre;
use libs\utils\Monitor;
use libs\utils\ABControl;
use libs\utils\Logger;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use libs\utils\Curl;
use libs\utils\Alarm;
use core\service\candy\CandyUtilService;


class CandyCreService
{
    
    // Cre提取订单初始状态
    const CRE_CONVERT_ORDER_STATUS_CREATE = 1;
    
    // Cre提取订单成功
    const CRE_CONVERT_ORDER_STATUS_SUCCESS = 2;

    // Cre提取订单失败
    const CRE_CONVERT_ORDER_STATUS_FAIL = 3;

    // Cre单位保留位数
    const CRE_AMOUNT_DECIMALS = 6;

    // 信宝兑CRE的基本兑换比率
    const BASE_CONVERT_CRE_RATE = 2;

    // 兑换CRE返回结果
    private $creConvertResult = '';

    // 提币错误记录
    private $convertError = '';

    // 远程接口错误
    private static $creConvertFails = array(
        '500'   => '服务器内部错误',
        '10002' => '用户未注册',
        '10003' => '用户未实名认证',
        '10004' => '请求非法（签名错误）',
        '10005' => '请求非法，（token重复）',
        '10006' => '单日兑换达到上限',
        '10007' => '系统兑换达到上限',
        '10008' => '无此兑换记录',
        '10009' => '此兑换兑换释放中，无法退回',
    );

    // 信宝兑换CRE汇率
    private static $candyToCreRate = array(
        200000 => 10,
        100000 => 8,
        1000 => 3,
    );

    /**
     * 创建订单，扣除信宝，扣库存
     */
    public function convert($userId, $creAmount)
    {
        if (!$this->isOpen()) {
            throw new \Exception("系统已关闭");
        }

        $candyToCreRate = $this->getCandyCreRate($userId);

        $changeAmount = CandyUtilService::calcAmountByCoin($candyToCreRate, $creAmount, CandyCreService::CRE_AMOUNT_DECIMALS);

        $limit = $this->getOrCreateConvertCreLimit();
        if ($limit['cre_amount_total'] < $limit['cre_amount_used'] + $creAmount) {
            throw new \Exception("您申请兑换的cre数量已经超过了当前库存");
        }

        // 判断用户兑换的cre是否超过该用户的兑换额度
        $userUsed = $this->getUserConvertCreUsed($userId);
        if ($limit['cre_amount_user_total'] < bcadd($userUsed, $creAmount, self::CRE_AMOUNT_DECIMALS)) {
            throw new \Exception("您申请兑换的cre数量已经超过了当前库存");
        }

        // 兑换的cre数值必须大于最小单位
        if (bccomp($creAmount, 0, self::CRE_AMOUNT_DECIMALS) <= 0) {
            throw new \Exception("兑换的最小单位必须大于0.000001");
        }

        $accountService = new CandyAccountService();
        // 检查用户是否存在
        $accountInfo = $accountService->getAccountInfo($userId);
        if (empty($accountInfo)) {
            throw new \Exception("可用信宝不足");
        }

        // 用户是否受限
        if ($accountService->isLimited($userId)) {
            throw new \Exception("投资一次即可兑换！");
        }

        // 检查用户的信宝余额是否足够
        if (bcsub($accountInfo['amount'], $changeAmount, CandyAccountService::AMOUNT_DECIMALS) < 0) {
            throw new \Exception("可用信宝不足");
        }

        $gtm = new GlobalTransactionManager();
        $gtm->setName('cre_convert');
        $token = $gtm->getTid();
        Logger::info("CandyCreService convert. userId:{$userId}, token:{$token}, creAmount:{$creAmount}, amount:{$changeAmount}");
        $gtm->addEvent(new EventMaker(array(
            'execute' => array($this, 'convertOrderCreate', array($userId, $token, $creAmount, $changeAmount)),
            'rollback' => array($this, 'convertOrderCancel', array($userId, $token, $creAmount, $changeAmount)),
        )));
        $gtm->addEvent(new EventMaker(array(
            'execute' => array($this, 'convertCreRequest', array($userId, $token, $creAmount, $changeAmount))
        )));

        $ret = $gtm->execute();

        if ($ret === false) {
            throw new \Exception('兑换并提取失败，' . $this->convertError);
        }

        return $this->creConvertResult;
    }

    /**
     * 信宝兑换CRE并创建订单
     */
    public function convertOrderCreate($userId, $token, $creAmount, $changeAmount)
    {
        $accountService = new CandyAccountService();

        $db = Db::getInstance('candy');
        $db->startTrans();
        try {
            // 更改信宝账户余额
            $accountService->changeAmount($userId, -$changeAmount, '兑换CRE', '兑换CRE:'.$creAmount);
            // 更改cre库存数量
            $this->changeCreDailyStock($creAmount);
            // 创建订单
            $this->createCreOrder($userId, $token, $creAmount, $changeAmount);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error('cre convert fail. msg:'. $e->message() . ', userId: {$userId}');
            $this->convertError = "库存不足";
            return false;
        }
        return true;
    }

    /**
     * 更改CRE日常库存
     */
    public function changeCreDailyStock($creAmount)
    {
        $token = $this->getCreDailyStockToken();

        $db = Db::getInstance('candy');
        $updateSql = "UPDATE cre_daily_stock SET cre_amount_used=cre_amount_used+{$creAmount}, update_time=" . time();
        $updateSql .= " WHERE token= '{$token}' AND cre_amount_used+{$creAmount}<=cre_amount_total";
        $db->query($updateSql);
        if ($db->affected_rows() < 1) {
            throw new \Exception('系统繁忙，请稍后再试');
        }
    }

    /**
     * 获取token
     */
    public function getCreDailyStockToken()
    {
        return date('Ymd');
    }

    /**
     * 创建提币订单
     */
    public function createCreOrder($userId, $token, $creAmount, $changeAmount)
    {
        $creExchangeOrder = array(
            'token' => $token,
            'user_id' => $userId,
            'cre_amount' => $creAmount,
            'amount' => $changeAmount,
            'status' => self::CRE_CONVERT_ORDER_STATUS_CREATE,
            'create_time' => time(),
            'errormsg'  => '',
        );
        $insertId = Db::getInstance('candy')->insert('cre_convert_order', $creExchangeOrder);
        if (empty($insertId)) {
            throw new \Exception("插入订单失败！");
        }
    }

    /**
     * 提币
     */
    public function convertCreRequest($userId, $token, $creAmount, $changeAmount)
    {
        $ret = Cre::instance()->requestExchange($userId, $creAmount, $changeAmount, $token);

        $where = "token = '{$token}'";
        if ($ret['code'] == '500') {
            throw new \Exception("提币接口异常,服务器内部错误");
        }

        if (isset(self::$creConvertFails[$ret['code']])) {
            Monitor::add("CRE_CONVERT_ERROR");
            $this->convertError = self::$creConvertFails[$ret['code']] . '(' . $ret['code'] . ')';
            return false;
        }

        Monitor::add("CRE_CONVERT_SUCCESS");
        $updateData = array(
            'status' => self::CRE_CONVERT_ORDER_STATUS_SUCCESS,
            'update_time' => time(),
            'token' => $token,
        );
        Db::getInstance('candy')->update('cre_convert_order', $updateData, $where);
        if (Db::getInstance('candy')->affected_rows() < 1) {
            throw new \Exception("订单更新失败！");
        }

        $this->creConvertResult = $ret;
        return true;
    }

    /**
     * 提币冲正
     */
    public function convertOrderCancel($userId, $token, $creAmount, $changeAmount)
    {
        $db = Db::getInstance('candy');
        $result = $db->getRow("SELECT id, status FROM cre_convert_order WHERE token='{$token}'");

        // 订单已经更新成失败或订单没创建成功
        if ($result['status'] == self::CRE_CONVERT_ORDER_STATUS_FAIL || empty($result['id'])) {
            return true;
        }

        // 提币回滚
        $db->startTrans();
        try {
            // 回滚CRE库存
            $this->changeCreDailyStock(-$creAmount);
            // 冲正信宝
            $accountService = new CandyAccountService();
            $accountService->changeAmount($userId, $changeAmount, '提币冲正', 'CRE提币失败回滚');
            // 更新订单
            $updateData = array(
                'status' => self::CRE_CONVERT_ORDER_STATUS_FAIL,
                'errormsg' => $this->convertError,
                'update_time' => time(),
            );
            $where = "token = '{$token}'";
            $db->update('cre_convert_order', $updateData, $where);
            if ($db->affected_rows() < 1) {
                throw new \Exception('更新冲突');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 获取用户CRE兑信宝汇率
     */
    public function getCandyCreRate($userId)
    {
        $amount = CandyUtilService::getUserInvestAmountToday($userId, CandyUtilService::LIMIT_DEAL_AMOUNT_ANNUALIZED);
        krsort(self::$candyToCreRate);
        foreach (self::$candyToCreRate as $key => $value) {
            if ($amount >= $key) {
                return $value;
            }
        }
        return self::BASE_CONVERT_CRE_RATE;
    }

    /**
     * 信宝兑CRE数量
     */
    public function calcCreAmount($candyToCreRate, $candyAmount)
    {
        return bcmul($candyAmount, $candyToCreRate, self::CRE_AMOUNT_DECIMALS);
    }

    /**
     * 兑换cre每日库存
     */
    public function getOrCreateConvertCreLimit()
    {
        $db = Db::getInstance('candy');
        $token = $this->getCreDailyStockToken();
        $result = $db->getRow("SELECT * FROM cre_daily_stock WHERE token='{$token}'");
        if (!empty($result)) {
            return $result;
        }

        $userLimit = app_conf('CONVERT_CRE_DAILY_USER_LIMIT');
        $totalLimit = app_conf('CONVERT_CRE_DAILY_TOTAL_LIMIT');

        $dailyStock = [
            'token' => $token,
            'cre_amount_total' => $totalLimit,
            'cre_amount_user_total' => $userLimit,
            'cre_amount_used' => 0,
            'create_time' => time(),
        ];
        $insertId = $db->insert('cre_daily_stock', $dailyStock);
        if (empty($insertId)) {
            throw new \Exception('写入cre每日库存失败');
        }

        return $dailyStock;
    }

    /**
     * 兑换CRE每天用户已用额度
     */
    public function getUserConvertCreUsed($userId)
    {
        $start = strtotime($this->getCreDailyStockToken());
        $used = Db::getInstance('candy')->getOne("SELECT sum(cre_amount) FROM cre_convert_order WHERE user_id = '{$userId}' AND create_time >= '{$start}' AND status = " . self::CRE_CONVERT_ORDER_STATUS_SUCCESS);
        if (empty($used)) {
            return 0;
        }
        return $used;
    }

    /**
     * 前台显示CRE与信宝兑换比例
     */
    public function getCreInfo()
    {
        if (!$this->isOpen()) {
            return [];
        }

        return [
            [
                'name' => "限时抢兑CRE",
                'price' => 1,
            ],
        ];
    }

    /**
     * CRE开关
     */
    public function isOpen()
    {
        if (app_conf('CANDY_CRE_SWITCH') || ABControl::getInstance()->hit('candy_cre')) {
            return true;
        }

        return false;
    }

}
