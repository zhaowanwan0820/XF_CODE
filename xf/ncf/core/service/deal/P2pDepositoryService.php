<?php
/**
 * p2p存管 基础service
 */

namespace core\service\deal;
use core\enum\UserAccountEnum;
use core\service\BaseService;

class P2pDepositoryService extends BaseService {


    //存管资金类型C：平台佣金 P：本金 I：利息 S：分润，YR：银信通还款
    // 产品给出的解释是 平台佣金是给自身平台的费用。分润给的是第三方的费用
    public function getP2pMoneyType($type)
    {
        $moneyType = array(
            "平台手续费" => "S",
            "担保费" => "S",
            "咨询费" => "S",
            "支付服务费" => "S",
            "管理服务费" => "S",
            "渠道服务费" => "S",
            "逾期罚息" => "I",
            "银信通贷款冻结" => "YR",
            "偿还本金" => "P",
            "平台管理费" => "S",
            "提前还款本金" => "P",
            "提前还款补偿金" => "I",
            "提前还款利息" => "I",
            "付息" => "I",
            "顾问服务费" => "I",
        );
        return isset($moneyType[$type]) ? $moneyType[$type] : "";
    }
}
