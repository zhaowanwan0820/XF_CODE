<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ArticleCateEnum extends AbstractEnum
{
    // 网信app
    const PLATFORM_WXAPP = 'wxapp';

    // 网信pc
    const PLATFORM_WXPC = 'wxpc';

    // 普惠app
    const PLATFORM_PHAPP = 'phapp';

    // 普惠pc
    const PLATFORM_PHPC = 'phpc';

    // 企业pc
    const PLATFORM_QYPC = 'qypc';

    // 企业app
    const PLATFORM_QYAPP = 'qyapp';
}
