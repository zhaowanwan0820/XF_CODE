<?php
/**
 *  HongbaoBindService
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 **/

namespace core\service;

use core\dao\BonusBindModel;
use core\dao\BonusModel;
use core\service\BonusService;

/**
 * UserFeedback service
 *
 * @packaged default
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
class BonusBindService extends BaseService
{
    public function bindUser($openid, $mobile) {
        if (!$mobile || !$openid) {
            return false;
        }
        $data['mobile'] = $mobile;
        $data['openid'] = $openid;

        $bindModel = new BonusBindModel();
        if ($result = $bindModel->getByConditions(array('openid' => $openid), 'mobile')) {
            if ($mobile === $result['mobile']) {
                return true;
            } else {
                return $this->updateByOpenid($openid, array('mobile' => $mobile));
            }
        } else {
            return $bindModel->insertData($data);
        }
    }

    public function getBindInfoByMobile($mobile)
    {
        $bindModel = new BonusBindModel();
        $result = $bindModel->findAllViaSlave("mobile = '{$mobile}'", true);
        return $result;
    }

    public function getBindInfoByOpenid($openid, $fields = '*') {
        if (!$openid) {
            return false;
        }

        $bindModel = new BonusBindModel();
        $result = $bindModel->getByConditions(array('openid' => $openid), $fields);
        return $result;
    }

    public function updateByMobile($mobile, $data) {
        if (!$mobile || !$data) {
            return false;
        }

        return $bindModel->updateByMobile($mobile, $data);
    }

    public function updateByOpenid($openid, $data) {
        if (!$openid || !$data) {
            return false;
        }

        $bindModel = new BonusBindModel();
        return $bindModel->updateByOpenid($openid, $data);
    }
}
// END class UserFeedbackService
