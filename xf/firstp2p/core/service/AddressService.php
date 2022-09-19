<?php
namespace core\service;

/**
 * 用户地址服务
 * @author longbo
 */
use core\dao\UserAddressModel;
use core\dao\DeliveryModel;
use libs\utils\Logger;

class AddressService extends BaseService
{
    const MAX_LIMIT = 5;

    /**
     * 获取全部地址列表
     * @params $user_id
     * @return array
     */
    public function getList($user_id)
    {
        $addrList = UserAddressModel::instance()->getAll(intval($user_id));
        if (empty($addrList) && $oldAddr = $this->syncOldAddress($user_id)) {
            $addrList[] = $oldAddr;
        }

        if (is_array($addrList)) {
            array_walk_recursive($addrList, function(&$v){$v = htmlspecialchars($v);});
            return $addrList;
        } else {
            return array();
        }
    }


    /**
     * 同步旧收货地址
     */
    private function syncOldAddress($user_id)
    {
        try {
            $oldAddr = DeliveryModel::instance()->findByViaSlave('user_id='.intval($user_id));
            $data = [];
            if ($oldAddr) {
                if (!empty($oldAddr['name'])) {
                    $data['consignee'] = $oldAddr['name'];
                }
                if (!empty($oldAddr['address'])) {
                    $data['address'] = $oldAddr['address'];
                }
                if (!empty($oldAddr['area'])) {
                    $data['area'] = $oldAddr['area'];
                }
                if (!empty($oldAddr['mobile'])) {
                    $data['mobile'] = $oldAddr['mobile'];
                }
                if (!empty($oldAddr['postalcode'])) {
                    $data['postcode'] = $oldAddr['postalcode'];
                }
            }
            if ($data) {
                return $this->add($user_id, $data);
            }
        } catch (\Exception $e) {
            Logger::error('synOldAddressErr:' . $e->getMessage());
        }
        return [];
    }

    /**
     * 获取默认地址
     * @params $user_id
     * @return array
     */
    public function getDefault($user_id)
    {
        $res = UserAddressModel::instance()->getAll(intval($user_id), 1);
        return !empty($res) ? reset($res) : [];
    }

    /**
     * 获取单个地址
     * @params $user_id
     * @params $id
     * @return array
     */
    public function getOne($user_id, $id)
    {
        $this->checkUserAddress($user_id, $id);
        $res = UserAddressModel::instance()->findViaSlave(intval($id));
        if ($res) {
            $addr = $res->getRow();
            array_walk_recursive($addr, function(&$v){$v = htmlspecialchars($v);});
            return $addr;
        } else {
            return [];
        }
    }

    /**
     * 获取用户的收货地址
     * @params $user_id
     * @params $id
     * @return array
     */
    public function getAddress($user_id, $id)
    {
        $res = array();
        if (!empty($id)) {
            $res = UserAddressModel::instance()->findViaSlave(intval($id));
        }
        if (empty($res) || ($res['user_id'] != $user_id)) {
            $res = $this->getList($user_id);
            if(empty($res)) {
                return [];
            } else {
                return $res[0];
            }
        }
        return $res;
    }

    /**
     * 添加收货地址
     * @params $user_id
     * @params $data
     * @return array
     */
    public function add($user_id, $data)
    {
        $condition = 'user_id ='.intval($user_id).' and type = '.UserAddressModel::TYPE_DELIVERY;
        $count = UserAddressModel::instance()->countViaSlave($condition, []);
        if ($count >= self::MAX_LIMIT) {
            throw new \Exception('地址最多设置'.self::MAX_LIMIT.'条');
        }
        return UserAddressModel::instance()->add(intval($user_id), $data);
    }

    /**
     * 更新地址
     * @params $user_id
     * @params $data
     * @return array
     */
    public function update($user_id, $id, $data)
    {
        $this->checkUserAddress($user_id, $id);
        return UserAddressModel::instance()->updateData(intval($user_id), intval($id), $data);
    }

    /**
     * 删除地址
     * @params $user_id
     * @params $data
     * @return array
     */
    public function del($user_id, $id)
    {
        $this->checkUserAddress($user_id, $id);
        return UserAddressModel::instance()->del(intval($id));
    }

    /**
     * 检查用户和地址是否同一个人
     */
    private function checkUserAddress($user_id, $id)
    {
        $condition = 'id = '.intval($id).' and user_id = '.intval($user_id);
        $count = UserAddressModel::instance()->countViaSlave($condition, []);
        if (!$count) {
            throw new \Exception('用户该地址不存在');
        }
    }

}


