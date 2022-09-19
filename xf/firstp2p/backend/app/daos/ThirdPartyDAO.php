<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Library\Logger;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\Enum\UserEnum;
use libs\utils\PaymentApi;
use NCFGroup\Ptp\daos\UserDAO;
use NCFGroup\Ptp\models\Firstp2pThirdpartyInvest0;
use NCFGroup\Ptp\models\Firstp2pTradeLog;
use NCFGroup\Ptp\models\Firstp2pUser;

/**
 * ThirdPartyDAO
 * 第三方交互相关数据库操作
 * @package backend
 */
class ThirdPartyDAO
{
    /**
     * 第三方交互-投资逻辑
     * @param int $merchantId 商户ID
     * @param string $merchantNo 商户编号
     * @param int $userId 用户ID
     * @param int $amount 用户认筹金额，单位：分
     * @param string $outOrderId 第三方付款单号
     * @param string $case 交易事由,交易简单描述
     * @param string $message 消费简介
     */
    public static function invest($merchantId, $merchantNo, $userId, $amount, $outOrderId, $case, $message = '第三方投资冻结')
    {
        $result = array();
        //重新组成[第三方业务的订单号]
        $thirdOutOrderId = $outOrderId;

        //检查该投资订单是否已存在
        Firstp2pThirdpartyInvest0::$shardFieldValue = $merchantId;
        $thirdPartyInvestInfo = Firstp2pThirdpartyInvest0::findFirst(array('merchantId=:merchantId: AND outOrderId=:outOrderId:', 'bind'=>array('merchantId'=>$merchantId, 'outOrderId'=>$thirdOutOrderId)));
        if ($thirdPartyInvestInfo) {
            $result['respCode'] = UserEnum::ERROR_THIRD_PARTY_INVEST_REPEAT;
            $result['respMsg'] = UserEnum::$ERROR_MSG[$result['respCode']];
            $result['orderStatus'] = $thirdPartyInvestInfo->orderStatus;
            return $result;
        }

        //检查该用户是否存在或是否为有效用户
        $userInfo = Firstp2pUser::findFirst($userId);
        if (!$userInfo OR !isset($userInfo->isEffect) OR $userInfo->isEffect != 1) {
            $result['respCode'] = UserEnum::ERROR_THIRD_PARTY_USER_INVEST_FAILED;
            $result['respMsg'] = UserEnum::$ERROR_MSG[$result['respCode']];
            return $result;
        }

        $current_time = UserDAO::getGmtime();
        $db = getDI()->get('firstp2p');
        //开启事务
        $db->begin();
        try {
            //插入[第三方投资表]
            $thirdpartyInvestModel = new Firstp2pThirdpartyInvest0();
            $thirdpartyInvestModel->merchantNo = $merchantNo;
            $thirdpartyInvestModel->merchantId = $merchantId;
            $thirdpartyInvestModel->outOrderId = $thirdOutOrderId;
            $thirdpartyInvestModel->userId = $userId;
            $thirdpartyInvestModel->amount = $amount;
            $thirdpartyInvestModel->orderStatus = UserEnum::ERROR_ASYNC_NOTIFY_N;
            $thirdpartyInvestModel->createTime = $current_time;
            if ($thirdpartyInvestModel->create() == false) {
                throw new \Exception(sprintf('%s|%s', UserEnum::ERROR_THIRD_PARTY_INVEST_INSERT_FAILED, '插入firstp2p_thirdparty_invest表失败'));
            }

            //冻结用户余额、记录资金流水日志等
            $amountYuan = bcdiv($amount, 100, 2); //转换为元
            $lockUserMoney = UserDAO::changeMoney($userId, $amountYuan, $message, $case, UserEnum::TYPE_LOCK_MONEY, 0);
            if (!$lockUserMoney) {
                throw new \Exception(sprintf('%s|%s', UserEnum::ERROR_LOCK_MONEY_FAILED, '第三方投资冻结用户余额失败'));
            }

            //插入[第三方app 消费（余额扣减，转账）的业务）]表，进行异步处理
            $tradeLogModel = new Firstp2pTradeLog();
            $tradeLogModel->bCode = Firstp2pTradeLog::BUSINESS_CODE_THIRDPARTY_INVEST;
            $tradeLogModel->merchantNo = $merchantNo;
            $tradeLogModel->merchantId = $merchantId;
            $tradeLogModel->outOrderId = $thirdOutOrderId;
            $tradeLogModel->orderStatus = UserEnum::ERROR_ASYNC_NOTIFY_N;
            $tradeLogModel->payerId = $userId;
            $tradeLogModel->amount = $amount;
            $tradeLogModel->createTime = $current_time;
            if ($tradeLogModel->create() == false) {
                throw new \Exception(sprintf('%s|%s', UserEnum::ERROR_THIRD_PARTY_TRANSFERLOG_FAILED, '插入firstp2p_trade_log表失败'));
            }
            unset($tradeLogModel);

            //提交事务
            $db->commit();

            //记录业务日志-用户投资记录
            Logger::info(implode(' | ', array_merge(array(__CLASS__, __FUNCTION__, TASK_APP_NAME), $thirdpartyInvestModel->toArray())));
            //记录本次请求的回溯跟踪流程
            $trace = debug_backtrace();
            $caller1 = isset($trace[1]['function']) ? basename($trace[0]['file']) . '/' . $trace[1]['function'] . ':' . $trace[0]['line'] : '';
            $caller2 = isset($trace[0]['class']) ? $trace[0]['class'] . $trace[0]['type'] . $trace[0]['function'] : '';
            PaymentApi::log(sprintf('%s.%s. %s, %s, thirdpartyInvestInfo:%s', __CLASS__, __FUNCTION__, $caller1, $caller2, json_encode($thirdpartyInvestModel->toArray(), JSON_UNESCAPED_UNICODE)));
            unset($thirdpartyInvestModel);

            //组织数据
            $result['outOrderId']  = $thirdOutOrderId;
            $result['orderStatus'] = UserEnum::ERROR_ASYNC_NOTIFY_N;
            $result['respCode']    = UserEnum::ERROR_COMMON_SUCCESS;
            $result['respMsg']     = UserEnum::$ERROR_MSG[$result['respCode']];
        } catch (\Exception $ex) {
            $exceptionInitMsg = $ex->getMessage();
            if (false !== strpos($exceptionInitMsg, '|')) {
                list($exceptionCode, $exceptionMsg) = explode('|', $exceptionInitMsg);
            }
            $errorMsg = (isset($exceptionMsg) ? $exceptionMsg : $exceptionInitMsg);
            Logger::error(sprintf('%s|%s.%s_is_exception,merchantId:%d,merchantNo:%s,userId:%d,amount:%d,outOrderId:%s,case:%s,ExceptionMsg:%s', TASK_APP_NAME, __CLASS__, __FUNCTION__, $merchantId, $merchantNo, $userId, $amount, $outOrderId, $case, $errorMsg));
            //回滚事务
            $db->rollback();
            $result['respCode'] = isset($exceptionCode) ? $exceptionCode : UserEnum::ERROR_THIRD_PARTY_EXCEPTION;
            $result['respMsg'] = UserEnum::$ERROR_MSG[$result['respCode']];
            $result['exception'] = $errorMsg;
        }
        return $result;
    }

    /**
     * 第三方交互-获取投资订单列表逻辑
     * @param int $merchantId 商户ID
     * @param string $outOrderId 第三方付款单号
     * @param int $startTime 查询开始时间戳
     * @param int $endTime 查询结束时间戳
     * @param int $pageNo 当前页码
     * @param int $pageLimit 每页记录数
     */
    public static function getInvestOrderList($merchantId, $outOrderId = '', $startTime = 0, $endTime = 0, $pageNo = 1, $pageLimit = 100)
    {
        //是否只获取一条投资记录
        $isFetchOne = false;
        //组织where条件
        $where = $result = array();

        //第三方商户ID
        $where['conditions'][] = 'merchantId = :merchantId:';
        $where['bind']['merchantId'] = $merchantId;

        //第三方付款单号
        if (!empty($outOrderId)) {
            if (false === strpos($outOrderId, ',')) {
                $where['conditions'][] = 'outOrderId = :outOrderId:';
                $where['bind']['outOrderId'] = $outOrderId;
                $isFetchOne = true;
            } else {
                $outOrderIdString = self::filter($outOrderId);
                $where['conditions'][] = sprintf('outOrderId IN (\'%s\')', join('\',\'', $outOrderIdString));
            }
        }

        //查询开始/结束时间戳
        if (is_numeric($startTime) && is_numeric($endTime) && $startTime > 0 && $endTime > 0) {
            $where['conditions'][] = 'createTime BETWEEN :startTime: AND :endTime:';
            $where['bind']['startTime'] = $startTime;
            $where['bind']['endTime'] = $endTime;
        }

        //查询列表的条件
        $condtions = array(join(' AND ', $where['conditions']), 'bind'=>$where['bind']);
        //每条投资数据只查这几个字段的值
        $searchFields = array('outOrderId', 'orderStatus', 'amount', 'updateTime|finishTime|Y-m-d');
        //需要指定[第三方投资表]分表字段的值
        Firstp2pThirdpartyInvest0::$shardFieldValue = $merchantId;

        if ($isFetchOne) {
            //只传一个订单号，查询某条投资订单
            $thirdPartyInvestInfo = Firstp2pThirdpartyInvest0::findFirst($condtions);
            $thirdPartyData = Firstp2pThirdpartyInvest0::reorganizeData($searchFields, $thirdPartyInvestInfo);
            $result['tradeList'][] = $thirdPartyData;
            $result['tradeCount'] = !empty($thirdPartyData) ? 1 : 0;
        } else {
            //查询投资订单列表
            $condtions['order'] = 'id DESC';
            $pageableObj = Firstp2pThirdpartyInvest0::findByPageableList(new Pageable($pageNo, $pageLimit), $condtions, $searchFields);
            $result['tradeList'] = $pageableObj->getContent();
            $result['tradeCount'] = $pageableObj->getTotalSize();
        }

        //查询交易总金额的条件
        $where['conditions'][] = 'orderStatus = :orderStatus:';
        $where['bind']['orderStatus'] = UserEnum::ERROR_ASYNC_NOTIFY_SUCC;
        $condtions_trade = array(join(' AND ', $where['conditions']), 'bind'=>$where['bind']);

        //按指定条件，获取交易总笔数、交易总金额(索引问题所以通过程序汇总)
        $result['tradeSum'] = 0;
        $thirdPartyInvestModel = new Firstp2pThirdpartyInvest0();
        $tradeSumData = $thirdPartyInvestModel->createBuilder()
            ->columns('SUM(amount) AS tradeSum')
            ->where($condtions_trade[0], $condtions_trade['bind'])
            ->getQuery()->execute()->getFirst();

        //交易总金额
        $result['tradeSum'] = isset($tradeSumData->tradeSum) ? (int)$tradeSumData['tradeSum'] : 0;
        //业务状态码
        $result['respCode'] = UserEnum::ERROR_COMMON_SUCCESS;

        return $result;
    }

    /**
     * 对字符串或数组进行格式化，返回格式化后的数组
     * @param array|string $input 要格式化的字符串或数组
     * @param string $delimiter 按照什么字符进行分割
     * @return array 格式化结果
     */
    public static function filter($input, $delimiter = ',')
    {
        if (!is_array($input))
        {
            $input = explode($delimiter, trim($input));
        }
        return array_filter(array_map('trim', $input), 'strlen');
    }

}
