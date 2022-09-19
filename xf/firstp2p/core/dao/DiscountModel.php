<?php

namespace core\dao;

class DiscountModel extends BaseModel
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

    public function delRecord($userId, $discountId) {
        if (empty($userId) || empty($discountId)) {
            return true;
        }

        $record = $this->findBy('user_id = ' . intval($userId). ' AND discount_id = '.intval($discountId));
        return $record ? $record->remove() : true;
    }
}
