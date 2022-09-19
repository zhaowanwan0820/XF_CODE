<?php
namespace core\service;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use libs\utils\PaymentApi;
use libs\utils\DBDes;
use core\service\ncfph\AccountService as PhAccountService;
use libs\db\MysqlDb;
use core\dao\UserModel;
use NCFGroup\Common\Library\Idworker;

class  WashUserLogService
{
    const PARTITION_CACHE_KEY = 'wash_log_partition_new_run_%d';

    const DEAL_TYPE_PH = 0; // 普惠系统发生的资金记录
    const DEAL_TYPE_NCFWX = 1; // 网信系统发生的资金记录
    const DEAL_TYPE_ALL = 2; // 查询所有系统发生的资金记录

    public $ncfwx;
    public $wxMoved;
    public $cache;
    public $ncfph;
    public $start = 0;
    public $end = 0;
    public $init;
    public $step = 1000;

    private $tableNameFormat = 'firstp2p_user_log_%d';
    private $identifyNoKeyFormat = 'WASH_USER_LOG_IDENTITY_NO_%d';

    // 针对不存在于ncfph.firstp2p_ifa_user中的用户也需要执行资金记录写入操作, ifa_user_log.status 置为2
    public $userWhiteList = [];


    // 默认查询普惠系统发生的资金记录
    public $queryDealType = self::DEAL_TYPE_PH;

    // 指定查询的资金记录类型
    public $queryLogInfo = [];



    /**
     * api ,  cli
     */
    private $runMode = 'api';

    // 允许的资金记录用户类型
    public $allowUserPurpose = [
        UserAccountEnum::ACCOUNT_INVESTMENT,
        UserAccountEnum::ACCOUNT_FINANCE,
        UserAccountEnum::ACCOUNT_GUARANTEE,
        UserAccountEnum::ACCOUNT_PURCHASE,
        UserAccountEnum::ACCOUNT_REPLACEPAY,
    ];

    // online apiconfig
    public $allowUserLogInfo = [
        '招标成功' => '1|放款',
        '投资放款' => '2|投资',
        '平台手续费' => '3|平台手续',
        '分期咨询费' => '5|交易手续费',
        '咨询费' => '5|交易手续费',
        '担保费' => '5|交易手续费',
        '充值' => '6|充值',
        '提现成功' => '7|提现',
        '还本'  => '8|赎回本金',
        '提前还款本金'  => '8|赎回本金',
        '付息'  => '9|赎回利息',
        '提前还款利息'  => '9|赎回利息',
        '提前还款补偿金'  => '9|赎回利息',
        '使用红包充值'  => '10|红包',
        '红包充值'  => '10|红包',
        '智多鑫-债权出让'  => '11|全部转让',
        '智多新-债权出让'  => '11|全部转让',
        '智多鑫-匹配债权'  => '17|承接',
        '智多新-匹配债权'  => '17|承接',
        '偿还本息'  => '18|还款本金',
        '提前还款'  => '18|还款本金',
        // 根据规则临时生成的loginfo
        '代偿金'    => '27|代偿金',
    ];

    // cli 模式需要关注的资金记录类型
    public $allowUserLogInfoCliMod = [
        '招标成功' => '1|放款',
        '投资放款' => '2|投资',
        '平台手续费' => '3|平台手续',
        '分期咨询费' => '5|交易手续费',
        '咨询费' => '5|交易手续费',
        '担保费' => '5|交易手续费',
        //'充值' => '6|充值',
        //'提现成功' => '7|提现',
        '还本'  => '8|赎回本金',
        '提前还款本金'  => '8|赎回本金',
        '付息'  => '9|赎回利息',
        '提前还款利息'  => '9|赎回利息',
        '提前还款补偿金'  => '9|赎回利息',
        //'使用红包充值'  => '10|红包',
        //'红包充值'  => '10|红包',
        //'智多鑫-债权出让'  => '11|全部转让',
        //'智多新-债权出让'  => '11|全部转让',
        //'智多鑫-匹配债权'  => '17|承接',
        //'智多新-匹配债权'  => '17|承接',
        '偿还本息'  => '18|还款本金',
        '提前还款'  => '18|还款本金',
        // 根据规则临时生成的loginfo
        '代偿金'    => '27|代偿金',
    ];
    /**
     *
     * @param $partition
     * @param $db ncfwx|backup
     */
    public function __construct($partition, $db = 'ncfwx', $start = 0, $runMode = 'api')
    {
        $this->partition = $partition;
        $this->cache = \SiteApp::init()->dataCache->getRedisInstance();
        $this->wxMoved = \libs\db\Db::getInstance('firstp2p_moved', 'slave');
        $this->ncfwx = \libs\db\Db::getInstance('firstp2p', 'slave');
        $this->ncfph = new MysqlDb('w-ncfph.mysql.ncfrds.com', 'ncfph_pro', '9718DFw3165pCF1', 'ncfph');
        //$this->ncfph = new MysqlDb('10.20.69.129', 'tester', 'tester123', 'ncfph');
        $this->tableName = sprintf($this->tableNameFormat, $partition);
        $this->init = $this->restoreFromCache($partition, $db, $start);
        // 初始化结束值
        $this->init['end'] = $this->getEnd();
        // 初始化起始值,订阅模式不需要查询起始值
        $this->runMode = $runMode;
        if ($this->runMode == 'cli')
        {
            // @override 命令行模式跑的用户资金记录类型
            $this->allowUserLogInfo = $this->allowUserLogInfoCliMod;
            $this->init['pos'] = max($start, $this->getStart());
        } else {
            // 代偿机构列表初始化白名单
            //$this->userWhiteList = $this->ncfph->getCol("select user_id from firstp2p_deal_agency where type in (1,6)");
            $this->init['pos'] = $start;
        }
        $this->accountService = new PhAccountService();

    }

    /**
     * 读取2月2号以后的网贷资金记录值
     */
    public function getStart()
    {
        // 读取全量的用户资金记录
        $sql = "SELECT id FROM firstp2p_user_log_{$this->partition} WHERE log_time + 28800 >= UNIX_TIMESTAMP('2016-02-02 19:05:53') LIMIT 1";
        return max(intval($this->wxMoved->getOne($sql)) - 1, 0);
    }

    /**
     * 根据partition检查断点
     */
    public function restoreFromCache($partition, $db, $start)
    {
        $cacheKey = $this->getCacheKey($partition);
        $init = [];
        $init = [
            'pos'    => 0,
            'db'    => 'ncfwx',
        ];
        // 启动时，尝试从缓存中恢复断点
        if ($this->cache->exists($cacheKey))
        {
            $partitionInfo = $this->cache->get($cacheKey);
            $init = array_combine(['db', 'pos'], explode('|', $partitionInfo));
        }
        // 指定断点启动位置时， 强制覆盖断点的位置
        if (!empty($db))
        {
            $init['db'] = $db;
        }
        if (!empty($start))
        {
            $init['pos'] = $start;
        }

        return $init;
    }


    public function getEnd()
    {
        $sql ="SELECT id FROM {$this->tableName} ORDER BY id DESC LIMIT 1";
        if ($this->init['db'] == 'ncfwx')
        {
            // 超时断开连接的问题
            return intval($this->ncfwx->getOne($sql));
        } else if ($this->init['db'] == 'backup') {
            return intval($this->wxMoved->getOne($sql));
        }
    }


    private function getCacheKey($partition)
    {
        return sprintf(self::PARTITION_CACHE_KEY, $partition);
    }

    public function saveCheckPoint()
    {
        $cacheKey = $this->getCacheKey($this->partition);
        $this->cache->setex($cacheKey, 3*86400, $this->init['db'].'|'.$this->init['pos']);
    }

    public function getBySourceCode($dealId)
    {
        $cacheKey = sprintf('WASH_USER_LOG_IFA_DEAL_'.__function__.'%d', $dealId);
        if ($this->cache->exists($cacheKey))
        {
            return json_decode($this->cache->get($cacheKey), true);
        }
        $dealInfo = $this->ncfph->getRow("SELECT sourceProductCode,productName FROM firstp2p_ifa_deal WHERE sourceProductCode = '{$dealId}'");
        $this->cache->setex($cacheKey, 86400, json_encode($dealInfo));
        return $dealInfo;

    }

    public function getInfoById($userId, $extra = true)
    {
        $cacheKey = sprintf('WASH_USER_LOG_'.__function__.'%d', $userId);
        if ($this->cache->exists($cacheKey))
        {
            return json_decode($this->cache->get($cacheKey), true);
        }
        $accountInfo = $this->accountService->getInfoById($userId, $extra);
        $this->cache->setex($cacheKey, 86400, json_encode($accountInfo));
        return $accountInfo;
    }


    public function parseUserLog($userLog)
    {
        // 判断用户类型
        $accountInfo = $this->getInfoById($userLog['user_id'], false);
        $data = [];
        // 用户类型过滤
        if (empty($accountInfo['accountType']) || !in_array($accountInfo['accountType'], $this->allowUserPurpose))
        {
            PaymentApi::log('not in allowUserPurpose, accountInfo:'.json_encode($accountInfo));
            return false;
        }

        // 更改代偿户的资金记录类型
        if ($accountInfo['accountType'] == UserAccountEnum::ACCOUNT_REPLACEPAY && $userLog['log_info'] == '偿还本息')
        {
            PaymentApi::log($accountInfo['accountType'].' changed 偿还本息 to 代偿金');
            $userLog['log_info'] = '代偿金';
        }

        // 判断用户是否已经存在于报备信息表里
        // 如果对于代偿用户不在报备信息表里的用户,则检查是否在白名单中
        $isWhiteUser = in_array($userLog['user_id'], $this->userWhiteList);
        if (!$this->isUserReported($userLog['user_id']) && !$isWhiteUser)
        {
            PaymentApi::log($userLog['user_id'].' not in firstp2p_ifa_user or user white list');
            return false;
        }

        // 解析标的id
        if (isset($userLog['biz_token']))
        {
            $extra = json_decode($userLog['biz_token'], true);
            $dealId = isset($extra['dealId']) ? $extra['dealId'] : -1;
        }
        $dealInfo = [];
        $dealInfo['sourceProductCode'] = '-1';
        $dealInfo['productName'] = '-1';
        if ($dealId != -1)
        {
            // 判断标是否存在上报名单里
            $dealInfo = $this->getBySourceCode($dealId);
            if (empty($dealInfo))
            {
                PaymentApi::log($dealId.' not in firstp2p_ifa_deal');
                return false;
            }
        }
        // 生成主键
        $transId = 'LOG'.$this->partition.'_'.$userLog['id'];
        $transType = $this->getTransTypeInfo($userLog['log_info']);
        // 判断主键是否已经生成
        if ($this->isZdx($transType[0]))
        {
            $cnt = $this->ncfph->getOne("SELECT COUNT(*) FROM firstp2p_ifa_user_log_zdx WHERE transId = '{$transId}'");
        } else {
            $cnt = $this->ncfph->getOne("SELECT COUNT(*) FROM firstp2p_ifa_user_log WHERE transId = '{$transId}'");
        }
        if ($cnt >= 1)
        {
            PaymentApi::log($transId .' processed ');
            return true;
        }

        // 生成需要写入的数据
        $identityNo = $this->getIdentifyNo($userLog['user_id']);
        // 是否网贷资金记录
        $isSupervisionUserLog = 1;
        if ($userLog['deal_type'] != 4)
        {
            $isSupervisionUserLog = 0;
        }

        /**
         * lock_money 与 money 字段可以没传 使用isset处理notice
         *
         * @author by sunxuefeng and from wangqunqiang
         */
        !isset($userLog['lock_money']) && $userLog['lock_money'] = '0.00';
        !isset($userLog['money']) && $userLog['money'] = '0.00';

        $data = [
            'order_id' => Idworker::instance()->getId(),
            'transTime' => date('Y-m-d H:i:s',($userLog['log_time']+28800)),
            'transId'=> $transId,
            'sourceProductCode' => $dealInfo['sourceProductCode'],
            'sourceProductName' => $dealInfo['productName'],
            'transType' => $transType[0],
            'transTypeDec' => $userLog['note'],
            'transMoney' => abs(bcadd($userLog['money'], $userLog['lock_money'], 2)),
            'transDate' => date('Y-m-d',($userLog['log_time']+28800)),
            'userIdcard' => DBDes::encryptOneValue($identityNo),
            'transPayment' => 'a',
            'transBank' => '海口联合农村商业银行股份有限公司',
            'create_time' => time(),
            'isSupervisionUserLog' => $isSupervisionUserLog,
        ];

        return $data;
    }


    /**
     * 检查用户是否已经报备，如果没有报备的用户则不上送资金流水
     */
    public function isUserReported($userId)
    {
        $cacheKey = sprintf("WASH_USER_LOG_USER_REPORTED_%d", $userId);
        if ($this->cache->exists($cacheKey))
        {
            return $this->cache->get($cacheKey);
        }
        $result = 1;
        $cnt = $this->ncfph->getOne("SELECT COUNT(*) FROM firstp2p_ifa_user WHERE userId = '{$userId}'");
        $cnt = intval($cnt);
        if($cnt < 1)
        {
            $result = 0;
        }
        $this->cache->setex($cacheKey, 86400, $result);
        return $result;
    }

    /**
     * 根据log_info 获取对应的type信息
     */
    public function getTransTypeInfo($loginfo)
    {
        $transType = explode('|', $this->allowUserLogInfo[$loginfo]);
        return $transType;
    }

    /**
     * 读取用户证件号， 企业用户返回三证合一
     */
    public function getIdentifyNo($userId)
    {
        $identifyNoKey = sprintf($this->identifyNoKeyFormat, $userId);
        if ($this->cache->exists($identifyNoKey))
        {
            return $this->cache->get($identifyNoKey);
        }
        //取用户信息
        $userInfo = $this->ncfwx->getRow("SELECT idno,user_type FROM firstp2p_user WHERE id = '{$userId}'");
        $identityNo = !empty($userInfo['idno']) ? $userInfo['idno'] : '';
        if ($userInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
        {
            // 企业用户取企业用户组织机构代码证
            $credentialsInfo = $this->ncfwx->getRow("SELECT credentials_no FROM firstp2p_enterprise WHERE user_id = '{$userId}'");
            $identityNo = !empty($credentialsInfo['credentials_no']) ? $credentialsInfo['credentials_no'] : '';
        }
        $this->cache->setex($identifyNoKey, 86400, $identityNo);
        return $identityNo;
    }

    /**
     * cli模式,直接sql 获取数据执行
     * @param string $db 数据实例名称
     * @param string $sql 查询sql
     * @return array
     */
    public function getRecordsBySql($db = 'ncfwx', $sql)
    {
        switch($db)
        {
            case 'ncfph':
                return $this->ncfph->getAll($sql);
                break;
            case 'backup':
                return $this->wxMoved->getAll($sql);
                break;
            default:
                return $this->ncfwx->getAll($sql);
        }
        return [];
    }


    /**
     * 读取列表
     * 2019-04-16 只处理deal_type != 4的存量标的的还款流水
     */
    public function getRecords()
    {
        $initialize = $this->init;
        $db = null;
        $db = $initialize['db'] == 'ncfwx' ? $this->ncfwx : $this->wxMoved;
        //创建sql
        $tableName = sprintf('firstp2p_user_log_%d', $this->partition);
        // 生成查询ID范围
        $idStart = $initialize['pos'];
        $idEnd = min($idStart + $this->step, $this->init['end']);
        $idCondtion = " AND id > {$idStart} AND id <= {$idEnd} ";

        // 资金记录明细类型范围
        $logInfo = array_keys($this->allowUserLogInfo);
        array_pop($logInfo);
        $logInfoCondition = " AND log_info IN ('".implode("','", $logInfo)."') ";

        // 覆盖原来的资金记录查询类型
        if (!empty($this->queryLogInfo))
        {
            $logInfoCondition = " AND log_info IN ('".implode("','", $this->queryLogInfo)."') ";
        }
        PaymentApi::log('washuserlogINFO<partition:'.$this->partition.' pos:'.$this->init['pos'].' end:' .$this->init['end']. ' db: '. $this->init['db']. '> sql:'.$idCondtion);
        $this->incrPos();

        // deal_type
        $dealTypeSubCondition = ' deal_type = 4 ';
        if ($this->queryDealType == self::DEAL_TYPE_NCFWX)
        {
            $dealTypeSubCondition = ' deal_type != 4 ';
        } else if ($this->queryDealType == self::DEAL_TYPE_ALL) {
            $dealTypeSubCondition = '1';
        }
        $sql = "SELECT * FROM {$tableName} WHERE {$dealTypeSubCondition} {$idCondtion} {$logInfoCondition}";
        return $db->getAll($sql);
    }

    public function incrPos()
    {
        $this->init['pos'] += $this->step;
        // 切换数据库
        if ($this->init['pos'] > $this->init['end'])
        {
            $this->init['db'] = 'ncfwx';
            // 激活ncfwx数据库
            $this->ncfwx = \libs\db\Db::getInstance('firstp2p', 'slave');
            // 重新初始化起始位置
            $this->init['pos'] = min($this->init['end'], $this->init['pos']);
            // 初始化结束值
            $this->init['end'] = $this->getEnd();
        }
        $this->saveCheckPoint();
    }


    public function addLog($data)
    {
        /// 智多鑫的单独进智多鑫表
        if ($this->isZdx($data['transType']))
        {
            return $this->ncfph->insert('firstp2p_ifa_user_log_zdx', $data);
        } else {
            return $this->ncfph->insert('firstp2p_ifa_user_log', $data);
        }

    }

    public function setUserWhiteList($list = [])
    {
        if (empty($list))
        {
            return true;
        }
        $this->userWhiteList = $list;
    }

    public function setQueryLogInfo($logInfoList = [])
    {
        if (empty($logInfoList))
        {
            return true;
        }
        $this->queryLogInfo = $logInfoList;
    }

    /**
     * 判断type是否智多鑫
     */
    private function isZdx($type)
    {
        return in_array($type, [11, 17]);
    }


}
