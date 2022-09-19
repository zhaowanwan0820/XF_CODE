<?php
/**
 * Index.php
 * Passport 回调入口
 *
 */
namespace web\controllers\passport;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use libs\passport\Passport;
use libs\passport\CodeEnum;
use core\service\PassportService;
use libs\utils\Logger;

class Index extends BaseAction
{

    public function init()
    {
        $this->form = new Form('post');
        $this->form->rules = array(
            'requestParam' => array('filter' => 'required', 'message' => ''),
            'service' => array('filter' => 'required', 'message' => ''),
            'version' => array('filter' => 'required', 'message' => ''),
            'extProperties' => array('filter' => 'required', 'message' => ''),
            'signature' => array('filter' => 'required', 'message' => '')
        );
        if (!$this->validateJsonData()) {
            return Passport::response(CodeEnum::SYS_PARAM_LACK);
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $service = new PassportService();
        return $service->handle($data);
    }

    private function validateJsonData()
    {
        if (empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
            Logger::info('PassporyWX no json data');
            return false;
        }

        Logger::info('PassportWX request data:' . $GLOBALS['HTTP_RAW_POST_DATA']);
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        foreach ($this->form->rules as $field => $filter) {
            if ($filter['filter'] == 'required' && !isset($data[$field])) {
                Logger::info('PassportWX ' . $field. ' is empty');
                return false;
            }
        }
        $this->form->data = $data;

        return true;
    }
}
