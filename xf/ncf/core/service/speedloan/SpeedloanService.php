<?php

namespace core\service\speedloan;

use core\service\BaseService;
use libs\utils\Logger;

class SpeedloanService extends BaseService {
    private static $funcMap = array(
        'triggerRepay' => array('userId', 'dealId', 'dealRepayId','dealType', 'dealLoanType', 'dealProductType', 'dealTag', 'dealRepayType'),
        'backendRepayApply' => array('userId', "dealId", 'dealRepayId', 'dealType', 'dealRepayType'),
        'payCallback' => array('orderId', "status", 'assignOrderId'),
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        return self::rpc('speedloan', 'repay/'.$name, $args);
    }

    /**
     * 用户是否有速贷
     */
    public static function userHasLoan($userId) {
        $response = self::rpc('speedloan', 'repay/userHasLoan', ['userId' => $userId]);
        if (empty($response)) {
            Logger::error(__CLASS__  . ',' .  __FUNCTION__ . ',速贷接口报错');
            throw new \Exception('速贷接口报错');
        }
        if ($response['errCode'] == 0 && $response['data']['loanCount'] > 0) {
            return true;
        }
        return false;
    }
}
