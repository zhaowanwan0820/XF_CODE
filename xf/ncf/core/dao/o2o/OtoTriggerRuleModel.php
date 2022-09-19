<?php

namespace core\dao\o2o;

use core\dao\BaseModel;
use core\enum\O2oEnum;

class OtoTriggerRuleModel extends BaseModel
{
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
    public function getRuleList($status=O2oEnum::STATUS_VALID)
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
            array(':entra_id' => $entraId,':status'=>O2oEnum::STATUS_VALID, ':current_time'=>time())
        );
    }
}
