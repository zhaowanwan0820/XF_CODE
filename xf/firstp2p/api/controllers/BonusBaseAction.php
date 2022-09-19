<?php
namespace api\controllers;

use libs\rpc\Rpc;
use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Signature;
use libs\utils\Aes;

/**
 * BonusBaseAction
 * 红包API安全验证部分
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author luzhengshuai@ucfgroup.com
 */
class BonusBaseAction extends AppBaseAction
{
    // 查询数据成功时返回的flag
    const FLAG_SUCCESS = 0; // 请求处理成功
    const FLAG_NO_REQUEST = 1; // 没有接到请求
    const FLAG_DEAL_FAILED = 2; // 接到请求，但处理失败

    // 请求公共信息验证
    public $generalFormRules = array (
        'version' => array('filter' => "required", 'message'=> '版本号不能为空！'),
        //'requestType' => array('filter' => "required", 'message' => '请求类型不能为空！'),
        'requestType' => array('filter' => "string"),
        'requestCode' => array('filter' => "string"),
        'requestNo' => array('filter' => 'required', 'message' => '流水序列号不能为空！'),
        'signature' => array('filter' => "required", 'message'=> '签名不能为空！'),
    );

    public function _before_invoke() {

        // 补充验证
        if (!empty($this->form->rules)) {
            foreach ($this->form->rules as $field => $filter) {
                if ($filter['filter'] == 'string') {
                    $this->form->data[$field] = trim($this->form->data[$field]);
                }
                if ($filter['filter'] == 'int') {
                    $this->form->data[$field] = intval($this->form->data[$field]);
                    if ($this->form->data[$field] <= 0) {
                        $this->setErr('ERR_PARAMS_VERIFY_FAIL', $filter['message']);
                        return false;
                    }
                }
                if ($filter['filter'] == 'required') {
                    $this->form->data[$field] = trim($this->form->data[$field]);
                    if ($this->form->data[$field] === NULL || $this->form->data[$field] === '') {
                        $this->setErr('ERR_PARAMS_VERIFY_FAIL', $filter['message']);
                        return false;
                    }
                }
            }
        }

        if (!Signature::verify($_POST, $GLOBALS['sys_config']['BONUS_SEC_KEY'], 'signature')) {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名证书不正确
            return false;
        }

        return true;
    }

    public function _after_invoke() {

        $arr_result = array();
        if ($this->errno == 0) {
            $arr_result["errorCode"] = 0;
            $arr_result["errorMsg"] = '';
            $arr_result['requestNo'] = $this->form->data['requestNo'];
            $arr_result["data"] = $this->json_data;
        } else {
            $arr_result["errorCode"] = $this->errno;
            $arr_result["errorMsg"] = $this->error;
            $arr_result['requestNo'] = $this->form->data['requestNo'];
            $arr_result["data"] = array();
        }
        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            var_export($arr_result);
        } else {
            $arr_result['data'] = json_encode($arr_result['data'], JSON_UNESCAPED_UNICODE);
            $arr_result['data'] = Aes::encode($arr_result['data'], base64_decode($GLOBALS['sys_config']['BONUS_AES_KEY']));
            $arr_result['signature'] = Signature::generate($arr_result, $GLOBALS['sys_config']['BONUS_SEC_KEY']);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
        }
    }
}
