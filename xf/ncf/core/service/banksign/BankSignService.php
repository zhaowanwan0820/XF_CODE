<?php
namespace core\service\banksign;

use core\service\BaseService;
use core\service\user\BankService;
use libs\utils\GibberishAES;

class BankSignService extends BaseService {

    const DEC_KEY = 'BANK_SIGN';

    public static function decToken($token){
        $tokenStr = base64_decode($token);
        return GibberishAES::dec($tokenStr,self::DEC_KEY);
    }

    public static function encToken($token){
        $tokenStr = GibberishAES::enc($token,self::DEC_KEY);
        return base64_encode($tokenStr);
    }

    public static function getBankShortName($bankNo){
        $bankInfo = BankService::searchCardBin($bankNo);
        return $bankInfo['shortName'];
    }
}