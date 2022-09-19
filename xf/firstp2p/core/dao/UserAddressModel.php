<?php
/**
 * 用户地址管理
 * @author longbo
 */

namespace core\dao;

use libs\utils\Logger;

class UserAddressModel extends BaseModel
{
    const TYPE_DELIVERY = 0;

    public function getAll($user_id, $isDefault = 0)
    {
        $condition = 'user_id = :user_id and type = :type';
        if ($isDefault == 1) {
            $condition .= ' and is_default = 1';
        }
        $condition .= ' ORDER BY is_default DESC, update_time DESC ';
        $params = [':user_id' => intval($user_id), ':type' => self::TYPE_DELIVERY];
        if ($res = $this->findAllViaSlave($condition, true, '*', $params)) {
            return $res;
        }
        return false;
    }

    public function add($user_id, $data)
    {
        if (empty($user_id)) {
            throw new \Exception('缺少用户项');
        }
        if (!($data['area'] && $data['address'] && $data['consignee'] && $data['mobile'])) {
            throw new \Exception('地址信息缺少必填项');
        }
        $addCols = array(
            'user_id' => $user_id,
            'type' => self::TYPE_DELIVERY,
            'create_time' => time(),
            'update_time' => time(),
            'area' => $this->escape($data['area']),
            'address' => $this->escape($data['address']),
            'consignee' => $this->escape($data['consignee']),
            'mobile' => $this->escape($data['mobile']),
        );

        if (isset($data['postcode'])) {
            $addCols['postcode'] = $this->escape($data['postcode']);
        }

        $this->db->startTrans();
        try {
            if (isset($data['is_default'])) {
                $addCols['is_default'] = intval($data['is_default']);
                if ($addCols['is_default'] == 1) {
                    $this->updateAll(['is_default' => 0], 'user_id = '.$user_id);
                }
            } else {
                $data['is_default'] = 0;
            }

            $this->setRow($addCols);
            $this->insert();
            $this->db->commit();
            return $this->getRow();
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error("Add Address Failed:".$e->getMessage());
            throw new \Exception('添加失败');
        }
        return false;
    }

    public function updateData($user_id, $id, $data)
    {
        if (empty($id) || empty($data)) {
            throw new \Exception('地址信息缺少');
        }
        $this->db->startTrans();
        try {
            if (isset($data['is_default']) && $data['is_default'] == 1) {
                $this->updateAll(['is_default' => 0], 'user_id = '.$user_id);
            }
            $data['update_time'] = time();
            $res = $this->updateAll($data, 'id = '.$id);
            $this->db->commit();
            return $res;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error("update Address Failed:".$e->getMessage());
            throw new \Exception('更新失败');
        }
        return false;
    }

    public function del($id)
    {
        if (empty($id)) {
            return false;
        }
        $this->setRow(['id' => intval($id)]);
        return $this->remove();
    }


}
