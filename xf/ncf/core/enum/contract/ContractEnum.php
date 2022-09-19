<?php
/**
 * 枚举一些合同Model相关的
 */
namespace core\enum\contract;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ContractEnum extends AbstractEnum
{
    const ROLE_BORROWER = 1; //借款人
    const ROLE_LENDER = 2; //出借人
    const ROLE_GUARANTOR = 3; //保证人（已无保证人）
    const ROLE_AGENCY = 3; //担保公司
    const ROLE_ADVISORY = 4;//资产管理方
    const ROLE_ENTRUST = 5;//资产管理方
    const ROLE_CANAL = 6;//渠道方

    // 附件合同标记
    static $tpl_type_tag_attachment = array(
        1   =>  'ATTACHMENT_GR',
        2   =>  'ATTACHMENT_QY',
    );

    const LENGTH_P2P_DEAL_NUMBER_1 = 28; //第一版网贷合同编号长度
    const LENGTH_P2P_DEAL_NUMBER_2 = 30; //第二版网贷合同编号长度
    const LENGTH_P2P_DEAL_NUMBER_3 = 33; //第三版网贷合同编号长度  // 10月26号之后 智多新合同编号长度

    const LENGTH_DT_DEAL_NUMBER = 36; //智多鑫债转合同编号长度
    const LENGTH_DT_CONSULT_NUMBER = 34; //智多鑫顾问合同编号长度

}
