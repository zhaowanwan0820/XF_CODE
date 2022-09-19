<?php
/**
 * 预约资金分配比例
 * @date 2017-10-25
 * @author weiwei12@ucfgroup.com>
 */

namespace core\dao\reserve;

use core\dao\BaseModel;

class ReservationMoneyAssignRatioModel extends BaseModel
{
    /**
     * 获取资金分配比例
     * @param int $deadline 投资期限
     * @param int $deadlineUnit 投资期限单位
     * @param int $typeId 资产类型
     */
    public function getMoneyAssignRatio($deadline, $deadlineUnit)
    {
        $condition = sprintf("`invest_deadline` = '%d' and `invest_deadline_unit` = '%d' and is_effect = 1 order by money_limit desc", intval($deadline), intval($deadlineUnit));
        $ret = $this->findAllViaSlave($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;

    }

    /**
     * 获取资金分配比例列表
     */
    public function getMoneyAssignRatioList()
    {
        $condition = '';
        $ret = $this->findAllViaSlave($condition);
        return $ret;
    }

    /**
     * 添加资金分配比例
     * @return boolean
     */
    public function addMoneyAssignRatio($params)
    {
        if (empty($params)) {
            return false;
        }
        $data = array(
            'type_id'               => (int) $params['typeId'],
            'invest_deadline'       => (int) $params['deadline'],
            'invest_deadline_unit'  => (int) $params['deadlineUnit'],
            'money_ratio'            => addslashes($params['moneyRatio']),
            'money_limit'            => (float) $params['moneyLimit'],
            'is_effect'             => intval($params['isEffect']),
            'remark'                => addslashes($params['remark']),
            'create_time'           => time(),
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }

    }

    /**
     * 更新资金分配比例
     * @return boolean
     */
    public function updateMoneyAssignRatio($id, $updateParams)
    {
        if (empty($id) || empty($updateParams)) {
            return false;
        }
        $updateParams = [
            'type_id'               => (int) $updateParams['typeId'],
            'invest_deadline'       => (int) $updateParams['deadline'],
            'invest_deadline_unit'  => (int) $updateParams['deadlineUnit'],
            'money_ratio'            => addslashes($updateParams['moneyRatio']),
            'money_limit'            => (float) $updateParams['moneyLimit'],
            'is_effect'             => intval($updateParams['isEffect']),
            'remark'                => addslashes($updateParams['remark']),
            'update_time'           => time(),
        ];
        return $this->updateBy(
            $updateParams,
            sprintf('`id`=%d', intval($id))
        );
    }

    /**
     * 删除资金分配比例
     * @return boolean
     */
    public function deleteMoneyAssignRatio($id)
    {
        $sql = "delete from " . $this->tableName() . " where id='%d'";
        $sql = sprintf($sql, $id);
        return $this->execute($sql);
    }
}
