<?php
/**
 * 网信理财-用户信息修改Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class WxUpdateUserInfoEvent extends GlobalTransactionEvent {
    /**
     * 参数列表-新
     * @var array
     */
    private $data;
    /**
     * 参数列表-旧
     * @var array
     */
    private $oldData;

    public function __construct($data, $oldData) {
        $this->data = $data;
        $this->oldData = $oldData;
    }

    /**
     * 网信理财-用户信息修改
     */
    public function execute() {
        try{
            $res = save_user($this->data, 'UPDATE', 0, true);
            if ($res['status'] == 0) {
                $error_field = $res['data'];
                if ($error_field['error'] == EMPTY_ERROR) {
                    if ($error_field['field_name'] == 'user_name') {
                        throw new \Exception('会员名不能为空');
                    } else {
                        throw new \Exception(sprintf('会员%s不能为空', $error_field['field_show_name']));
                    }
                }
                if ($error_field['error'] == FORMAT_ERROR) {
                    if ($error_field['field_name'] == 'email') {
                        throw new \Exception('员邮箱格式错误');
                    }
                    //去掉手机校验 20150514 shengjia需求, 前端隐藏
                    if ($error_field['field_name'] == 'mobile') {
                        throw new \Exception('会员手机格式错误');
                    }
                }
                // todo 日志记录
                if ($error_field['error'] == EXIST_ERROR) {
                    if ($error_field['field_name'] == 'user_name') {
                        throw new \Exception('会员名称已存在');
                    } elseif ($error_field['field_name'] == 'email') {
                        throw new \Exception('会员邮箱已存在');
                    } elseif ($error_field['field_name'] == 'mobile') {
                        throw new \Exception('手机号已经存在！');
                    }
                }
            }
            return true;
        }catch(\Exception $e) {
            $this->setError('网信账户：' . $e->getMessage());
            return false;
        }
    }

    public function rollback() {
        $userService = new \core\service\UserService();
        if (empty($this->oldData)) {
            $this->oldData = $userService->getUserViaSlave($this->data['id']);
        }

        $data = [
            'id' => $this->oldData['id'],
            'mobile' => addslashes($this->oldData['mobile']),
            'group_id' => (int)$this->oldData['group_id'],
            'user_purpose' => (int)$this->oldData['user_purpose'],
            'coupon_level_id' => addslashes($this->oldData['coupon_level_id']),
            'coupon_level_valid_end' => $this->oldData['coupon_level_valid_end'],
            'is_effect' => (int)$this->oldData['is_effect'],
            'update_time' => get_gmtime(),
        ];
        !empty($this->oldData['email']) && $data['email'] = addslashes($this->oldData['email']);
        !empty($this->oldData['real_name']) && $data['real_name'] = addslashes($this->oldData['real_name']);
        !empty($this->oldData['id_type']) && $data['id_type'] = (int)$this->oldData['id_type'];
        !empty($this->oldData['idno']) && $data['idno'] = addslashes($this->oldData['idno']);
        !empty($this->oldData['byear']) && $data['byear'] = (int)$this->oldData['byear'];
        !empty($this->oldData['bmonth']) && $data['bmonth'] = (int)$this->oldData['bmonth'];
        !empty($this->oldData['bday']) && $data['bday'] = (int)$this->oldData['bday'];
        !empty($this->oldData['sex']) && $data['sex'] = (int)$this->oldData['sex'];
        !empty($this->oldData['marriage']) && $data['marriage'] = addslashes($this->oldData['marriage']);
        return $userService->updateInfo($data);
    }
}