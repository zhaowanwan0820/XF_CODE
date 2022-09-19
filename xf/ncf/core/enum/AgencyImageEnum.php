<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AgencyImageEnum extends AbstractEnum {

    // 机构相关图片类型（对应agencyImage表type）
    CONST AGENCY_IMAGE_TYPE_LOGO            = 1; // logo
    CONST AGENCY_IMAGE_TYPE_LICENSE         = 2; // 营业执照
    CONST AGENCY_IMAGE_TYPE_BUSINESS_PLACE  = 3; // 经营场所图
    CONST AGENCY_IMAGE_TYPE_SIGN            = 4; // 电子签章
}
