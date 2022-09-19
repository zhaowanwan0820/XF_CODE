<?php
/**
 * 用户信用借款服务类
 * @data 2017.09.13
 * @author weiwei12 weiwei12@ucfgroup.com
 */

namespace core\service\speedLoan;

use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\RequestCommon;
use libs\utils\Rpc;
use libs\utils\Monitor;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;
use NCFGroup\Protos\Creditloan\RequestCreditLoanInfo;
use NCFGroup\Protos\Creditloan\ResponseCreditLoanInfo;
use core\service\speedLoan\UserService as CreditUserService;
use core\service\speedLoan\RepayService as CreditRepayService;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;
use NCFGroup\Protos\Creditloan\Enum\CreditUserEnum;
use NCFGroup\Protos\Creditloan\Enum\CreditRepayEnum;
use core\dao\JobsModel;
use libs\utils\PaymentApi;
use core\service\SupervisionOrderService;

class LoanService extends BaseService
{
    public function applyLoan($params)
    {
        $request = new RequestCommon();
        $request->setVars(['userId'=>$params['userId'], 'loanAmount' => $params['loanAmount'], 'loanDays' => $params['loanDays'], 'orderId' => $params['orderId']]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'applyLoan', $request);
        return $response;
    }

    public function loanNotify($params)
    {
        $request = new RequestCommon();
        $request->setVars(['userId'=>$params['userId'], 'orderId' => $params['orderId'], 'loanStatus' => $params['loanStatus']]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'loanNotify', $request);
        return $response;
    }

    /**
     * 开户申请， 创建记录
     */
    public function createCreditUser($params)
    {
        $request =  new RequestCommon();
        $request->setVars($params);
        return $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'initUserCredit', $request);
    }
    /**
     * 审核申请， 提交身份证资料
     */
    public function updateCreditUser($params, $requestOnly = false)
    {
        if (empty($params['orderId'])) {
            $params['orderId'] = Idworker::instance()->getId();
        }
        $params['applyTime'] = time();
        $userInfo = $params['userInfo'];
        unset($params['userInfo']);
        $dealInfo = $params['dealInfo'];
        unset($params['dealInfo']);
        $bankcardInfo= $params['bankcardInfo'];
        unset($params['bankcardInfo']);

        if (!$requestOnly) {
            $request =  new RequestCommon();
            $request->setVars($params);
            $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'updateUserCredit', $request);
            if (empty($response) || isset($response['errCode']) && $response['errCode'] != 0) {
                throw new \Exception('系统繁忙');
            }
        }

        // 提交审核申请请求
        $applyInfo = [
            'orderId' => $params['orderId'],
            'applyTime' => date('YmdHis', $params['applyTime']),
            'userId' => $params['userId'],
            'realName' => $userInfo['real_name'],
            'certNo' => $userInfo['idno'],
            'mobile' => $userInfo['mobile'],
            'bankCardNo' => $bankcardInfo['bankcard'],
            'lastDate' => $dealInfo['lastDate'],
            'principalAmount' => $dealInfo['principalAmount'],
        ];
        $requestApply = new RequestCommon();
        $requestApply->setVars($applyInfo);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'apply', $requestApply);
        return true;
    }

    /**
     * 审核申请回调
     * @param array $params
     *  string applyNo 即富申请号
     *  string status 申请状态 1成功 99 处理中 0 失败
     *
     * @return creditloan\BaseService::response
     */
    public function authResultNotify($params)
    {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'authResultNotify', $request);
        return $response;
    }


    /**
     * 创建借款记录
     */
    public function createCreditLoan($params)
    {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'createCreditLoan', $request);
        return $response;
    }

    /**
     * 放款结果回调
     * @param array $params
     *
     */
    public function grantResultNotify($params)
    {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'grantResultNotify', $request);
        return $response;
    }


    /**
     * 还款结果回调
     */
    public function repayResultNotify($params)
    {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditRepay', 'repayCallBack', $request);
        return $response;
    }


    public function withdraw($params)
    {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'withdraw', $request);
        return $response;
    }


    public function getUserCreditInfo($userId)
    {
        $request = new RequestCommon();
        $request->setVars(['userId' => $userId]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'getUserCreditInfo', $request);
        $creditUserInfo = $response['data'];
        if (!empty($creditUserInfo['id'])) {
            $usableAmount = bcdiv($creditUserInfo['usable_amount'], 100);
            $usableAmount = $usableAmount - $usableAmount % 100;
            $creditUserInfo['usableAmountFormat'] = number_format($usableAmount, 2);
            $totalAmount = bcdiv($creditUserInfo['total_amount'], 100);
            $totalAmount = $totalAmount - $totalAmount % 100;
            $creditUserInfo['totalAmountFormat'] = number_format($totalAmount, 2);
            $creditUserInfo['isNewApply'] = time() - strtotime(date('Ymd', $creditUserInfo['apply_time']).' 23:59:59') <= 0;
        }
        return $creditUserInfo;
    }

    /**
     * 获取借款列表
     */
    public function getCreditLoanList($params) {
        $request = new RequestCommon();
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'getCreditLoanList', $request);
        $result = $response['data'];
        $list = $result['data'];
        foreach ($list as $key=>$value) {
            $list[$key]['totalAmountFormat'] = number_format(bcdiv($value['totalAmount'], 100, 2), 2); //借款金额
            $list[$key]['principalRepayFormat'] = number_format(bcdiv($value['principalRepay'], 100, 2), 2); //已还本金
            $list[$key]['interestRepayFormat'] = number_format(bcdiv($value['interestRepay'], 100, 2), 2); //已还利息
            $list[$key]['createTimeFormat'] = date('Y年m月d日 H:i:s', $value['createTime']);
        }
        $result['data'] = $list;
        return $result;
    }

    /**
     * 获取待还款列表
     */
    public function getCreditWaitingRepayList($userId, $pageNum = 1, $pageSize = 20) {
        $params = [
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
            'condition' => sprintf('userId = %d AND loanStatus in (%s) ORDER BY id ASC', $userId, CreditLoanEnum::LOAN_STATUS_SUCESS . ',' . CreditLoanEnum::LOAN_STATUS_REPAY),
        ];
        return $this->getCreditLoanList($params);
    }

    /**
     * 获取借款信息
     */
    public function getCreditLoanById($id, $userId) {
        $request = new RequestCommon();
        $params = [
            'id' => $id,
            'userId' => $userId,
        ];
        $request->setVars($params);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'getCreditLoanById', $request);
        $creditLoanInfo = $response['data'];
        if (!empty($creditLoanInfo)) {
            $creditLoanInfo['totalAmountFormat'] = number_format(bcdiv($creditLoanInfo['totalAmount'], 100, 2), 2); //借款金额
            $creditLoanInfo['principalRepayFormat'] = number_format(bcdiv($creditLoanInfo['principalRepay'], 100, 2), 2); //已还本金
            $creditLoanInfo['principalPendingFormat'] = number_format(bcdiv($creditLoanInfo['principalPending'], 100, 2), 2); //还款中本金
            $creditLoanInfo['principalWaiting'] = $creditLoanInfo['totalAmount'] - $creditLoanInfo['principalRepay'] - $creditLoanInfo['principalPending']; //待还本金
            $creditLoanInfo['principalWaitingFormat'] = number_format(bcdiv($creditLoanInfo['principalWaiting'], 100, 2), 2); //已还利息
            $creditLoanInfo['interestRepayFormat'] = number_format(bcdiv($creditLoanInfo['interestRepay'], 100, 2), 2); //已还利息
            $creditLoanInfo['loanProviderDes'] = CreditLoanEnum::$loanProviderDesMap[$creditLoanInfo['loanProvider']];
            $creditLoanInfo['createTimeFormat'] = date('Y-m-d H:i:s', $creditLoanInfo['createTime']);
            $creditLoanInfo['applyTimeFormat'] = date('Y-m-d H:i:s', $creditLoanInfo['applyTime']);
            $creditLoanInfo['loanTimeFormat'] = date('Y-m-d H:i:s', $creditLoanInfo['loanTime']);
            $createDate = new \DateTime(date('Y-m-d', $creditLoanInfo['loanTime']));
            $nowDate = new \DateTime(date('Y-m-d'));
            $intervals = $nowDate->diff($createDate);
            $creditLoanInfo['borrowedDays'] = $intervals->days == 0 ? 1 : $intervals->days; //已借天数 1 1 2 3 4 5 6
            $creditLoanInfo['dailyRateFormat'] = $creditLoanInfo['dailyRate']; //日利率
            $creditLoanInfo['serviceFeeWaiting'] = $creditLoanInfo['serviceFeeAssess'] - $creditLoanInfo['serviceFee'] - $creditLoanInfo['serviceFeeRepay'];//待还款平台服务费
            $creditLoanInfo['serviceFeeWaitingFormat'] = number_format(bcdiv($creditLoanInfo['serviceFeeWaiting'], 100, 2), 2);

            //计算待还本金，请求即富
            $creditUserService = new CreditUserService();
            $userInfo  =$creditUserService->getUserCreditInfo($userId);

            $creditRepaySerivce = new CreditRepayService();
            $reqParams = [
                'userId' => $params['userId'],
                'capPaymentNo' => $creditLoanInfo['outOrderId'],
                'loanAmt' => $creditLoanInfo['principalWaiting'],
                'loanPeriod' => '1',
                'loanPeriodUnit' => '1',
                'repaymentMethod' => CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL,
            ];
            $creditLoanInfo['interestWaiting'] = $creditRepaySerivce->getInterestWaiting($reqParams);
            $creditLoanInfo['interestWaitingFormat'] = number_format(bcdiv($creditLoanInfo['interestWaiting'], 100, 2), 2);

            $creditLoanInfo['repayAmount'] = $creditLoanInfo['principalWaiting'] + $creditLoanInfo['interestWaiting'] + $creditLoanInfo['serviceFeeWaiting']; //待还款金额
            $creditLoanInfo['repayAmountFormat'] = number_format(bcdiv($creditLoanInfo['repayAmount'], 100, 2), 2);
            $creditLoanInfo['repayMoney'] = bcdiv($creditLoanInfo['repayAmount'], 100, 2);//转成元

            $creditLoanInfo['jfUserId'] = $userInfo['data']['jfUserId'];
        }
        return $creditLoanInfo;
    }

    public function getLoanList($userId, $pageNum = 1, $pageSize = 20)
    {
        $request = new RequestCommon();
        $request->setVars(['condition' => " userId =  $userId ", 'pageNum' => $pageNum , 'pageSize' => $pageSize]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'getCreditLoanList', $request);
        $result = ['data' => []];
        $result['totalPage'] = $response['data']['totalPage'];
        $result['totalNum'] = $response['data']['totalNum'];
        if (is_array($response['data']['data']))
        {
            foreach ($response['data']['data'] as $row){
                $row['applyTimeDateFormat'] = date('Y-m-d H:i:s', $row['applyTime']);
                $row['createTime'] = $row['createTime'];
                $row['loanAmtFormat'] = number_format(bcdiv($row['totalAmount'], 100, 2),2);
                $result['data'][] = $row;
            }

        }

        return $result;
    }


    /**
     * 读取信用贷记录信息
     */
    public function getCreditLoanInfoById($loanId)
    {
        $request = new RequestCommon();
        $request->setVars(['id' => $loanId]);
        $loanInfo = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'getLoanInfo', $request);

        // 借款金额格式化
        $loanInfo['totalAmountFormat'] = number_format(bcdiv($loanInfo['totalAmount'], 100), 2);
        $loanInfo['repayAmountFormat'] = number_format(bcdiv($loanInfo['principalRepay']+$loanInfo['principalPending'], 100, 2), 2);
        $loanInfo['loanStatusDesc'] =  CreditLoanEnum::$loanStatusDesMap[$loanInfo['loanStatus']];
        $loanInfo['needRepayPrincipalFormat'] = number_format(bcdiv($loanInfo['totalAmount'] - $loanInfo['principalRepay'] - $loanInfo['principalPending'], 100, 2), 2);
        $dateNow = new \DateTime(date('Y-m-d'));
        if ($loanInfo['loanStatus'] == CreditLoanEnum::LOAN_STATUS_FINISH) {
            $dateNow = new \DateTime(date('Y-m-d', $loanInfo['finishTime']));
        }
        $dateLoan = new \DateTime(date('Y-m-d', $loanInfo['loanTime']));
        $intervals = $dateNow->diff($dateLoan);
        $loanInfo['loanTimeDateFormat'] =  '-';
        $loanInfo['lasts'] = '0';
        $loanInfo['needRepayPrincipalFormat'] = '0.00';
        $loanInfo['serviceFeeFormat'] = number_format(bcdiv($loanInfo['serviceFeeAssess'], 100, 2), 2);
        if ($loanInfo['loanStatus'] >= CreditLoanEnum::LOAN_STATUS_SUCESS && $loanInfo['loanStatus'] != CreditLoanEnum::LOAN_STATUS_FAIL) {
            $loanInfo['loanTimeDateFormat'] =  date('Y-m-d H:i:s', $loanInfo['loanTime']);
            $loanInfo['lasts'] = $intervals->days == 0 ? 1: $intervals->days; // 使用时间 1 1 2 3 4
            $loanInfo['needRepayPrincipalFormat'] = number_format(bcdiv($loanInfo['totalAmount'] - $loanInfo['principalRepay'] - $loanInfo['principalPending'], 100, 2), 2);
        }
        $loanInfo['applyTimeDateFormat'] = date('Y-m-d H:i:s', $loanInfo['applyTime']);
        $loanInfo['dailyRate'] = $loanInfo['dailyRate'];//number_format(bcdiv($loanInfo['da'], 100), 2);
        //$loanInfo['interestFormat'] = number_format(bcdiv($loanInfo['dailyRate'], 100, 4) * $loanInfo['lasts'] * bcdiv($loanInfo['totalAmount'], 100, 2),2);
        $loanInfo['interestRepayFormat'] = number_format(bcdiv($loanInfo['interestRepay'], 100, 2), 2);
        $loanInfo['principalAmountFormat'] = number_format(bcdiv($loanInfo['principalRepay'] + $loanInfo['principalPending'], 100, 2), 2);
        return $loanInfo;
    }

    /**
     * 获取token
     */
    public function getToken()
    {
        $request = new RequestCommon();
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'getToken', $request);
        return $response['data']['token'];
    }

    /**
     * 读取用户借款的还款记录
     */
    public function getCreditRepayList($loanId, $page = 1, $pageSize= 10)
    {
        $request = new RequestCommon();
        $request->setVars([
            'condition' => ' loan_id = '.$loanId.' GROUP BY apply_id ',
            'pageSize' => $pageSize,
            'pageNum' => $page]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditRepay', 'getCreditRepaySumList', $request);
        $result = [];
        if (is_array($response['data']['data']))
        {
            foreach ($response['data']['data'] as $row){
                // 格式化
                $row['createTimeFormat'] = date('Y-m-d H:i:s', $row['createTime']);
                $row['amountFormat'] = number_format(bcdiv($row['amountSum'], 100, 2),2);
                $row['interestFormat'] = number_format(bcdiv($row['interestSum'], 100, 2),2);
                $row['serviceFeeFormat'] = number_format(bcdiv($row['serviceFeeSum'], 100, 2),2);
                if (is_numeric($row['dealId'])) {
                    $dealInfo = (new \core\service\DealService())->getDeal($row['dealId']);
                    $row['dealInfo'] = $dealInfo['name'];
                } else {
                    $row['dealInfo'] = $row['dealId'];
                }
                $row['extraInfo'] ='';
                if(in_array($row['repayStatus'] ,[CreditRepayEnum::REPAY_STATUS_INIT, CreditRepayEnum::REPAY_STATUS_PROCESSING])) {
                    $row['extraInfo'] = "（还款中）";
                }
                $result[] = $row;
            }
        }
        $resp = [
            'data' => $result,
            'totalNum' => $response['data']['totalNum'],
            'totalPage' => $response['data']['totalPage'],
        ];
        return $resp;
    }

    public function withdrawNotify($orderId, $status, $paymentNo = '', $isSv = false)
    {
        $request = new RequestCommon();
        $request->setVars([
            'orderId' => $orderId,
            'status' => $status,
            'paymentNo' => $paymentNo,
        ]);
        $result = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditRepay', 'payCallBack', $request);
        //异步更新存管订单
        if ($isSv && isset($result['errCode']) && $result['errCode'] == 0) {
            $supervisionOrderService = new SupervisionOrderService();
            $orderStatus = $status == '00' ? SupervisionOrderService::NOTICE_SUCCESS : SupervisionOrderService::NOTICE_FAILURE;
            $supervisionOrderService->asyncUpdateOrder($orderId, $orderStatus);
        }
        return isset($result['errCode']) && $result['errCode'] == 0 ? true : false;
    }

    /**
     * 获取用户借款时间
     * @param string $year 年
     * @return integer
     */
    public function getLoanDays($year)
    {
        $dateTime = new \DateTime($year);
        $dateNow = new \DateTime(date('Y-m-d'));
        $interval = $dateTime->diff($dateNow);
        return $interval->days + 10;
    }

    /**
     * 上传到即富sftp
     */
    public function uploadToJF($userId, $idcardPhoto) {
        $request = new RequestCommon();
        $request->setVars([
            'userId'=> intval($userId),
            'idcardPhoto' => $idcardPhoto,
        ]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'uploadToJF', $request);
        return isset($response['errCode']) && $response['errCode'] == 0 ? true : false;
    }

    /**
     * 异步上传到即富sftp
     */
    public function asyncUploadToJF($userId, $idcardPhoto) {
        $jobs_model = new JobsModel();
        $function = '\core\service\speedLoan\LoanService::uploadToJF';
        $param = array($userId, $idcardPhoto);
        $jobs_model->priority = JobsModel::PRIORITY_UPLOADTOJF;
        $r = $jobs_model->addJob($function, $param);
        return $r;
    }

    public function decodeData($data)
    {
        $request = new RequestCommon();
        $request->setVars(['data' => $data]);
        $result = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'decodeData', $request);
        if (empty($result['data'])) {
            return null;
        }
        return $result['data'];
    }

    public function response($data)
    {
        PaymentApi::log('Response data :'.json_encode($data));
        $request = new RequestCommon();
        $request->setVars(['data' => $data]);
        $result = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'responseData', $request);
        header('sign:'.$result['data']['sign']);
        echo $result['data']['respData'];
        return ;
    }

    /**
     * 用户是否有速贷
     * 返回借款次数
     */
    public function userHasLoan($userId)
    {
        $request = new RequestCommon();
        $request->setVars([
            'userId'=> intval($userId),
        ]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditLoan', 'userHasLoan', $request);
        if (empty($response)) {
            throw new \Exception('速贷接口报错');
        }
        if ($response['errCode'] == 0 && $response['data']['loanCount'] > 0) {
            return true;
        }

        return false;
    }
}
