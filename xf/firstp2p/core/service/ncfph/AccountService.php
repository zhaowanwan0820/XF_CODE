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
     * @param int $history  ?????????moved ??? 1??????0??????
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
     * ????????????ID
     * ?????????????????????????????????
     * @param int $userId ??????Id
     * @param int $accountType ????????????
     * @return array
     */
    public function getUserAccountId($userId, $accountType)
    {
        $param = compact('userId', 'accountType');
        Logger::info(sprintf('%s, %s, params:%s', __CLASS__, __FUNCTION__, json_encode($param)));
        return ApiService::rpc("ncfph", "account/GetUserAccountId", $param);
    }

    /**
     * ???????????????
     * ????????????Id
     * @param int $userId ??????Id
     * @param int $accountType ????????????
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
     * ??????????????????
     * @param int $userId ??????Id
     * @param int $accountType ????????????
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
     * ????????????????????????
     * @param array $userIds ??????Id??????
     * @param array $accountTypeList ??????????????????
     * @param bool $syncStatus ??????????????????
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
     * ??????????????????
     * @param array $accountId ??????Id
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
     * ????????????????????????
     * @param array $accountIds ??????Id??????
     * @param bool $syncStatus ??????????????????
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
     * ??????????????????
     * @param int $userId ??????Id
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
     * ????????????????????????
     * @param array $userIds ??????Id??????
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
     * ??????????????????
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
     * ??????????????????????????????
     * @param int $minId ????????????id
     * @param int $maxId ????????????id
     * @param float $minMoney ???????????? ?????????
     * @param float $minMax ???????????? ?????????
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
     * ????????????????????????
     * @param int $orderId ?????????
     * @param int $accountId ??????id
     * @param int $money ?????? ???
     * @param string $message ??????
     * @param string $note ??????
     * @param string $note ??????ID
     * @param int $moneyType ???AccountEnum
     * @param int $isAsync ??????????????????
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
     * ???????????????
     * @param int $orderId ?????????
     * @param int $payerId ????????????ID
     * @param int $receiverId ????????????ID
     * @param float $money ??????
     * @param string $payerType ????????????
     * @param string $payerNote ????????????
     * @param string $receiverType ????????????
     * @param string $receiverNote ????????????
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
     * ???????????????????????????
     * @return array
     */
    public function getRefund($userId,$status,$pageNum,$pageSize){
        $param = compact('userId','status','pageNum','pageSize');
        return ApiService::rpc("ncfph", "account/refund", $param);
    }

    /**
     * ?????????????????????????????????
     * @return array
     */
    public function getQuickRefund($userId,$dealId){
        $param = compact('userId','dealId');
        return ApiService::rpc("ncfph", "account/quickrefund", $param);
    }

    /**
     * ?????????????????????????????????
     * @param $idno ??????????????????????????? ?????????????????????/???????????????
     * @param $userType ???????????? 0?????? 1??????
     * @return int
     */
    public function checkRelatedUser($idno,$userType){
        $param = compact('idno','userType');
        $res =  ApiService::rpc("ncfph", "account/CheckRelatedUser", $param);
        if($res === false) { //??????????????????
            return -1 ;
        }
        return $res;
    }

    /**
     * ???????????? -- ?????????????????? p2p
     *
     * @param $user_id
     * @return array
     */
    public function getInvestOverview($userId){
        $param = compact('userId');
        return ApiService::rpc("ncfph", "account/GetInvestOverview", $param);
    }
    /**
     * ???????????? -- ????????????
     *
     * @param $user_id
     * @return array
     */
    public function GetDealRepayOverview($userId){
        $param = compact('userId');
        return ApiService::rpc("ncfph", "account/GetDealRepayOverview", $param);
    }


    /**
     * ????????????-????????????
     * web/controllers/account/Contractph.php
     * @param $user_id
     * @return array
     */
    public function getContractDeals($userId, $pageNum, $pageSize, $role, $isP2p)
    {
        $param = compact('userId', 'pageNum', 'pageSize', 'role', 'isP2p');
        // 25???????????????
        return ApiService::rpc("ncfph", "account/contract", $param, false, 25);
    }

    /**
     * ??????--??????dealLoadId??????????????????
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
     * ????????????-???????????????????????????
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
     * ??????????????????????????????????????????
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
     * ????????????-????????????????????????
     * ?????????????????????????????????????????????
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
     * ????????????-????????????????????????
     * ?????????????????????????????????????????????
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
     * ????????????-x????????????????????????
     * ?????????????????????????????????????????????
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
        // ?????????????????????,???????????????????????????????????????
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
        // ????????????????????????
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
     * ????????????????????????????????????
     * @param integer $accountId ????????????id
     * @return floatval
     */
    public static function getDayChargeAmount($accountId)
    {
        $param = compact('accountId');
        return ApiService::rpc("ncfph", "account/getDayChargeAmount", $param);
    }
}

