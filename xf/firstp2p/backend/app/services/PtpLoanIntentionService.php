<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RequestLoanIntention;
use NCFGroup\Protos\Ptp\ResponseLoanIntention;
use core\service\LoanIntentionService;

class PtpLoanIntentionService extends ServiceBase {

    public function checkInviteCode(RequestLoanIntention $request) {
        $inviteCode = $request->getInviteCode();
        $userInfo = array("id" => $request->getUserId());

        $loanIntentionService = new LoanIntentionService();
        $checkRes = $loanIntentionService->checkQualification($userInfo, $inviteCode);

        $response = new ResponseBase();
        $response->resCode = $checkRes['errno'];
        $response->resMsg  = $checkRes['errmsg'];
        $response->resExt  = $checkRes['ext'];

        $maxMoney = $loanIntentionService->getXFDMaxMoney();
        $response->maxMoney = $maxMoney;
        return $response;
    }

    public function addLoanIntention(RequestLoanIntention $request) {
        $userInfo = array("id" => $request->getUserId());
        $saveData = array(
            "money" => $request->getMoney(),
            "time"  => $request->getTime(),
            "phone" => $request->getPhone(),
            "addr"  => $request->getAddr(),
            "company"  => $request->getCompany(),
            "wl"  => $request->getWl(),
            "code"  => $request->getCode(),
        );

        $loanIntentionService = new LoanIntentionService();
        $addRes = $loanIntentionService->addNewIntention($userInfo, $saveData);

        $response = new ResponseBase();
        $response->resCode = $addRes['errno'];
        $response->resMsg  = $addRes['errmsg'];
        return $response;
    }

}
