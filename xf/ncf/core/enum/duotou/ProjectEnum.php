<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ProjectEnum extends AbstractEnum {

    const PROJECT_STATUS_WAIT  = 0; // 等待确认
    const PROJECT_STATUS_GOING = 1; // 进行中
    const PROJECT_STATUS_CLEAN = 2; // 清盘
    const PROJECT_STATUS_DEL   = 3; // 删除
    const PROJECT_STATUS_CLEANING = 4; // 清盘中

    const PROJECT_IS_EFFECT_OPEN = 1;   // 是否可以发起投资 开启
    const PROJECT_IS_EFFECT_CLOSE = 0; // 是否可以发起投资 关闭
    const PROJECT_IS_SHOW_YES = 1;     // 前台是否显示该标 显示
    const PROJECT_IS_SHOW_NO = 0;      // 前台是否显示该标 不显示

    /**
     * 结息日
     * @var array
     */
    public static $interestDay = array(
        1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28
    );

    /**
     * 项目状态
     * @var array
     */
    public static $status = array(
        self::PROJECT_STATUS_WAIT,
        self::PROJECT_STATUS_GOING,
        self::PROJECT_STATUS_CLEAN,
        self::PROJECT_STATUS_CLEANING,
        self::PROJECT_STATUS_DEL
    );
}
