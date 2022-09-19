<?php

namespace core\dao;

class OtoTriggerRuleModel extends BaseModel
{
    /**
    * 触发类型-1:预约结束累计投资额
    * @var int
    */
    const TYPE_ACCUMULATE  = 1;

    /**
    * 触发类型-2:单笔内单次投资成功
    * @var int
    */
    const TYPE_SINGLE  = 2;

    /**
     * 状态-0:无效
     * @var int
     */
    const STATUS_UNVALID = 0;

    /**
    * 状态-1:有效
    * @var int
    */
    const STATUS_VALID = 1;

    /**
     * 礼品类型-1:礼券2:投资券
     * @var int
     */
    const GIFT_TYPE_COUPON = 1;
    const GIFT_TYPE_DISCOUNT = 2;
    
    /**
     * 礼品类型的单位配置(1:礼券2:投资券)
     * @var array
     */
    public static $giftTypeConfig = array(
        self::GIFT_TYPE_COUPON => '礼券',
        self::GIFT_TYPE_DISCOUNT => '投资券',
    );

    public function addRecord($data)
    {
        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }

        return $this->id;
    }

    public function editRecord($data) {
        $this->setRow($data);
        $this->_isNew = false;
        return $this->save();
    }

    /**
     * 获取某条触发规则
     * @param int $id
     */
    public function getTriggerRuleOneById($id)
    {
        return $this->findBy('`id`=:id', '*', array(':id'=>intval($id)), true);
    }

    /**
     * 获取触发规则列表
     * @param int $status
     */
    public function getRuleList($status=self::STATUS_VALID)
    {
        $where = '1=1';
        if ($status >= 0) {
            $where = sprintf('status=%d', intval($status));
        }
        return $this->findAllViaSlave($where . ' ORDER BY `id` DESC', true);
    }

    /**
     * 根据入口，获取触发规则列表
     * @return \libs\db\model
     */
    public function getOtoTriggerRuleListByEntraId($entraId, $type=self::TYPE_ACCUMULATE) {
        return $this->findAllViaSlave('`entra_id` = :entra_id AND `status`=:status AND `use_start_time`<=:current_time AND `use_end_time`>=:current_time', true, '*',
            array(':entra_id' => $entraId,':status'=>self::STATUS_VALID, ':current_time'=>time())
        );
    }
}
