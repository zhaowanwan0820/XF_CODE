<?php
/**
 * ReserveBaseAction
 * 预约标Action
 *
 */

namespace openapi\controllers;

use libs\web\Form;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use core\service\UserReservationService;
use core\dao\UserModel;

class ReserveBaseAction extends BaseAction
{
    /**
     * 预约按钮是否置灰
     * @var int
     */
    protected $_isBookingButtonUnused = 0;

    /**
     * 预约标相关配置
     * @var array
     */
    protected $reserveConfig = array(
        'title' => '随心约',
    );

    public function _before_invoke()
    {
        //初始化预约按钮开关
        $siteId = \libs\utils\Site::getId();
        $this->_isBookingButtonUnused = (int) get_config_db('BOOKING_BUTTON_UNUSED', $siteId);

        if(!$this->form instanceof Form){
            $this->form = new Form();
            $this->form->sys_param_rules = $this->sys_param_rules;
        }

        if(!$this->form->validate()){
            $this->setErr('ERR_SYSTEM', $this->form->getErrorMsg());
            return false;
        }

        $data = $this->form->data;

        return true;
    }

    public function _after_invoke()
    {
        $arr_result = array();
        if ($this->errorCode == 0) {
            $this->dataFilter();
            $arr_result["errorCode"] = 0;
            $arr_result["errorMsg"] = "";
            $arr_result["data"] = $this->json_data;
        } else {
            $arr_result["errorCode"] = $this->errorCode;
            $arr_result["errorMsg"] = $this->errorMsg;
            $arr_result["data"] = $this->json_data_err;
        }

        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            var_export($arr_result);
        } else {
            echo json_encode($arr_result);
        }

    }

    /**
     * @特殊用户处理(检查用户请求频繁)
     * @param int $userId
     * @return void
     */
    public function chkUserOfter($userId)
    {
        if(\libs\utils\Block::isSpecialUser($userId)){
            if(\libs\utils\Block::checkAccessLimit($userId) === false){
                $this->setErr('ERR_RESERVE_OFTER', '刷新过于频繁，请稍后再试');
                return false;
            }
        }
    }

    /**
     * 是否开放预约-系统维护的开关
     * @return boolean
     */
    public function isOpenReserve() {
        if((int)app_conf('YYB_SWITCH') === 0) {
            $this->setErr('ERR_SYSTEM', '系统维护中，请稍后再试！');
            return false;
        }
        return true;
    }

    /**
     * 是否开启存管系统预约(0:关闭1:开启)
     * @return boolean
     */
    public function isOpenSupervisionReserve() {
        if((int)app_conf('SUPERVISION_RESERVE_SWITCH') === 0) {
            return false;
        }
        return true;
    }

    /**
     * 是否关闭预约-仅预约按钮置灰的开关
     * @return boolean
     */
    public function canReserve() {
        $siteId = \libs\utils\Site::getId();
        $isBookingButtonUnused = (int) get_config_db('BOOKING_BUTTON_UNUSED', $siteId);
        if($isBookingButtonUnused === 1) {
            $this->setErr('ERR_SYSTEM', '预约服务优化中，请稍后再试！');
            return false;
        }
        return true;
    }

    /**
     * @检查是否企业用户
     * @param string $mobile
     * @param int $type
     * @return int
     */
    public function checkEnterpriseUser($mobile, $type)
    {
        $is_enterprise_user = 0;
        if((!empty($mobile) && substr($mobile, 0, 1) == 6) || (isset($type) && $type == UserModel::USER_TYPE_ENTERPRISE)){
            $is_enterprise_user = 1;
        }

        return $is_enterprise_user;
    }
}
