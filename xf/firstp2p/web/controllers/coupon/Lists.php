<?php
/**
 * Check.php
 *
 * @date 2016-01-12
 * @author xiaoan
 */

namespace web\controllers\coupon;

use core\dao\UserModel;
use core\dao\CouponLogModel;
use core\dao\UserBankcardModel;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\CouponLogService;
use core\service\CouponService;

/**
 * 获取邀请记录
 */
class Lists extends BaseAction {

    public $user_id = 0;

    public function init() {
        if(!$this->check_login()) return false;
        $user_id = intval($GLOBALS['user_info']['id']);
        if (empty($user_id)) {
            return false;
        }
        $this->user_id = $user_id;

        $this->form = new Form();
        $this->form->rules = array(
            'type' => array('filter' => 'string'),
            'page' => array('filter' => 'int'),
            'dataType' => array('filter' => 'string'),
            'content' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    /**
     * status: 2:优惠码为空；1:正常返回
     */
    public function invoke() {
        $log_info = array(__CLASS__, __FUNCTION__, APP, __LINE__);
        $params = $this->form->data;
        logger::info(implode(" | ", array_merge($log_info, array(json_encode($params)))));
        $type = $params['type'];
        $dataType = $params['dataType'];
        $page = (intval($params['page']) <= 0) ? 1: intval($params['page']);
        $pageSize = 10;
        $firstRow = ($page-1)*$pageSize;
        if ($type != CouponLogService::MODULE_TYPE_DUOTOU && $type != CouponLogService::MODULE_TYPE_P2P && $type != CouponLogService::MODULE_TYPE_REG  && $type != CouponLogService::MODULE_TYPE_THIRD){
            return false;
        }

        list($consume_real_name,$consume_user_mobile) = $this->getRealNameAndMobile();//获取手机号查询条件或者真实姓名
        $isBigUser =  $this->rpc->local("CouponLogService\getCountUser",array($this->user_id));
        if($isBigUser){
            $consume_real_name = null ;
        }
        $couponLogService = new CouponLogService($type,$dataType);
        $data = $couponLogService->getLogPaid($type, $this->user_id, $firstRow, $pageSize, '', $consume_real_name, $consume_user_mobile);
        //$this->formatData($type,$data['data']['list']);
        $data['page'] = $page;
        $data['pagecount'] = ceil($data['count']/$pageSize) ;
        $data['count'] = $data['count'];
        $data['type'] = $type;
        logger::info(implode(" | ", array_merge($log_info, array('result', json_encode($params), json_encode($data)))));
        return ajax_return($data);
    }
    /**
     * 获取电话号码或者真实姓名
     */
    private function getRealNameAndMobile()
    {
        $nameAndMobile = array(0 => '', 1 => '');
        $content = htmlentities(trim($this->form->data['content']));
        if(!empty($content)){
            if(preg_match('/^\d+$/',$content)){
                $nameAndMobile[1]   = $content;//手机号
            }else{
                $nameAndMobile[0] = $content;//姓名
            }
        }
        $this->tpl->assign('content', $content);
        return $nameAndMobile;
    }

}
