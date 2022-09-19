<?php
/**
 * 网信理财-修改银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\UserBankcardService;


class WxUpdateUserBankCardEvent extends GlobalTransactionEvent {
    /**
     * 参数列表
     * @var array
     */
    private $data;

    /**
     *
     * @param array $data
     *      string id 用户id 必填
     *      string bank_card_name 持卡人姓名 必填
     *      string c_region_lv1 开户行坐在地 国家 可空
     *      string c_region_lv2 开户行坐在地 省 可空
     *      string c_region_lv3 开户行坐在地 市 可空
     *      string c_region_lv4 开户行坐在地 区县 可空
     *      string bankzone_1 开户行名称 手填版本 可空
     *      string bank_bankzone 开户行名称 下拉选择版本 可空
     *      string bank_bankcard 银行卡号 必填
     *      string bank_id 银行编号 bank_id  和 bankcode 二选一 必填
     *      string bankcode 银行短码 bank_id  和 bankcode 二选一  必填
     *      string card_type 银行卡类型 可空默认对私
     *      string bankcard_id 用户银行卡记录id  可空表示新增绑卡，不为空则更新
     *      string cert_status 用户验卡方式 可空 不为空时，更新用户的绑卡状态和认证方式以及验卡状态
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * 网信理财-修改银行卡
     */
    public function commit() {
        $userBankcrdService = new UserBankcardService();
        $userBankcrdService->wxUpdateUserBankCard($this->data);
        return true;
    }
}
