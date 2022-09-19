<?php
/**
 * 调用普惠接口
 * 合同查看和下载
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\ncfph\AccountService;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

class Contshowph extends BaseAction
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
        // 标的合同
        $service_id = intval($data['dealId']);
        if (($id <= 0) || ($service_id <= 0) || !in_array($tag, array('show', 'download', 'download_tsa', 'download_new', 'shownew'))) {
            return self::download_return($ajax);
        }


        $accountServcie = new AccountService();
        if ($tag == 'download_tsa') {
            $accountServcie->downContract($id, $service_id, $GLOBALS['user_info'], true);
        } elseif ($tag == 'download') {
            $accountServcie->downContract($id, $service_id, $GLOBALS['user_info'], false);
            return true;
        } else {
            $contract = $accountServcie->getContractContent($id, $service_id, $GLOBALS['user_info']);
            echo hide_message($contract['content']);
            exit();
        }
        $contract = $accountServcie->getContractContent($id, $service_id, $GLOBALS['user_info']);
        echo hide_message($contract['content']);
        exit();

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

