<?php
namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use core\dao\ContractFilesWithNumModel;
use core\dao\FastDfsModel;
use core\service\contract\ContractUtilsService;

class AccountService
{
    public static function mergeP2P($wx, $ph)
    {
        $data = [];
        if (!is_array($wx) || !is_array($ph)) {
            return $wx;
        }

        foreach ($wx as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $wx[$field][$k] = isset($wx[$field][$k]) ? $wx[$field][$k] : 0;
                    $ph[$field][$k] = isset($ph[$field][$k]) ? $ph[$field][$k] : 0;
                    $data[$field][$k] = bcadd($wx[$field][$k], $ph[$field][$k],2);
                }
            } else {
                $data[$field] = bcadd($wx[$field], $ph[$field],2);
            }
        }

        return $data;
    }

    public function getLoanCalendarList($userId, $type = 'api')
    {
        $param = compact('userId', 'type');
        return ApiService::rpc("ncfph", "account/LoanCalendarList", $param);
    }

    public function getSumByYearMonth($userId,$year,$type='api'){
        $param = compact('userId', 'year');
        return ApiService::rpc("ncfph", "account/GetSumByYearMonth", $param);
    }

    public function getDealLoanRepayCalendar($userId, $year, $month, $type = "api")
    {
        $param = compact('userId', 'year', 'month', 'type');
        return ApiService::rpc("ncfph", "account/LoanCalendar", $param);
    }

    public function getUserNoRepayCalendar($userId,$beginYear,$beginMonth,$beginDay,$day)
    {
        $param = compact('userId', 'beginYear', 'beginMonth', 'beginDay', 'day');
        return ApiService::rpc("ncfph", "account/GetUserNoRepayCalendar", $param);
    }

    public function getUserRecentCalendar($userId,$beginYear,$beginMonth,$beginDay,$day)
    {
        $param = compact('userId', 'beginYear', 'beginMonth', 'beginDay', 'day');
        return ApiService::rpc("ncfph", "account/GetUserRecentCalendar", $param);
    }

    public function getLoanCalendarDay($userId, $time)
    {
        $param = compact('userId', 'time');
        return ApiService::rpc("ncfph", "account/LoanCalendarDay", $param);
    }

    public function getSummary($userId)
    {
        return ApiService::rpc("ncfph", "account/Summary", ['userId' => $userId]);
    }

    public function getSummaryExt($userId)
    {
        return ApiService::rpc("ncfph", "account/SummaryExt", ['userId' => $userId]);
    }


    /**
     * @param $userId
     * @param $startTime
     * @param $endTime
     * @param $limit
     * @param string $type
     * @param null $moneyType
     * @param null $repayStatus
     * @param bool $dealType
     * @param int $history  是否读moved 库 1是，0不是
     * @return mixed
     * @throws \Exception
     */
    public function getLoan($userId, $startTime, $endTime, $limit, $type = 'web',$moneyType = null, $repayStatus = null, $dealType = false,$history = 0)
    {
        $param = compact('userId', 'startTime', 'endTime', 'limit', 'type', 'moneyType', 'repayStatus', 'dealType','history');
        $param['limit'] = implode(',', $param['limit']);
        return ApiService::rpc("ncfph", "account/Loan", $param);
    }

    public function getPendingAmount($userId)
    {
        return ApiService::rpc("ncfph", 'account/PendingAmount', ['userId' => $userId]);
    }

    public function getUserStat($userId, $isCache = false, $makeCache = false, $siteId = 1)
    {
        $param = compact('userId', 'isCache', 'makeCache', 'siteId');
        return ApiService::rpc("ncfph", "account/UserStat", $param);
    }

    public function getRepayDealSumaryByTime($userId, $time)
    {
        return ApiService::rpc("ncfph", "account/RepayDealSummaryByTime", ['userId' => $userId, 'time' => $time]);
    }

    public function getTotalLoanMoneyByUserId($userId, $startTime =null, $endTime = null, $dealStatus = [4, 5])
    {
        return ApiService::rpc("ncfph", "account/GetTotalLoanMoney", ['userId' => $userId, 'startTime' => $startTime, 'endTime' => $endTime, 'dealStatus' => implode(',', $dealStatus)]);
    }

    public function getDealLoadCount($userId)
    {
        return ApiService::rpc("ncfph", "account/GetDealLoadCount", ['userId' => $userId]);
    }

    public function getUserInTheLoanCount($userId)
    {
        $count = ApiService::rpc("ncfph", "account/GetUserInTheLoanCount", ['userId' => $userId]);
        return (int) $count;
    }

    /**
     * 获取账户ID
     * 只返回开通或未激活用户
     * @param int $userId 账号Id
     * @param int $accountType 账户类型
     * @return array
     */
    public function getUserAccountId($userId, $accountType)
    {
        $param = compact('userId', 'accountType');
        Logger::info(sprintf('%s, %s, params:%s', __CLASS__, __FUNCTION__, json_encode($param)));
        return ApiService::rpc("ncfph", "account/GetUserAccountId", $param);
    }

    /**
     * 初始化账户
     * 返回账户Id
     * @param int $userId 账号Id
     * @param int $accountType 账户类型
     * @return array
     */
    public static function initAccount($userId, $accountType)
    {
        $param = compact('userId', 'accountType');
        Logger::info(sprintf('%s, %s, params:%s', __CLASS__, __FUNCTION__, json_encode($param)));
        $ret = ApiService::rpc("ncfph", "account/InitAccount", $param);
        return isset($ret['accountId']) ? $ret['accountId'] : false;
    }

    /**
     * 获取账户信息
     * @param int $userId 账号Id
     * @param int $accountType 账户类型
     * @return array
     */
    public function getInfoByUserIdAndType($userId, $accountType, $syncStatus = true)
    {
        $param = [
            'userIds' => $userId,
            'accountTypeList' => $accountType,
            'syncStatus' => $syncStatus,
        ];
        $result = ApiService::rpc("ncfph", "account/GetInfoByUserIdsAndTypeList", $param);
        $key = $userId . '_' . $accountType;
        return !empty($result[$key]) ? $result[$key] : [];
    }

    /**
     * 批量获取账户信息
     * @param array $userIds 账号Id集合
     * @param array $accountTypeList 账户类型集合
     * @param bool $syncStatus 同步存管状态
     * @return array
     */
    public function getInfoByUserIdsAndTypeList($userIds, $accountTypeList, $syncStatus = true)
    {
        $param = [
            'userIds' => implode(',', $userIds),
            'accountTypeList' => implode(',', $accountTypeList),
            'syncStatus' => $syncStatus,
        ];
        return ApiService::rpc("ncfph", "account/GetInfoByUserIdsAndTypeList", $param);
    }

    /**
     * 获取账户信息
     * @param array $accountId 账户Id
     * @return array
     */
    public function getInfoById($accountId, $syncStatus = true)
    {
        $param = [
            'accountIds' => $accountId,
            'syncStatus' => $syncStatus,
        ];
        $result = ApiService::rpc("ncfph", "account/GetInfoByIds", $param);
        return !empty($result[$accountId]) ? $result[$accountId] : [];
    }

    /**
     * 批量获取账户信息
     * @param array $accountIds 账户Id集合
     * @param bool $syncStatus 同步存管状态
     * @return array
     */
    public function getInfoByIds($accountIds, $syncStatus = true)
    {
        $param = [
            'accountIds' => implode(',', $accountIds),
            'syncStatus' => $syncStatus,
        ];
        return ApiService::rpc("ncfph", "account/GetInfoByIds", $param);
    }

    /**
     * 获取账户列表
     * @param int $userId 账号Id
     * @return array
     */
    public function getListByUserId($userId, $syncStatus = true)
    {
        $param = [
            'userIds' => $userId,
            'syncStatus' => $syncStatus,
        ];
        $result = ApiService::rpc("ncfph", "account/GetListByUserIds", $param);
        return !empty($result[$userId]) ? $result[$userId] : [];
    }

    /**
     * 批量获取账户列表
     * @param array $userIds 账号Id集合
     * @return array
     */
    public function getListByUserIds($userIds, $syncStatus = true)
    {
        $param = [
            'userIds' => implode(',', $userIds),
            'syncStatus' => $syncStatus,
        ];
        return ApiService::rpc("ncfph", "account/GetListByUserIds", $param);
    }

    /**
     * 同步账户金额
     */
    public function syncAccountMoney($userId, $accountType) {
        $param = [
            'userId'        => (int) $userId,
            'accountType'   => (int) $accountType,
        ];
        $result = ApiService::rpc("ncfph", "account/syncAccountMoney", $param);
        return !empty($result['ret']) ? true : false;
    }

    /**
     * 根据范围获取账户金额
     * @param int $minId 最小用户id
     * @param int $maxId 最大用户id
     * @param float $minMoney 最小金额 单位元
     * @param float $minMax 最大金额 单位元
     * @return array
     */
    public function getBalanceByRange($minId, $maxId, $minMoney = 0, $maxMoney = 0, $accountType = UserAccountEnum::ACCOUNT_INVESTMENT)
    {
        $param = [
            'minId'        => (int) $minId,
            'maxId'        => (int) $maxId,
            'minMoney'     => bcmul($minMoney, 100),
            'maxMoney'     => bcmul($maxMoney, 100),
            'accountType'   => (int) $accountType,
        ];
        return ApiService::rpc("ncfph", "account/GetBalanceByRange", $param, false, 120);
    }

    /**
     * 操作账户资金记录
     * @param int $orderId 订单号
     * @param int $accountId 账户id
     * @param int $money 金额 元
     * @param string $message 类型
     * @param string $note 备注
     * @param string $note 订单ID
     * @param int $moneyType 见AccountEnum
     * @param int $isAsync 是否异步操作
     */
    public function changeMoney($orderId, $accountId, $money, $message, $note, $moneyType, $isAsync = false, $adminId = 0)
    {
        $param = [
            'orderId' => $orderId,
            'accountId' => (int)$accountId,
            'money' => bcmul($money, 100),
            'message' => $message,
            'note' => $note,
            'moneyType' => $moneyType,
            'isAsync' => $isAsync,
            'adminId' => $adminId,
        ];
        return ApiService::rpc("ncfph", "account/ChangeMoney", $param);
    }

    /**
     * 按账户转账
     * @param int $orderId 订单号
     * @param int $payerId 付款账户ID
     * @param int $receiverId 收款账户ID
     * @param float $money 金额
     * @param string $payerType 付款类型
     * @param string $payerNote 付款备注
     * @param string $receiverType 收款类型
     * @param string $receiverNote 收款备注
     */
    public function transferMoney($orderId, $payerId, $receiverId, $money, $payerType, $payerNote, $receiverType, $receiverNote, $payerAsync = false, $receiverAsync = false)
    {
        $param = [
            'orderId' => $orderId,
            'payerId' => (int)$payerId,
            'receiverId' => (int)$receiverId,
            'money' => bcmul($money, 100),
            'payerType' => $payerType,
            'payerNote' => $payerNote,
            'receiverType' => $receiverType,
            'receiverNote' => $receiverNote,
            'payerAsync' => $payerAsync,
            'receiverAsync' => $receiverAsync,
        ];
        return ApiService::rpc("ncfph", "account/TransferMoney", $param);
    }

    public function getUserLoadList($user_id,$status,$date_start,$date_end,$p=1,$page_size=10){

        $param = compact('user_id','status','date_start','date_end','p','page_size');
        return ApiService::rpc("ncfph", "account/load", $param,false, 10);
    }

    /**
     * 获取账户的还款标的
     * @return array
     */
    public function getRefund($userId,$status,$pageNum,$pageSize){
        $param = compact('userId','status','pageNum','pageSize');
        return ApiService::rpc("ncfph", "account/refund", $param);
    }

    /**
     * 获取账户的还款计划详情
     * @return array
     */
    public function getQuickRefund($userId,$dealId){
        $param = compact('userId','dealId');
        return ApiService::rpc("ncfph", "account/quickrefund", $param);
    }

    /**
     * 检查用户是否是关联用户
     * @param $idno 个人用户为身份证号 企业用户为注册/营业执照号
     * @param $userType 用户类型 0企业 1个人
     * @return int
     */
    public function checkRelatedUser($idno,$userType){
        $param = compact('idno','userType');
        $res =  ApiService::rpc("ncfph", "account/CheckRelatedUser", $param);
        if($res === false) { //请求数据错误
            return -1 ;
        }
        return $res;
    }

    /**
     * 账户总览 -- 用户投资概况 p2p
     *
     * @param $user_id
     * @return array
     */
    public function getInvestOverview($userId){
        $param = compact('userId');
        return ApiService::rpc("ncfph", "account/GetInvestOverview", $param);
    }
    /**
     * 账户总览 -- 回款计划
     *
     * @param $user_id
     * @return array
     */
    public function GetDealRepayOverview($userId){
        $param = compact('userId');
        return ApiService::rpc("ncfph", "account/GetDealRepayOverview", $param);
    }


    /**
     * 合同中心-借款列表
     * web/controllers/account/Contractph.php
     * @param $user_id
     * @return array
     */
    public function getContractDeals($userId, $pageNum, $pageSize, $role, $isP2p)
    {
        $param = compact('userId', 'pageNum', 'pageSize', 'role', 'isP2p');
        // 25秒超时时间
        return ApiService::rpc("ncfph", "account/contract", $param, false, 25);
    }

    /**
     * 合同--根据dealLoadId获取合同信息
     * web/controllers/account/Contractph.php
     * @param $user_id
     * @return array
     */
    public function getContractByDealLoadId($dealLoadId)
    {
        $param = compact('dealLoadId');
        return ApiService::rpc("ncfph", "account/ContractByDealLoadId", $param, false, 25);
    }


    /**
     * 合同中心-某个标的的合同列表
     * web/controllers/account/Contlistph.php
     * @param $user_id
     * @return array
     */
    public function getContractList($dealId, $userId, $role, $pageNum)
    {
        $param = compact('dealId', 'userId', 'role', 'pageNum');
        return ApiService::rpc("ncfph", "account/contlist", $param);
    }

    /**
     * 获取某个用户在某个标中的角色
     * web/controllers/account/Contlistph.php
     * @param $user_id
     * @param $deal_id
     * @return array
     */
    public function getContractRole($dealId, $userId)
    {
        $param = compact('dealId', 'userId');
        return ApiService::rpc("ncfph", "account/contractRole", $param);
    }


    /**
     * 合同中心-某个标的合同内容
     * 并且验证这份合同是否属于这个人
     * web/controllers/account/Contlistshow?tag=show
     * @param $user_id
     * @return array
     */
    public function getContractContent($contract_id, $deal_id, $user_info)
    {
        $param = compact('contract_id', 'deal_id', 'user_info');
        return ApiService::rpc("ncfph", "account/contshow", $param);
    }

    /**
     * 合同中心-签署某个标的合同
     * 并且验证这份合同是否属于这个人
     * web/controllers/account/Contsignajax
     * @param $user_id
     * @return array
     */
    public function contSignAjax($user_id, $deal_id, $role)
    {
        $param = compact('user_id', 'deal_id', 'role');
        return ApiService::rpc("ncfph", "account/contsignajax", $param);
    }

    /**
     * 合同中心-x下载某个标的合同
     * 并且验证这份合同是否属于这个人
     * web/controllers/account/Contlistshow?tag=download
     * @param $user_id
     * @return array
     */
    public function downContract($contract_id, $deal_id, $user_info, $isTsa)
    {
        $contract_info = $this->getContractContent($contract_id, $deal_id, $user_info);
        if (empty($contract_info)) {
            return false;
        }
        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contract_info['number']);
        if (empty($ret) || empty($ret[0])) {
            ContractUtilsService::writeSignLog(sprintf('contract file is signing [contractId:%d]', $contract_id));
        }
        // 合同已打时间戳,则下载打戳的合同，尝试两次
        if (($isTsa === true) && !empty($ret[0])) {
            $fileInfo = $ret[0];
            $dfs = new FastDfsModel();
            $fileContent = $dfs->readTobuff($fileInfo['group_id'], $fileInfo['path']);
            if (!empty($fileContent)) {
                header("Content-type: application/octet-stream");
                header('Content-Disposition: attachment; filename="' . $contract_info['number'] . '.pdf"');
                echo $fileContent;
                exit;
            }
            ContractUtilsService::writeSignLog(sprintf('signed contract file is lost [contractId:%d] 1', $contract_id));

            $fileContent = $dfs->readTobuff($fileInfo['group_id'], $fileInfo['path']);
            if (!empty($fileContent)) {
                header("Content-type: application/octet-stream");
                header('Content-Disposition: attachment; filename="' . $contract_info['number'] . '.pdf"');
                echo $fileContent;
                exit;
            }
            ContractUtilsService::writeSignLog(sprintf('signed contract file is lost [contractId:%d] 2', $contract_id));
        }
        // 下载未打戳的合同
        $file_name = $contract_info['number'] . ".pdf";
        $file_path = APP_ROOT_PATH . 'runtime/' . $file_name;
        if (!file_exists($file_path)) {
            \FP::import("libs.tcpdf.tcpdf");
            \FP::import("libs.tcpdf.mkpdf");
            set_time_limit(300);
            $mkpdf = new \Mkpdf ();
            $mkpdf->mk($file_path, $contract_info['content']);
        }
        header("Content-type: application/pdf");
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header("Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;
    }

    /**
     * 获取普惠用户日充值总金额
     * @param integer $accountId 用户账户id
     * @return floatval
     */
    public static function getDayChargeAmount($accountId)
    {
        $param = compact('accountId');
        return ApiService::rpc("ncfph", "account/getDayChargeAmount", $param);
    }
}

