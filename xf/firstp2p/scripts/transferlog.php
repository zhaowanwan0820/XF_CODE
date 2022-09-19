<?php
/**
 * 导出转账数据
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\FinanceQueueModel;
use libs\utils\PaymentApi;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class Transferlog
{
    const BIZ_TYPE_REPAY = 1; // 还款
    const BIZ_TYPE_GRANT = 4; // 放款
    const BIZ_TYPE_YEEPAY_CHARGE = 8; // 易宝充值


    const CATE_TYPE_LOAN_FEE = 'LOAN_FEE';
    const CATE_TYPE_PAY_SERVICE_FEE = 'PAY_SERVICE_FEE';
    const CATE_TYPE_CONSULT_FEE = 'CONSULT_FEE';
    const CATE_TYPE_GUARANTEE_FEE = 'GUARANTEE_FEE';
    const CATE_TYPE_DEALLOANREPAY = 'DEALLOANREPAY';
    const CATE_TYPE_DEALLOANREPAYHF = 'DEALLOANREPAYHF';
    const CATE_TYPE_PREDEALLOANPAY= 'PREDEALLOANREPAY';
    const CATE_TYPE_PREPAY_INTEREST = 'PREPAYINTEREST';
    const CATE_TYPE_PREPAY_COMPENSATION = 'PREPAYCOMPENSATION';
    const CATE_TYPE_PREPAYHF = 'PREPAYDEALHF';
    const CATE_TYPE_PREPAYDEAL= 'PREPAYDEAL';

    const CATE_TYPE_YEEPAY_CHARGE = 'YEEPAY_CHARGE';
    const CATE_TYPE_GRANT = 'GRANT';

    const REPAY_TYPE_PRINCIPAL = 1;
    const REPAY_TYPE_INTEREST = 2;

    static $bizTypeNames = [
        self::CATE_TYPE_GRANT => '放款',
        self::CATE_TYPE_LOAN_FEE => '平台手续费',
        self::CATE_TYPE_PAY_SERVICE_FEE => '支付服务费',
        self::CATE_TYPE_CONSULT_FEE => '咨询费',
        self::CATE_TYPE_GUARANTEE_FEE => '担保费',
        self::CATE_TYPE_YEEPAY_CHARGE => '充值',
    ];

    // 需要屏蔽的收款方用户id黑名单
    // 增加黄金的黑名单
    static $receiverBlackList = [
        10429704, 13519, 7307872, 7484723, 7947982, 8161098, 9119098, 9119105,
        9119114, 9119151, 8756674, 10310842, 3655529, 3544204, 3457511,
    ];
    // 需要屏蔽的付款方的资金记录
    static $payerBlackList = [
        10429704, 13519, 7307872, 7484723, 7947982, 8161098, 9119098, 9119105,
        9119114, 9119151, 8756674, 10310842, 3655529, 3544204, 3457511,
    ];

    protected $dbMoved;
    protected $cache;
    protected $dbP2p;
    protected $logFileFormat = '';
    protected $logPath = '';
    protected $start = 0;
    protected $end = 0;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end  = $end;
        $this->dbMoved = \libs\db\Db::getInstance('firstp2p_moved', 'slave');
        $this->dbP2p = \libs\db\Db::getInstance('firstp2p', 'slave');
        $this->cache = \SiteApp::init()->dataCache->getRedisInstance();
        $this->logPath = APP_ROOT_PATH.'/log/transferlog/';
        $this->logFileFormat = $this->logPath.'transferlog_'.$this->start.'_%d.csv';
    }


    public function removeAllRedis()
    {
        $args = $this->cache->keys('getDealGrantTime*');
        if (!empty($args)) {
            $this->cache->del($args);
        }
        $args = $this->cache->keys('getFirstDealGrantTime*');
        if (!empty($args)) {
            $this->cache->del($args);
        }
        $args = $this->cache->keys('isP2pDeal*');
        if (!empty($args)) {
            $this->cache->del($args);
        }
        $args = $this->cache->keys('getFirstDealRepayTime*');
        if (!empty($args)) {
            $this->cache->del($args);
        }
        $args = $this->cache->keys('getDealRepayTime*');
        if (!empty($args)) {
            $this->cache->del($args);
        }
    }

    public function test()
    {
    }

    public function run()
    {
        $logFileCnt = 0;
        // 输出的数据文件格式
        $recordCount = 0;
        $pos = $this->start;
        if (!is_dir($this->logPath))
        {
            mkdir($this->logPath, 0755, true);
        }
        if ($this->end <= 0 || ($this->end > 0 && $this->end < $this->start))
        {
            echo '起止id范围不正确, 起始id'.$this->start . ', 结束id'.$this->end;
            exit;
        }
        do {
            $posEnd = min($pos + 1000, $this->end);
            $sql = "SELECT id,content,create_time FROM firstp2p_finance_queue WHERE id >= $pos AND id <  $posEnd";
            $result = $this->dbMoved->getAll($sql);
            foreach ($result as $record)
            {
                if (empty($record))
                {
                    break;
                }
                $this->parseContent($record['content'], $record['create_time'] + 28800, $recordCount, $logFileCnt);
            }
            $pos = $pos + 1000;
        } while ($pos < $this->end);
    }

    private function parseContent($contentString, $createTime, &$recordCount, &$logFileCnt)
    {
        $arr = unserialize($contentString);
        $orders = json_decode($arr['orders'], 1);
        $data = [];
        // 解析单笔转账批次数据
        foreach ($orders as $order)
        {
            // 根据转账记录biztype 和 cate 重建dealId 和 bizTypeName
            $info = $this->getOrderExtraInfo($order, $createTime);
            if ($info === false)
            {
                continue;
            }
            $createDatetime = date('Y-m-d H:i:s', $createTime);
            // 生成的资金记录明细数据
            $logData = "{$order['outOrderId']},{$info['payerId']},{$info['payerName']},{$order['receiverId']},{$info['receiverName']},{$order['repaymentAmount']},{$info['bizTypeName']},{$info['dealId']},{$createDatetime}".PHP_EOL;
            // 每10万行写一个文件
            if ($recordCount % 100000 == 0)
            {
                 ++ $logFileCnt;
            }
            // 计算写入文件名称
            $logFileFullName = sprintf($this->logFileFormat,$logFileCnt);
            // 写入文件
            file_put_contents($logFileFullName, $logData, FILE_APPEND);
            $recordCount ++;

        }
        return $data;
    }


    /**
     * 取标的扩展资金记录信息
     * @param array $order 转账数据详细
     * @param integer $createTime 转账数据创建时间
     * @return array | false   当返回false 时,表明当前订单不符合导出规则
     */
    private function getOrderExtraInfo($order, $createTime)
    {
        $extraInfo = [];
        // 屏蔽收款方黑名单用户数据
        if (in_array($order['receiverId'], self::$payerBlackList) || in_array($order['payerId'], self::$payerBlackList))
        {
            return false;
        }
        // 取标id相关数据
        $bizTypeInfo = $this->getBizTypeInfo($order['bizType'], $order['cate'], $order['batchId'], $createTime);

        // 检查标类型是否符合条件
        if (!$bizTypeInfo || !$this->isP2pDeal($bizTypeInfo['dealId']))
        {
            return false;
        }

        // 补充用户信息
        $extraInfo['receiverName'] = $this->getUserRealName($order['receiverId']);
        switch ($order['bizType'])
        {
            case self::BIZ_TYPE_REPAY:
            case self::BIZ_TYPE_GRANT:
                $extraInfo['payerId'] = $order['payerId'];
                $extraInfo['payerName'] = $this->getUserRealName($order['payerId']); //$this->dbP2p->getOne("SELECT real_name FROM firstp2p_user WHERE id = {$order['payerId']}");
                $extraInfo['bizTypeName'] = $bizTypeInfo['bizTypeName'];
                $extraInfo['dealId'] = $bizTypeInfo['dealId'];
                break;
            case self::BIZ_TYPE_YEEPAY_CHARGE:
                $extraInfo['payerId'] = '';
                $extraInfo['payerName'] = '';
                $extraInfo['bizTypeName'] = $bizTypeInfo['bizTypeName'];
                $extraInfo['dealId'] = $bizTypeInfo['dealId'];
                break;
            default:
                #echo 'jump order#'.$order['outOrderId'].'bizType@'.$order['bizType'].PHP_EOL;
                return false;
        }
        return $extraInfo;
    }

    /**
     * 根据 bizType , cate,  batchId相关信息 获取 bizTypeName和dealId
     * @param integer $bizType 转账记录业务大类类型
     * @param string $cate cate值 一般是 dealId, projectId, repayId
     * @param string $batchId 转账批次号, 绝大部分情况下是dealId, 部分专享175数据为projectId
     * @param integer $createTime 转账创建时间
     * @return array | false
     */
    private function getBizTypeInfo($bizType, $cate, $batchId, $createTime)
    {
        if (strpos($cate, 'CONSULT_FEE') !== false)
        {
            $cate = 'CONSULT_FEE|'.str_replace(array('CONSULT_FEE', '|'), '', $cate);
        }
        // 放款
        if ($bizType == self::BIZ_TYPE_GRANT )
        {
            // 转账类型中不包含任何费用字段描述时, 认定batchId 就是标id
            if (strpos($cate, 'FEE') === false)
            {
                // 出借人 -> 借款人转账
                return [
                    'bizTypeName' => self::$bizTypeNames[self::CATE_TYPE_GRANT],
                    'dealId' => $batchId,
                ];
            } else {
                // 借款人 -> 各种收费账户 转账
                // 各种费用前收
                // 1. 把batchId 当成 projectId 取项目首标放款时间
                $cateInfo = explode('|', $cate);
                $projectId = $batchId;
                $dealInfo = $this->getFirstDealGrantTime($projectId);
                $dealId = '';
                //var_dump($projectId.'放款 batchId 为项目id, 首标放款时间'.date('YmdHis', $dealInfo['grant_time']).' 实际转账时间'.date('YmdHis', $createTime));
                //如果 首标放款时间在
                if (abs($dealInfo['grant_time'] - $createTime) <= 3600)
                {
                    $dealId = $dealInfo['id'];
                } else {
                    $dealId = $batchId;
                    $dealInfo = $this->getDealGrantTime($dealId);
                    //如果 首标放款时间在
                    //var_dump($dealId.'放款 batchId 为标的id, 放款时间'.date('YmdHis', $dealInfo['grant_time']).' 实际转账时间'.date('YmdHis', $createTime));
                    if (abs($dealInfo['grant_time'] - $createTime) > 3600)
                    {
                        // 够了提前还款传成了放款的类型
                        $projectId = $batchId;
                        $dealInfo = $this->getFirstDealRepayTime($projectId);
                        $dealId = '';
                        //如果 首标最后还款时间在转账的前后1小时内, 则该batchId 为项目id
                        //var_dump($projectId.'还款 batchId 为项目id, 首标末尾还款时间'.date('YmdHis', $dealInfo['repay_time']).' 实际转账时间'.date('YmdHis', $createTime));
                        //如果 首标放款时间在
                        if (abs($dealInfo['repay_time'] - $createTime) <= 3600)
                        {
                            $dealId = $dealInfo['id'];
                        } else {
                            $dealId = $batchId;
                            $dealInfo = $this->getDealRepayTime($dealId);
                            //如果 首标放款时间在
                            //var_dump($dealId.'还款 batchId 为标的id, 首标末尾还款时间'.date('YmdHis', $dealInfo['repay_time']).' 实际转账时间'.date('YmdHis', $createTime));
                            if (abs($dealInfo['repay_time'] - $createTime) > 3600)
                            {
                            // 单个标的还款时间和转账时间相差3600秒以上(一个小时以上) 剔除数据
                            return false;
                            }
                        }
                    }
                }
                return [
                    'dealId' => $dealId,
                    'bizTypeName' => self::$bizTypeNames[$cateInfo[0]],
                ];
            }
        } else if ($bizType == self::BIZ_TYPE_REPAY) {
            // 转账类型中不包含任何费用字段描述时, 认定batchId 就是标id
            //if (strpos($cate, 'FEE') === false)
            //{
            //    return [
            //        'bizTypeName' => self::$bizTypeNames[self::CATE_TYPE_],
            //        'dealId' => $batchId,
            //    ];
            //} else
            if (strpos($cate, 'FEE') !== false) {
                // 各种费用前收
                // 1. 把batchId 当成 projectId 取项目首标最后一次还款时间
                $cateInfo = explode('|', $cate);
                $projectId = $batchId;
                $dealInfo = $this->getFirstDealRepayTime($projectId);
                $dealId = '';
                //如果 首标最后还款时间在转账的前后1小时内, 则该batchId 为项目id
                //var_dump($projectId.'还款 batchId 为项目id, 首标末尾还款时间'.date('YmdHis', $dealInfo['repay_time']).' 实际转账时间'.date('YmdHis', $createTime));
                //如果 首标放款时间在
                if (abs($dealInfo['repay_time'] - $createTime) <= 3600)
                {
                    $dealId = $dealInfo['id'];
                } else {
                    $dealId = $batchId;
                    $dealInfo = $this->getDealRepayTime($dealId);
                    //如果 首标放款时间在
                    //var_dump($dealId.'还款 batchId 为标的id, 首标末尾还款时间'.date('YmdHis', $dealInfo['repay_time']).' 实际转账时间'.date('YmdHis', $createTime));
                    if (abs($dealInfo['repay_time'] - $createTime) > 3600)
                    {
                        // 单个标的还款时间和转账时间相差3600秒以上(一个小时以上) 剔除数据
                        return false;
                    }
                }
                return [
                    'dealId' => $dealId,
                    'bizTypeName' => self::$bizTypeNames[$cateInfo[0]],
                ];
            } else {
                // 各种非还款费用的还款类型区分
                $cateInfo = explode('|', $cate);
                $bizTypeName = '';
                switch ($cateInfo[0])
                {
                    case self::CATE_TYPE_DEALLOANREPAY:
                    case self::CATE_TYPE_DEALLOANREPAYHF:
                    case self::CATE_TYPE_PREDEALLOANPAY:
                        $bizTypeName = '还款'.$this->getBizTypeName($cateInfo[1]);
                        break;
                    case self::CATE_TYPE_PREPAYDEAL:
                    case self::CATE_TYPE_PREPAYHF:
                        $bizTypeName = '提前还款';
                        break;
                    case self::CATE_TYPE_PREPAY_INTEREST:
                    case self::CATE_TYPE_PREPAY_COMPENSATION:
                        $bizTypeName = '提前还款利息';
                        break;
                    default:
                        return false;
                }
                return [
                    'dealId' => $batchId,
                    'bizTypeName' => $bizTypeName,
                ];
            }
        } else if ($bizType == self::BIZ_TYPE_YEEPAY_CHARGE) {
            // 易宝充值
            return [
                'bizTypeName' => self::$bizTypeNames[self::CATE_TYPE_YEEPAY_CHARGE],
                'dealId' => '',
            ];
        }

        return false;
    }


    /**
     * 根据用户id读取用户真实姓名
     * @param integer $userId 用户id
     * @return string
     */
    private function getUserRealName($userId)
    {
        $cacheKey = __FUNCTION__.$userId;
        $val = $this->cache->get($cacheKey);
        if (!empty($val))
        {
            return $val;
        }
        $val = $this->dbP2p->getOne("SELECT real_name FROM firstp2p_user WHERE id = {$userId}");
        $this->cache->set($cacheKey, $val);
        return $val;

    }

    /**
     * 根据项目id获取首标的放款时间 可以缓存
     * @param integer $projectId 项目id
     * @return array
     */
    private function getFirstDealGrantTime($projectId)
    {
        $cacheKey = __FUNCTION__.$projectId;
        $val = json_decode($this->cache->get($cacheKey), 1);
        if (!empty($val))
        {
            return $val;
        }
        $sql = "SELECT id FROM firstp2p_deal WHERE project_id = '{$projectId}' LIMIT 1";
        $dealId = $this->dbP2p->getOne($sql);
        $sql = "SELECT op_time + 28800 as grant_time FROM firstp2p_loan_oplog WHERE deal_id = '{$dealId}' AND op_type IN (0,3)";
        $grantTime = $this->dbP2p->getOne($sql);
        $val = [
            'id' => $dealId,
            'grant_time' => $grantTime,
        ];
        $this->cache->set($cacheKey, json_encode($val));
        return $val;
    }

    /**
     * 根据项目id取首标的最后一次还款时间 可以缓存
     * @param integer $projectId 项目id
     * @return array
     */
    private function getFirstDealRepayTime($projectId)
    {
        $cacheKey = __FUNCTION__.$projectId;
        $val = $this->cache->get($cacheKey);
        if (!empty($val))
        {
            $dealId = $val;
        } else {
            $sql = "SELECT id FROM firstp2p_deal WHERE project_id = '{$projectId}' LIMIT 1";
            //var_dump($sql);
            $dealId = $this->dbP2p->getOne($sql);
            $this->cache->set($cacheKey, $dealId);
        }
        return $this->getDealRepayTime($dealId);
    }

    /**
     * 根据标id查询最后一次还款时间 可以缓存
     * @param integer $dealId 标的id
     * @return array
     */
    private function getDealRepayTime($dealId)
    {
        $cacheKey = __FUNCTION__.$dealId;
        $val = json_decode($this->cache->get($cacheKey), 1);
        if (!empty($val))
        {
            return $val;
        }
        $sql = "SELECT deal_id as id,operation_time+ 28800 AS repay_time FROM firstp2p_deal_repay_oplog WHERE deal_id = '{$dealId}' AND operation_status = 1 ORDER BY id DESC LIMIT 1";
        //var_dump($sql);
        $val = $this->dbP2p->getRow($sql);
        $this->cache->set($cacheKey, json_encode($val));
        return $val;
    }

    /**
     * 根据标id查询放款时间, 可以缓存
     * @param integer $dealId 标的id
     * @return array
     */
    private function getDealGrantTime($dealId)
    {
        $cacheKey = __FUNCTION__.$dealId;
        $val = json_decode($this->cache->get($cacheKey), 1);
        if (!empty($val))
        {
            return $val;
        }
        $sql = "SELECT op_time + 28800 as grant_time FROM firstp2p_loan_oplog WHERE deal_id = '{$dealId}' AND op_type IN (0,3)";
        //var_dump($sql);
        $val = $this->dbP2p->getRow($sql);
        $this->cache->set($cacheKey, json_encode($val));
        return $val;
    }

    /**
     * 根据还款id查询还款类型
     */
    private function getBizTypeName($repayId)
    {
        $sql = "SELECT type FROM firstp2p_deal_loan_repay WHERE id = '{$repayId}'";
        //var_dump($sql);
        $type = $this->dbP2p->getOne($sql);
        if ($type == self::REPAY_TYPE_PRINCIPAL) {
            return '-本金';
        } else if ($type == self::REPAY_TYPE_INTEREST) {
            return '-利息';
        }
    }

    /**
     * 判断标id是不是网贷标的 可以缓存
     */
    private function isP2pDeal($dealId)
    {
        $cacheKey = __FUNCTION__.$dealId;
        $val = $this->cache->get($cacheKey);
        if (!empty($val))
        {
            return (bool)$val;
        }
        $sql = "SELECT COUNT(*) FROM firstp2p_deal_wxid WHERE table_id = '{$dealId}'";
        //var_dump($sql);
        $count = $this->dbP2p->getOne($sql);
        $this->cache->set($cacheKey, $count == 0);
        return $count == 0;
    }
}

$args = ($_SERVER['argv']);
if (count($args) != 3)
{
    $t = new transferlog(-1, 0);
    $t->removeAllRedis();
    echo '参数错误,需要指定启动的开始id和结束id';
    exit;
}

$transferlog = new Transferlog($args[1], $args[2]);
$transferlog->run();
