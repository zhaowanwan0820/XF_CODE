<?php
/**
 * EnterpriseContactModel class file
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

class EnterpriseContactModel extends BaseModel {
    /**
     * [根据企业用户id，获取企业用户信息]
     * @author <liwenling@ufcgroup.com>
     * @param int $userID
     * @return model [有实体就返回对象，否则返回空]
     */
    public function getEnterpriseInfoByUserID($userID)
    {
        $cond = "`user_id` = " . intval($userID);
        return self::findByViaSlave($cond);
    }

    /**
     * 更新企业用户信息
     * @param $userId
     * @param $params
     * @return bool
     */
    public function updateByUid($userId, $params)
    {
        $info = $this->findBy(sprintf("user_id = '%s'", $userId));
        if($info && is_array($params)){
            return $info->update($params);
        }
        return false;
    }

    /**
     * 获取企业用户接受短信手机号码
     * @param $userId
     */
    public function getReceiveMobileByUserId($userId) {
        $cond = "`user_id` = " . intval($userId);
        $info = self::findByViaSlave($cond,'receive_msg_mobile');
        $receive_msg_mobile = self::_receiveUnique($info['receive_msg_mobile']);
        return $receive_msg_mobile;
    }

    /**
     * 根据联系人手机号获取企业联系人信息
     * @param $userId
     */
    public function getEnterpriseContactByMobile($mobile) {
        $cond = "`consignee_phone` = :mobile OR `major_mobile` = :mobile";
        $info = self::findByViaSlave($cond, '*', array(':mobile'=>$mobile));
        return $info;
    }

    /**
     * 根据联系方式获取用户
     * @param $mobile
     */
    public function checkUserByPhone($mobile) {
        $cond = "`consignee_phone` = :mobile OR `major_mobile` = :mobile";
        return self::findByViaSlave($cond, '*', array(':mobile'=>$mobile)) ? true : false;
    }

    /**
     * 对企业用户接收短信通知号码进行去重过滤等
     * @param string $receive_mobile
     */
    protected static function _receiveUnique($receive_mobile) {
        $tmp = explode(',', trim($receive_mobile, ','));
        $tmpUnique = array_unique($tmp);
        foreach ($tmpUnique as $key => $value) {
            if (empty($value)) {
                unset($tmpUnique[$key]);
                continue;
            }
        }
        return join(',', $tmpUnique);
    }

    public function addEnterpriseContact($data) {
        // 判空
        if (!$this->checkEmpty($data)) {
            return false;
        }

        $this->create_time = time();
        $this->update_time = $this->create_time;
        $this->setRow($data);

        return $this->insert();
    }

    /**
     * 检查参数是否为空
     */
    public function checkEmpty($data) {
        foreach ($data as $value) {
            if ($value === '' || $value === null) {
                return false;
            }
        }
        return true;
    }

}
