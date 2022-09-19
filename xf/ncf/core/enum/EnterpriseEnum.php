<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class EnterpriseEnum extends AbstractEnum {


    /**
     * enterprise表中字段名称 - company_name
     * @var string
     */
    const TABLE_FIELD_COMPANY_NAME = 'company_name';

    const VERIFY_STATUS_NO_INFO         = 1; //信息未填写
    const VERIFY_STATUS_HAS_INFO        = 2; //信息已填写
    const VERIFY_STATUS_FIRST_PASS      = 3; //初审通过
    const VERIFY_STATUS_PASS            = 4; //审核通过
    const VERIFY_STATUS_FAILED          = 5; //审核失败

}
