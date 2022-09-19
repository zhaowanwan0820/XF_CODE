<?php
/**
 * 合同分类枚举
 */
namespace NCFGroup\Protos\Contract\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ContractCategoryEnum extends AbstractEnum
{
    // 业务类型
    const BUSINESS_TYPE_DEAL = 0;
    const BUSINESS_TYPE_PROJECT = 1;

    const CATEGORY_IS_DLETE_NO = 0;
    const CATEGORY_IS_DLETE_YES = 1;

    const USE_STATUS_NOW = 1;
    const USE_STATUS_HISTORY = 0;
}
