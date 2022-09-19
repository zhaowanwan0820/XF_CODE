<?php
/**
 * 预约标卡片配置表
 * 废弃
 * @date 2017-01-03
 * @author guofeng3@ucfgroup.com>
 */

namespace core\dao\reserve;

use core\dao\BaseModel;
use core\dao\reserve\UserReservationModel;
use core\enum\ReserveCardEnum;

class ReservationCardModel extends BaseModel
{
    /**
     * 根据自增ID，获取预约卡片
     * @param int $id 自增id
     * @return \libs\db\model
     */
    public function getReserveCardById($id)
    {
        return $this->findBy('`id`=:id', '*', array(':id'=>intval($id)), true);
    }

    /**
     * 根据投资期限，获取预约卡片列表
     * @param int $investLine 投资期限
     * @param int $investUnit 投资期限单位
     * @return \libs\db\model
     */
    public function getReserveCardByInvestLine($investLine, $investUnit,$dealType = null,$id=0)
    {
        if($dealType!==null){
            return $this->findAllViaSlave('`invest_line`=:invest_line AND `invest_unit`=:invest_unit  AND `deal_type` = :deal_type AND  id != :id', true, '*', array(':invest_line'=>intval($investLine), ':invest_unit'=>intval($investUnit),':deal_type'=>intval($dealType),':id'=>intval($id),), true);
        }else{
            return $this->findAllViaSlave('`invest_line`=:invest_line AND `invest_unit`=:invest_unit  AND  id != :id', true, '*', array(':invest_line'=>intval($investLine), ':invest_unit'=>intval($investUnit),':id'=>intval($id),), true);
        }

    }
//getReserveCardCount

    /**
     * 获取预约卡数量
     */
    public function getReserveCardCount($status = ReserveCardEnum::STATUS_VALID)
    {
        $condition = "status = :status";
        $result = $this->count($condition, array(':status' => $status));
        return $result;
    }
    /**
     * 获取预约卡片列表
     * @return \libs\db\model
     */
    public function getReserveCardList($status = ReserveCardEnum::STATUS_VALID, $limit = 0,$offset = 0, $dealTypeList = [])
    {
        $whereParams = ' 1=1 ';
        $whereValues = array();
        if ($status == ReserveCardEnum::STATUS_VALID) {
            $whereParams .= ' AND `status`=:status ';
            $whereValues = array(':status'=>ReserveCardEnum::STATUS_VALID);
        }

        if (!empty($dealTypeList)) {
            $whereParams .= sprintf(' AND `deal_type` in (%s) ', implode(',', $dealTypeList));
        }

        $limitSql = '';
        if (intval($limit)) {
            $offset = intval($offset);
            $limit = intval($limit);
            $limitSql = " LIMIT {$offset}, {$limit} ";
        }

        $sql = $whereParams . 'ORDER BY `update_time` DESC' . $limitSql;

        return $this->findAllViaSlave($sql, true, '*', $whereValues);
    }


    /**
     * 创建预约卡片
     * @param int $investLine 投资期限
     * @param int $investUnit 投资期限单位
     * @param string $buttonName 预约卡片按钮的显示名称
     * @param string $labelBefore 前标签
     * @param string $labelAfter 后标签
     * @param int $displayPeople 是否启用今天预约人数
     * @param int $displayMoney 是否启用累积金额
     * @param int $status 是否有效
     * @return boolean
     */
    public function createReserveCard($investLine, $investUnit, $buttonName, $labelBefore = '', $labelAfter = '', $displayPeople = 0,$displayMoney = 0,$status = 1,$dealType=0,$description='')
    {
        if (empty($investLine) || empty($buttonName)) {
            return false;
        }
        $this->invest_line = intval($investLine); // 投资期限
        $this->deal_type = intval($dealType); // 贷款类型
        $this->invest_unit = intval($investUnit); // 投资期限单位
        $this->button_name = addslashes($buttonName); // 预约卡片按钮的显示名称
        $this->label_before = addslashes($labelBefore); // 前标签
        $this->label_after = addslashes($labelAfter); // 后标签
        $this->display_people = intval($displayPeople); // 是否启用今天预约人数
        $this->display_money = intval($displayMoney); // 是否启用累积金额
        $this->status = intval($status); // 是否有效
        $this->create_time = time(); // 创建时间
        $this->update_time = time(); // 创建时间
        $this->description = $description; // 产品详情
        try {
            $result = $this->save();
            if(!$result) {
                throw new \Exception("create reservation_card failed");
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 更新预约配置信息
     * @param int $id
     * @param int $investLine 投资期限
     * @param int $investUnit 投资期限单位
     * @param string $buttonName 预约卡片按钮的显示名称
     * @param string $labelBefore 前标签
     * @param string $labelAfter 后标签
     * @param int $displayPeople 是否启用今天预约人数
     * @param int $status 是否有效
     * @return boolean
     */
    public function updateReserveCard($id, $investLine, $investUnit, $buttonName, $labelBefore = '', $labelAfter = '', $displayPeople = 0, $displayMoney =0,$status = 1,$dealType=0,$description='')
    {
        $updateParams = array();
        $updateParams['invest_line'] = intval($investLine); // 投资期限
        $updateParams['invest_unit'] = intval($investUnit); // 投资期限单位
        $updateParams['deal_type'] = intval($dealType); // 贷款类型
        $updateParams['button_name'] = addslashes($buttonName); // 预约卡片按钮的显示名称
        $updateParams['label_before'] = addslashes($labelBefore); // 前标签
        $updateParams['label_after'] = addslashes($labelAfter); // 后标签
        $updateParams['display_people'] = intval($displayPeople); // 是否启用今天预约人数
        $updateParams['display_money'] = intval($displayMoney); // 是否启用今天预约人数
        $updateParams['status'] = intval($status); // 是否有效
        $updateParams['update_time'] = time(); // 更新时间
        $updateParams['description'] = $description; // 产品详情
        return $this->updateBy(
            $updateParams,
            sprintf('`id`=%d', intval($id))
        );
    }

    /**
     * 更新卡片状态为失效
     * @param int $investLine 投资期限
     * @param int $investUnit 投资期限单位
     */
    public function cancelReserveCardByInvestLine($investLine, $investUnit)
    {
        if (empty($investLine) || empty($investUnit)) {
            return false;
        }
        return $this->updateBy(
            array(
                'status' => ReserveCardEnum::STATUS_UNVALID,
                'update_time' => time(),
            ),
            sprintf('`invest_line`=%d AND `invest_unit`=%d AND `status`=%d', intval($investLine), intval($investUnit), ReserveCardEnum::STATUS_VALID)
        );
    }


    /**
     * 更新卡片状态为失效
     * @param array $ids
     */
    public function cancelReserveCardByNotInIds($ids)
    {
        if (empty($ids)) {
            return false;
        }
        return $this->updateBy(
            array(
                'status' => ReserveCardEnum::STATUS_UNVALID,
                'update_time' => time(),
            ),
            sprintf('`id`not in ( %s ) AND `status`=%d', implode(',',$ids), ReserveCardEnum::STATUS_VALID)
        );
    }
}
