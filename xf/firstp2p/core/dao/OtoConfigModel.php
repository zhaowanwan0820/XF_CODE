<?php
namespace core\dao;

/**
 * Jobs class
 * 后台任务类
 **/
class OtoConfigModel extends BaseModel
{

    static $events = array(
        'register' => array(
            'eventName' => 'register',
            'Name' => '注册后触发',
        ),
        'bindBankCard' => array(
            'eventName' => 'bindBankCard',
            'Name' => '首次绑定银行卡后触发',
        ),
        'firstLoan' => array(
            'eventName' => 'firstLoan',
            'Name' => '首次投资后触发',
        ),
        'makeLoan' => array(
            'eventName' => 'makeLoan',
            'Name' => '复投后触发',
        ),

    );

    /**
     * 根据邀请码和事件名称读取需要打的tag名称
     * @param string $eventName 事件名称
     * @param string $couponCode 邀请码
     * @return string
     */
    public function findAvailable($eventName, $couponCode = null, $inviterGroupId = 0) {
        $condition = sprintf("eventName = '%s' ", $this->escape($eventName)) . " AND isEffective = 1 ";
        $rules = $this->findAll($condition);
        $info = array();
        $info['tags'] = '';
        $info['needTransferGroup'] = false;
        foreach ($rules as $rule) {
            if($rule['groupId'] && $rule['couponCode']) {
                // 判断couponCode 的 GroupId 是否在rule.GroupId 
                if ($rule['groupId'] == $inviterGroupId && $rule['couponCode'] == $couponCode) {
                    $info['tags'] .= $rule['tagConstant'];
                    $info['toGroupId'] = $rule['toGroupId'];
                    $info['toCouponLevelId'] = $rule['toCouponLevelId'];
                }
            }
            else if ($rule['groupId'] && $rule['groupId'] == $inviterGroupId) {
                $info['tags'] .= $rule['tagConstant'];
                $info['toGroupId'] = $rule['toGroupId'];
                $info['toCouponLevelId'] = $rule['toCouponLevelId'];
            }
            else if ($rule['couponCode'] && $rule['couponCode'] == $couponCode) {
                $info['tags'] .= $rule['tagConstant'];
                $info['toGroupId'] = $rule['toGroupId'];
                $info['toCouponLevelId'] = $rule['toCouponLevelId'];
            }
        }
        \libs\utils\PaymentApi::log('读取要打的tag结果:'.var_export($info, true));
        return $info;
    }

}
