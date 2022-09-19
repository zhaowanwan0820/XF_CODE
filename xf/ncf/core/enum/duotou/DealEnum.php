<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealEnum extends AbstractEnum {
    const DEAL_IS_EFFECT_OPEN = 1;   // 是否可以发起投资 开启
    const DEAL_IS_EFFECT_CLOSE = 0; // 是否可以发起投资 关闭
    const DEAL_IS_SHOW_YES = 1;     // 前台是否显示该标 显示
    const DEAL_IS_SHOW_NO = 0;      // 前台是否显示该标 不显示
}
