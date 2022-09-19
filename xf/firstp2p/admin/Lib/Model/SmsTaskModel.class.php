<?php


class SmsTaskModel extends CommonModel {

    public function __construct() {
        $this->table_prefix = 'firstp2p_';
        parent::__construct('SmsTask', false, 'msg_box', 'master');
    }

    public static function getMobileByUserIds($userIds = []) {
        if(empty($userIds)) {
            return [];
        }

        $userService  = new core\service\UserService();
        $userInfoList = $userService->getUserInfoByIds($userIds, 'id,mobile_code,mobile');
        if(empty($userInfoList)) {
            return [];
        }

        $mobileUids = [];
        foreach ($userInfoList as $userInfo) {
            if(is_mobile($userInfo['mobile'])) {
                $mobileUids[$userInfo['id']] = $userInfo;
            }
        }

        return $mobileUids;
    }

    public static function getAdmNamesByAdminIds($adminIds = []) {
        $options = empty($adminIds) ? '' : implode(',', array_unique($adminIds));
        $admins  = M('admin')->findAll($options);
        if(empty($admins)) {
            return [];
        }
        if(is_array($admins)) {
            $adminInfos = [];
            foreach ($admins as $row) {
                $adminInfos[$row['id']] = $row['adm_name'];
            }
            return $adminInfos;
        }
    }

    public static function batchAddSmsTaskUsers($smsTaskId, $extList) {
        if(empty($extList)) {
            return true;
        }

        $datas = [];
        $userInfos = self::getMobileByUserIds(array_keys($extList));
        foreach ($extList as $userId => $extContent) {
            $datas[] = array(
                'sms_task_id' => $smsTaskId,
                'user_id'     => $userId,
                'mobile_code' => $userInfos[$userId]['mobile_code'],
                'mobile'      => $userInfos[$userId]['mobile'],
                'ext_content' => json_encode($extContent, JSON_UNESCAPED_UNICODE),
                'status'      => 0,
           );
        }

        return self::insertAll($datas, 'sms_task_user');
    }

    public function insertAll($datas, $table) {
        $fields = array_keys(current($datas));
        $valueArray = array();
        foreach ($datas as $item) {
            foreach ($fields as $value) {
                $item[$value] = is_null($item[$value]) ? 'null' : "'".addslashes($item[$value])."'";
            }
            $valueArray[] = '('.implode(',', $item).')';
        }
        foreach ($fields as $key => $value) {
            $fields[$key] = "`$value`";
        }

        $tableName = DB_PREFIX . $table;
        $sql = "INSERT INTO `{$tableName}` (".implode(',', $fields).") VALUES ".implode(', ', $valueArray);

        $model = new SmsTaskUserModel();
        return $model->execute($sql);
    }

    public static function getUserByMobile($sMobile){
        if(empty($sMobile)){
            return array();
        }
        $userService  = new core\service\UserService();
        return $userService->getByMobile($sMobile);
    }
}
