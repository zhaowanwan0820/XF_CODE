<?php
/**
 * UserLogService class file.
 *
 * @author sunxuefeng@ucfgroup.com
 **/

namespace core\service\user;

use core\service\BaseService;
use core\dao\supervision\SupervisionChargeModel;

/**
 * UserLogService
 *
 * @author sunxuefeng@ucfgroup.com
 **/
class UserLogService extends BaseService
{
    public function get_charge_list($user_id,$offset=0,$ps=100) {
        return SupervisionChargeModel::instance()->getRecentList($user_id,$offset,$ps);
    }
}

