<?php
namespace core\dao;
use libs\utils\Site;
use core\dao\UserModel;
use core\dao\DealModel;

/**
 * 网信通行证相关信息
 **/
class WangxinPassportModel extends BaseModel
{

    const FLAG_LOCAL = 1;

    const FLAG_PASSPORT = 0;

    const VERIFY_MARK_NEED = 1;

    const VERIFY_MARK_PASS = 0;

    public function savePassport($userId, $ppUserInfo, $localFlag = 1)
    {
        $data = [
            'user_id' => $userId,
            'ppid' => $ppUserInfo['ppId'],
            'identity' => $ppUserInfo['identity'],
            'biz_name' => $ppUserInfo['bizName'],
            'local_flag' => $localFlag,
        ];

        $data['create_time'] = time();
        $this->setRow($data);
        $this->_isNew = true;
        $res = $this->save();
        if (!$res) {
            return false;
        }
        return $this->id;
    }

    public function updatePassport($id, $data)
    {

        $data['id'] = $id;
        $data['update_time'] = time();
        $this->setRow($data);
        $res = $this->save();
        if (!$res) {
            return false;
        }
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function getPassportByUser($userId)
    {
        $passport = $this->findBy("user_id = '{$userId}'");
        return $passport;
    }

    public function getPassportByPPid($ppid)
    {
        $passport = $this->findBy("ppid = '{$ppid}'");
        return $passport;
    }

    public function getPassportByMobile($mobile)
    {
        $passport = $this->findBy("identity = '{$mobile}'");
        return $passport;
    }

    public function updatePassportByPPid($ppid, $data)
    {
        $data['update_time'] = time();
        $condition = "ppid = '$ppid'";
        return $this->updateBy($data, $condition);
    }

    public function updatePassportByMobile($mobile, $data)
    {
        $data['update_time'] = time();
        $condition = "identity = '$mobile'";
        return $this->updateBy($data, $condition);
    }
}
