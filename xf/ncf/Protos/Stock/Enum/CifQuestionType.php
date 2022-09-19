<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CifQuestionType extends AbstractEnum
{
    const QTYPE_SINGLE = '0';//单选题
    const QTYPE_MULTI = '1';//多选题
}
