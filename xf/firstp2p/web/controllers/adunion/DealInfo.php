<?php
/**
 * DealInfo controller class file.
 *
 * @author 景旭<jingxu@ucfgroup.com>
 **/

namespace web\controllers\adunion;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 获得项目详情
 *
 * @author jingxu<jingxu@ucfgroup.com>
 **/
class DealInfo extends BaseAction {

    public function init() {
        $this->form = new Form("get");
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'cn' => array('filter' => 'string'),
            'pubId' => array('filter' => 'int'),
            'ref' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $data['cn'] = htmlspecialchars($data['cn']);
        $data['ref'] = htmlspecialchars($data['ref']);

        $deal_info = $this->rpc->local("RssService\getDealInfo", array(
            'id' => $data['id'],
            'will_return' => true,
        )); 
        $deal_info['link'] = FormatLink::formatLink($data['cn'], $data['pubId'], $data['ref'], $deal_info['link']);

        echo json_encode($deal_info);
    }

}
