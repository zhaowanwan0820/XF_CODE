<?php
namespace NCFGroup\Protos\Gold\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CommonEnum extends AbstractEnum {
    // 活期黄金的标ID
    const GOLD_CURRENT_DEALID = 1;
    //黄金活期投资类型
    const GOLD_CURRENT_TYPE_ID = 0;
    //使用黄金券购买活期黄金类型
    const GOLD_DISCOUNT_TYPE_ID = 1;
    // 满额赠金类型，即系统赠金
    const GOLD_REBATE_TYPE_ID = 2;
    // 活动发放黄金，鑫里有底儿赠金
    const GOLD_EVENT_TYPE1_ID = 3;
    // 活动发放黄金，豪底气赠金
    const GOLD_EVENT_TYPE2_ID = 4;

    // 严重错误告警的key
    const ALARM_PUSH_FATAL_ERROR_KEY = 'gold_exception';


    /**
     * 提金订单状态
     */
    const DELIVER_STATUS_DEFAULT = 0;//默认值
    const DELIVER_STATUS_UNSAVED = 1;//待提交，未落单成功
    const DELIVER_STATUS_SUBMITTED = 2;//已落单，待确认
    const DELIVER_STATUS_CONFIRMED = 3;//审批通过，待发货
    const DELIVER_STATUS_DELIVERED = 4;//已发货，待收货
    const DELIVER_STATUS_FINISH = 5;//收货，已完成
    const DELIVER_STATUS_CANCEL_USER = 10;//用户取消
    const DELIVER_STATUS_CANCEL_ADMIN = 11;//运营取消

}
