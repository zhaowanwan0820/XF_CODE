<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FragmentsEnum extends AbstractEnum {
    const BID_FRAGMENTS_WAIT = 0; // 未匹配
    const BID_FRAGMENTS_FINISH = 1; // 已匹配
}
