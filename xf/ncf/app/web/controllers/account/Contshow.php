<?php
/**
 * 合同查看和下载
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use core\service\contract\ContractService;
use libs\web\Form;
use web\controllers\BaseAction;

use core\enum\contract\ContractServiceEnum;
use core\service\contract\ContractInvokerService;

class Contshow extends BaseAction
{

    public function init()
    {
        if (!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'tag' => array('filter' => 'string'),
            'ajax' => array('filter' => 'int'),
            'number' => array('filter' => 'string'),
            'dealId' => array('filter' => 'int'),
            'projectId' => array('filter' => 'int'),
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $tag = $data ['tag'];
        $id = intval($data ['id']);
        $ajax = intval($data['ajax']);
        $number = $data['number'];
        $dealId = $data['dealId'];
        $projectId = $data['projectId'];
        $user_info = $GLOBALS['user_info'];

        if ($id <= 0 || !in_array($tag, array('show', 'download', 'download_tsa', 'download_new', 'shownew'))) {
            return self::download_return($ajax);
        }
        // 标的合同
        $service_id = $data['dealId'];
        $service_type = ContractServiceEnum::SERVICE_TYPE_DEAL;

        if (!empty($service_id)) {
            if ($tag == 'download_tsa') {
                $ret = ContractInvokerService::downloadTsa('filer', $id, $service_id, $service_type, $GLOBALS['user_info']);
                if ($ret == false) {
                    //时间戳还没打出来。直接下载老合同,避免前台修改链接走错逻辑
                    ContractInvokerService::download('filer', $id, $service_id, $service_type, $GLOBALS['user_info']);
                    return true;
                }
            } elseif ($tag == 'download') {
                ContractInvokerService::download('filer', $id, $service_id, $service_type, $GLOBALS['user_info']);
                return true;
            }
            $contract = ContractInvokerService::getOneFetchedContract('viewer', $id, $service_id, $service_type, $GLOBALS['user_info']);
            echo hide_message($contract['content']);
            exit();
        } else {
            return false;
        }
        return true;
    }

    public static function download_return($ajax = 0)
    {
        if ($ajax == 0) {
            echo '<script>window.parent.location.href="/404.html"</script>';
        } else {
            return app_redirect('/404.html');
        }
        return false;
    }

}

