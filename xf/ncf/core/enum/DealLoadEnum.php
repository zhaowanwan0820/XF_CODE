<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealLoadEnum extends AbstractEnum {

    // 投资最小年龄
    const BID_AGE_MIN = 18;
    // 投资最大年龄
    const BID_AGE_MAX = 70;

    public static $SOURCE_TYPE = array(
        'general'     => 0, //前台正常投标
        'appointment' => 1, //后台预约投标
        'ios'         => 3, //ios客户端
        'android'     => 4, //安卓客户端
        'reservation' => 5, //前台预约投标
        'openapi'     => 6, //openAPI 目前支持即付使用
        'wap'         => 8, //WAP站投资
        'dtb'         => 9, //智多鑫投资
    );
}
