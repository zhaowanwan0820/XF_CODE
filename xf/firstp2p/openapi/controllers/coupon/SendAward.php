<?php
/**
 * openapi优惠券入口接口
 *
 * Date: 2016年03月24日
 */
namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;

class SendAward extends PageBaseAction {
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "siteId" => array("filter" => "required", "message" => "siteId is required"),
            "couponGroupId" => array("filter" => "required", "message" => "couponGroupId is required"),
            "actionId" => array("filter" => "required", "message" => "actionId is required"),
            "sign" => array("filter" => "required", "message" => "sign is required"),
            'mobile' => array('filter' => 'string', 'option' => array('optional' => true)),
            'userId' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $siteId = intval(trim($data['siteId']));
        $couponGroupId = intval(trim($data['couponGroupId']));
        $actionId = intval(trim($data['actionId']));
        $sign = trim($data['sign']);
        $mobile = intval(trim($data['mobile']));
        $userId = intval(trim($data['userId']));
        if(!$this->checkSign($siteId, $data)) {
            $this->setErr('ERR_SYSTEM_SIGN');
            return false;
        }
        try{
            if(empty($userId) && !empty($mobile)) {
                $userId = $this->rpc->local('UserService\getUserIdByMobile', array($mobile));
                if(empty($userId)) {
                    $this->setErr('ERR_PARAMS_ERROR', '用户不存在');
                    return false;
                }
            }
            $rpcParams = array($couponGroupId,$userId,$actionId, $siteId);
            PaymentApi::log('第三方直接发券 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
            $couponInfo = $this->rpc->local('O2OService\sendAward', $rpcParams);
            PaymentApi::log('第三方直接发券 - 请求结果'.json_encode($couponInfo, JSON_UNESCAPED_UNICODE));
            if($couponInfo) {
                $data = array(
                    'id' => $couponInfo['id'],
                    'name' => $couponInfo['productName'],
                    'couponGroupId' => $couponInfo['couponGroupId'],
                    'couponNumber' => $couponInfo['couponNumber'],
                    'status' => $couponInfo['status'],
                    'useStartTime' => $couponInfo['useStartTime'],
                    'useEndTime' => $couponInfo['useEndTime'],
                    'createTime' => $couponInfo['createTime'],
                );
            } else {
                $this->setErr('ERR_COUPON_FAILED');
                return false;
            }
        }catch(\Exception $e) {
            PaymentApi::log("sendAward_error|".$e->getMessage());
            return false;
        }
        $this->json_data = $data;
        return true;
    }

    public function _after_invoke() {
        $arr_result = array();
        if ($this->errorCode == 0) {
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
            echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
        }
    }

    public function checkSign($siteId, $req) {
        $app_secret = self::getSiteSecret($siteId);
        if(empty($app_secret)) {
            return false;
        }
        $sign = $req['sign'];
        unset($req['sign']);

        $sortedReq = $app_secret;
        ksort($req);
        reset($req);
        while (list ($key, $val) = each($req)) {
            if (!is_null($val)) {
                $sortedReq .= $key . $val;
            }
        }

        $sortedReq .= $app_secret;
        $sign_md5 = strtoupper(md5($sortedReq));
        PaymentApi::log("checkSign:sign|".$sign."|expSign|".$sign_md5."|secretKey|".$app_secret);
        if ($sign !== $sign_md5) {
            return false;
        }
        return true;
    }

    public static function getSiteSecret($siteId) {
        $redisKey = 'o2o_send_award_conf_'.$siteId;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $result = $redis->get($redisKey);
        if(!empty($result)) {
            return $result;
        } else {
            return 0;
        }
    }
}
