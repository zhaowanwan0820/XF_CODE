<?php
namespace api\controllers;

use libs\rpc\Rpc;
use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Signature;
use libs\utils\Aes;

/**
 * FundBaseAction
 * 基金API安全验证部分
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author Pine wangjiansong@ucfgroup.com
 */
class FundBaseAction extends AppBaseAction
{
    // 查询数据成功时返回的flag
    const FLAG_SUCCESS = 0; // 请求处理成功
    const FLAG_NO_REQUEST = 1; // 没有接到请求
    const FLAG_DEAL_FAILED = 2; // 接到请求，但处理失败

    public $flag = 0; // 查询数据时，处理成功的数据flag值
    public function _before_invoke() {

        $datas = $this->form->data;
        if (!Signature::verify($datas, $GLOBALS['sys_config']['FUND_SEC_KEY'], 'signature')) {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名证书不正确
            return false;
        }

        return true;
    }

    public function _after_invoke() {

        $arr_result = array();
        if ($this->errno == 0) {
            $arr_result["errno"] = 0;
            $arr_result["error"] = "";
            $arr_result['flag'] = $this->flag;
            $arr_result["data"] = $this->json_data;
        } else {
            $arr_result["errno"] = $this->errno;
            $arr_result["error"] = $this->error;
            $arr_result["data"] = "";
        }
        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            var_export($arr_result);
        } else {
            $arr_result = json_encode($arr_result, JSON_UNESCAPED_UNICODE);
            echo Aes::encode($arr_result, base64_decode($GLOBALS['sys_config']['FUND_AES_KEY']));
        }
    }
}
