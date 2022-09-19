<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MappingLogEnum extends AbstractEnum {
    const STEP_STATUS_BEGIN = 0; // 开始执行
    const STEP_STATUS_SUCCESS = 1; // 已完成

    const MAPPING_STATUS_WAITING = 1; // 匹配未开始
    const MAPPING_STATUS_DOING = 2; // 匹配进行中
    const MAPPING_STATUS_FINISH = 3; // 匹配已结束
//
//    // 匹配任务执行步骤
//    public static $steps = array(
//        'repayRedeemClear',
//        'bidFragments',
//        'redeemMapping',
//        'afterRedeemMapping',
//        'beforeP2pMapping',
//        'doP2pMapping',
//        'afterP2pMapping',
//        'collectLoanMapping'
//    );
}
