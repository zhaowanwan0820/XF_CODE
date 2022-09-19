<?php

namespace core\service\contract;

use core\service\BaseService;
use core\dao\contract\DealContractModel;
use core\enum\contract\ContractServiceEnum;

use libs\utils\Logger;
/**
 * 前置合同相关接口
 */
class ContractBeforeBorrowService extends BaseService {

    private static $funcMap = array(
        /**
         * 生成前置合同记录(一个ApproveNumber对应唯一一条合同记录)
         * @param `approve_number`  '放款审批单号',
         * @param `borrow_user_id`  '借款人ID',
         * @param `borrower_sign_time` '借款人签署时间',
         * @param `category_id`  '合同分类id',
         * @param `params` '合同参数',
         * @return int id
         */
        'insertBeforeBorrowContract' => array('approveNumber','borrowUserId','categoryId','params'),
        /**
         * 根据approveNumber获取前置临时合同记录
         * @param approveNumber
         * @return array
         */
        'getContractByApproveNumber' => array('approveNumber'),
        /**
         * 根据id获取前置临时合同记录
         * @param id 主键
         * @return array
         */
        'getContractById' => array('id'),
        /**
         * 根据合同id获取前置合同模板列表
         * @param contractId
         * @return array
         */
        'getTplByContractId' => array('contractId'),
        /**
         * 根据合同id签署前置合同
         * @param id 主键
         * @return array
         */
        'signBeforeBorrowContract' => array('id','borrowerSignTime'),

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

        return self::rpc('contract', 'contractBeforeBorrow/'.$name, $args);
    }

}