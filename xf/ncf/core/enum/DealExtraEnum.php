<?php
namespace core\enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealExtraEnum extends AbstractEnum {

    const RECOURSE_TYPE_LAWSUIT = 1; // 争议解决方式-诉讼
    const RECOURSE_TYPE_ARBITRATE = 2; // 争议解决方式-仲裁

    /**
     * 争议解决方式-在合同中显示的文本
     *
     * @var string
     **/
    static public $RECOURSE_TYPE_CONTRACT = array(
        self::RECOURSE_TYPE_LAWSUIT => 'A',
        self::RECOURSE_TYPE_ARBITRATE => 'B',
    );
}
