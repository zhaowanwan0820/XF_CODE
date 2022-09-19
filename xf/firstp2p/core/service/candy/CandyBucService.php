<?php

namespace core\service\candy;

use core\service\UserService;
use libs\db\Db;
use core\service\candy\CandyAccountService;
use libs\buc\Buc;
use libs\utils\Monitor;
use libs\utils\ABControl;
use libs\utils\Logger;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use libs\utils\Curl;
use libs\utils\Alarm;
use core\service\candy\CandyUtilService;


/**
 * 信宝Buc兑换相关
 */
class CandyBucService
{

    // 余额小数位数
    const BUC_AMOUNT_DECIMALS = 6;

    const GET_USD_CNY_URL = 'http://hq.sinajs.cn/rn=1531623135024list=fx_susdcny';

    const BUC_TO_CNY_CACHE_KEY = 'buc2cny_cache_key';

    //buc提取订单初始状态
    const BUC_WITHDRAW_ORDER_STATUS_CREATE = 1;

    //buc提取订单成功
    const BUC_WITHDRAW_ORDER_STATUS_SUCCESS = 2;

    //buc提取订单失败
    const BUC_WITHDRAW_ORDER_STATUS_FAIL = 3;

    const BUC_ACCOUNT_EXCHANGE_TYPE = '信宝兑换';

    //提币实名加密盐
    const IDNO_MD5_SALT = 'b523f47b7be4aa9c66ee';

    // 因20180725被盗提币，为将历史回填地址清空，查询地址起始ID改为380000
    const WITHDRAW_ADDRESS_START_ID = 380000;

    private static $withdrawError = '';

    /**
     * 获取汇率
     */
    public function getCandyBucRate()
    {
        return app_conf('CANDY_BUC_RATE');
    }

    /**
     * buc行情
     */
    public function buc2usd()
    {
        return Buc::instance()->buc2usd();
    }

    /**
     * buc人民币价值
     */
    public function buc2cny()
    {
        $usd = $this->buc2usd();
        if (empty($usd)) {
            Monitor::add('GET_BUC_MARKET_FAIL');
            return 0;
        }

        //获取新浪美元兑人民币汇率
        $data = Curl::get(self::GET_USD_CNY_URL);
        $arr = explode(',', $data);
        //默认汇率
        if (empty($arr) || count($arr) < 3) {
            Logger::info("get usd2cny fail. data:{$data}");
            $cny = '6.69';
        } else {
            $cny = $arr[2];
        }
        Logger::info("usd2cny request. cny:{$cny}, data:{$data}, cost:". Curl::$cost);

        return bcmul($usd, $cny, 2);
    }

    /**
     *  buc人民币价值缓存
     */
    public function setBuc2cnyToCache()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $price = $this->buc2cny();

        $redis->SET(self::BUC_TO_CNY_CACHE_KEY, $price);
        return $price;
    }

    /**
     * 获取缓存中buc人民币价值
     */
    public function getBuc2cnyFromCache()
    {
        // 暂时不展示
        return 0;

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $price = $redis->GET(self::BUC_TO_CNY_CACHE_KEY);
        return $price;
    }

    /**
     * 计算Buc数量
     */
    public function calcBucAmount($candyAmount)
    {
        return bcmul($candyAmount, $this->getCandyBucRate(), self::BUC_AMOUNT_DECIMALS);
    }

    /**
     * 兑换Buc
     */
    public function exchange($userId, $bucAmount)
    {
        if (!$this->isOpen()) {
            throw new \Exception('系统已关闭');
        }

        $limit = $this->getBucLimit();
        //提取buc数量超过当前总余额
        if ($limit['buc_amount_total'] < bcadd($limit['buc_amount_used'], $bucAmount, self::BUC_AMOUNT_DECIMALS)) {
            throw new \Exception('您申请兑换BUC数量超出当前库存');
        }

        $userUsed = $this->getUserExchangeBucUsed($userId);
        //提取buc数量超过用户可兑额度
        if ($limit['buc_amount_user_total'] < bcadd($userUsed, $bucAmount, self::BUC_AMOUNT_DECIMALS)) {
            throw new \Exception('您申请兑换BUC数量超出当前库存');
        }

        // 是否受限用户
        $accountService = new CandyAccountService();
        if ($accountService->isLimited($userId)) {
            throw new \Exception('投资1次即可兑换虚拟商品！');
        }

        if (bccomp($bucAmount, 0 , self::BUC_AMOUNT_DECIMALS) <= 0) {
            throw new \Exception('兑换BUC个数必须大于等于0.000001');
        }

        $db = Db::getInstance('candy');
        $accountInfo = $accountService->getAccountInfo($userId);
        if (empty($accountInfo))  {
            throw new \Exception('可用信宝不足');
        }

        $bucRate = $this->getCandyBucRate();
        $changeAmount = CandyUtilService::calcAmountByCoin($bucRate, $bucAmount, CandyBucService::BUC_AMOUNT_DECIMALS);

        if (bcsub($accountInfo['amount'], $changeAmount,CandyAccountService::AMOUNT_DECIMALS) < 0) {
            throw new \Exception('可用信宝不足');
        }
        $db->startTrans();
        try {
            $userUsed = $this->getUserExchangeBucUsed($userId);
            //提取buc数量超过用户可兑额度
            if ($limit['buc_amount_user_total'] < bcadd($userUsed, $bucAmount, self::BUC_AMOUNT_DECIMALS)) {
                throw new \Exception('您申请兑换BUC数量超出当前库存');
            }
            $accountService->changeAmount($userId, -$changeAmount, '兑换BUC', '兑换BUC:' . $bucAmount. '个');
            //更新buc余额
            $this->changeBucAmount($userId, $bucAmount, '信宝兑换', '消费信宝:' . $changeAmount . '个');
            //减库存
            $this->changeBucDailyStock($bucAmount);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error('buc exchange fail. msg:'. $e->getMessage() . ", userid: {$userId}");
            throw new \Exception('系统繁忙，请稍后再试');
        }
    }

    public function changeBucDailyStock($amount)
    {
        $token = $this->getBucDailyStockToken();

        $db = Db::getInstance('candy');
        $updateSql = "UPDATE buc_daily_stock SET buc_amount_used=buc_amount_used+{$amount}, update_time=" . time();
        $updateSql .= " WHERE token= '{$token}' AND buc_amount_used+{$amount}<=buc_amount_total";
        $db->query($updateSql);
        if ($db->affected_rows() < 1) {
            throw new \Exception('系统繁忙，请稍后再试');
        }
    }


    const EXCHANGE_BUC_DAILY_USER_LIMIT_DEFAULT = 1000000000; //默认个人限制，等于默认前端不展示
    const EXCHANGE_BUC_DAILY_TOTAL_LIMIT_DEFAULT = 1000000000;   //默认总额度限制，等于默认前端不展示

    /**
     * 兑换buc每日库存
     */
    public function getOrCreateExchangeBucLimit()
    {
        $db = Db::getInstance('candy');
        $token = $this->getBucDailyStockToken();
        $result = $db->getRow("SELECT * FROM buc_daily_stock WHERE token='$token'");
        if (empty($result)) {
            $userLimit = app_conf('EXCHANGE_BUC_DAILY_USER_LIMIT');
            $totalLimit = app_conf('EXCHANGE_BUC_DAILY_TOTAL_LIMIT');

            $dailyStock = [
                'token' => $token,
                'buc_amount_total' => $totalLimit === '' ? self::EXCHANGE_BUC_DAILY_TOTAL_LIMIT_DEFAULT : $totalLimit,
                'buc_amount_user_total' => $userLimit === '' ? self::EXCHANGE_BUC_DAILY_USER_LIMIT_DEFAULT: $userLimit,
                'buc_amount_used' => 0,
                'version' => 0,
                'create_time' => time(),
            ];
            $insertId = $db->insert('buc_daily_stock', $dailyStock);
            if (empty($insertId)) {
                throw new \Exception('写入buc每日库存失败');
            }
            return $dailyStock;
        }

        return $result;
    }

    /**
     * 兑换buc每日库存，非白名单用户库存为0
     */
    public function getBucLimit()
    {
        $limit = $this->getOrCreateExchangeBucLimit();

        if (!ABControl::getInstance()->hit('candy_buc_exchange')) {
            $limit['buc_amount_used'] = $limit['buc_amount_total'];
        }

        return $limit;
    }

    /**
     * 兑换buc每天用户已用额度
     */
    public function getUserExchangeBucUsed($userId)
    {
        $start = strtotime($this->getBucDailyStockToken()) + 9 * 3600;
        $used = Db::getInstance('candy')->getOne("SELECT sum(buc_amount) FROM buc_account_log WHERE user_id = '{$userId}' AND create_time >= '{$start}' AND type = '" . self::BUC_ACCOUNT_EXCHANGE_TYPE . "'");
        if (empty($used)) {
            return 0;
        }
        return $used;
    }

    public function getBucDailyStockToken()
    {
        if (date("H") < 9) {
            return date("Ymd", time() - 86400);
        }

        return date('Ymd');
    }

    /**
     * 提币
     */
    public function withdrawToBitUN($outTradeId, $toAddr, $amount, $userId, $idnoSign)
    {
        $db = Db::getInstance('candy');
        if (!empty($idnoSign)) {
            $ret = Buc::instance()->realNameWithdraw($outTradeId, $toAddr, $amount, $idnoSign);
        } else {
            $ret = Buc::instance()->withdraw($outTradeId, $toAddr, $amount);
        }

        $where = "token = '$outTradeId'";
        $bucWithdrawFails = [
            '100000' => '请求参数验证失败',
            //'100001' => '服务不可用',
            //'100004' => '系统错误',
            //'100005' => '业务失败',
            '100007' => '签名无效',
            '201001' => '提币地址无效',
            '201002' => '系统繁忙，请稍后重试',//'发起方余额不足',
            '201003' => '用户不可用',
            '201005' => '提币地址未实名，请到BitUN进行实名认证',
            '201006' => '只能提取到自己的地址',
        ];

        if ($ret['respCode'] == '201002') {
            Alarm::push('BUC_REQUEST', 'BUC头寸不足', "BUC头寸不足, amount:{$amount}, userid:{$userId}");
        }

        if (isset($bucWithdrawFails[$ret['respCode']])) {
            Monitor::add("BUC_WITHDRAW_ERROR");
            self::$withdrawError = $bucWithdrawFails[$ret['respCode']] . '(' . $ret['respCode'] . ')';
            return false;
        }

        if ($ret['respCode'] != '000000') {
            throw new \Exception('提币接口返回异常:'. $ret['respCode']);
        }

        Monitor::add("BUC_WITHDRAW_SUCCESS");
        $updateData = [
            'status' => self::BUC_WITHDRAW_ORDER_STATUS_SUCCESS,
            'finish_time' => time(),
            'buc_trade_no' => $ret['data']['tradeNo'],
        ];
        $db->update('buc_withdraw_order', $updateData, $where);
        return true;
    }

    public function withdraw($userId, $address, $bucAmount, $name, $idno)
    {
        Logger::info("CandyBucService withdraw. userId:{$userId}, address:{$address}, amount:{$bucAmount}, name:{$name}, idno:" . idnoNewFormat($idno));

        if (empty($name) || empty($idno)) {
            throw new \Exception('提取失败，获取不到用户信息');
        }
        $idnoSign = md5("{$name}&" . strtolower($idno) . "&" . self::IDNO_MD5_SALT);

        // 是否受限
        $accountService = new CandyAccountService();
        if ($accountService->isLimited($userId)) {
            throw new \Exception('投资1次即可提取！');
        }
        $gtm = new GlobalTransactionManager();
        $gtm->setName('buc_withdraw');
        $orderId = $gtm->getTid();
        $gtm->addEvent(new EventMaker([
            'execute' => [$this, 'createWithdrawOrder', [$orderId, $userId, $address, $bucAmount]],
            'rollback' => [$this, 'cancelWithdrawOrder', [$orderId, $userId, $bucAmount]],
        ]));
        if (ABControl::getInstance()->hit('candy_buc_real_name_withdraw')) {
            $gtm->addEvent(new EventMaker([
                'execute' => [$this, 'withdrawToBitUN', [$orderId, $address, $bucAmount, $userId, $idnoSign]],
            ]));
        } else {
            $gtm->addEvent(new EventMaker([
                'execute' => [$this, 'withdrawToBitUN', [$orderId, $address, $bucAmount, $userId, '']],
            ]));
        }

        $ret = $gtm->execute();

        if ($ret === false && strpos(self::$withdrawError, '系统繁忙') === false) {
            throw new \Exception('提取失败，'. self::$withdrawError);
        } else if ($ret === false) {
            throw new \Exception(self::$withdrawError);
        }

        return $ret;
    }

    public function cancelWithdrawOrder($orderId, $userId, $bucAmount) {
        $db = Db::getInstance('candy');
        $result = $db->getRow("SELECT `id`,`status` FROM buc_withdraw_order WHERE token = '{$orderId}'");

        //订单已经更新成失败 或者 订单没创建成功
        if ( empty($result['id']) || $result['status'] == self::BUC_WITHDRAW_ORDER_STATUS_FAIL) {
            return true;
        }

        //提币回滚
        $db->startTrans();
        try {
            $this->changeBucAmount($userId, $bucAmount, '提币失败充正', "提币{$bucAmount}失败回滚");
            $updateData = [
                'status' => self::BUC_WITHDRAW_ORDER_STATUS_FAIL,
                'errormsg' => self::$withdrawError,
            ];
            $where = "token = '$orderId'";
            $db->update('buc_withdraw_order', $updateData, $where);
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

    public function createWithdrawOrder($orderId, $userId, $address, $bucAmount) {
        $db = Db::getInstance('candy');
        $db->startTrans();
        try {
            $withdrawOrder = [
                'token' => $orderId,
                'address' => $address,
                'user_id' => $userId,
                'buc_amount' => $bucAmount,
                'status' => self::BUC_WITHDRAW_ORDER_STATUS_CREATE,
                'create_time' => time(),
            ];
            $insertId = $db->insert('buc_withdraw_order', $withdrawOrder);
            if (empty($insertId)) {
                throw new \Exception('插入订单失败');
            }
            $bucAmount = bcmul($bucAmount, -1, self::BUC_AMOUNT_DECIMALS);
            $this->changeBucAmount($userId, $bucAmount, '提币', "提币{$bucAmount}到地址{$address}");
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error('提币失败,'. $e->getMessage());
            throw new \Exception('系统繁忙,请稍后重试');
        }
        return true;
    }

    /**
     * 更改余额
     */
    public function changeBucAmount($userId, $amount, $type, $note)
    {
        $accountService = new CandyAccountService();
        $accountInfo = $accountService->getAccountInfo($userId);

        $amountNew = bcadd($accountInfo['buc_amount'], $amount, self::BUC_AMOUNT_DECIMALS);
        if ($amountNew < 0) {
            throw new \Exception('可用BUC不足');
        }

        $data = array(
            'buc_amount' => $amountNew,
            'update_time' => time(),
            'version' => $accountInfo['version'] + 1,
        );

        $db = Db::getInstance('candy');
        $db->startTrans();
        try {
            $where = "id='{$accountInfo['id']}' AND version='{$accountInfo['version']}'";
            $db->update('candy_account', $data, $where);
            if ($db->affected_rows() < 1) {
                throw new \Exception('修改BUC余额冲突');
            }

            $insertId = $db->insert('buc_account_log', array(
                'user_id' => $userId,
                'buc_amount' => $amount,
                'buc_amount_final' => $amountNew,
                'type' => $type,
                'note' => $note,
                'create_time' => time(),
            ));
            if (empty($insertId)) {
                throw new \Exception('buc记录插入失败');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function isOpen() {
        if (app_conf('CANDY_BUC_SWITCH') || ABControl::getInstance()->hit('candy_buc')) {
            return true;
        }

        return false;
    }

    public function getLastWithdrawAddress($userId) {
        $db = Db::getInstance('candy');
        $address = $db->getOne("SELECT `address` FROM buc_withdraw_order WHERE id > " . self::WITHDRAW_ADDRESS_START_ID . " AND user_id = '{$userId}' ORDER BY id DESC LIMIT 1");
        return $address;
    }

    public function getTopBucList() {
        if (!$this->isOpen()) {
            return [];
        }

        return [
            [
                'price' => 1,
                'pic' => '',
                'name' => $this->getCandyBucRate() . ' BUC',
                'market_price' => 100,
            ],
        ];
    }

    public function getSuggestBucList() {
        return $this->getTopBucList();
    }
}
