<?php
/**
 * DealList controller class file.
 *
 * @author 景旭<jingxu@ucfgroup.com>
 **/

namespace web\controllers\adunion;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 获得项目列表
 *
 * @author jingxu<jingxu@ucfgroup.com>
 **/
class DealList extends BaseAction {

    public function init() {
        $this->form = new Form("get");
        
        $this->form->rules = array(
            'cate' => array('filter'=>'int'),
            'pagesize' => array('filter'=>'int'),
            'page' => array('filter'=>'int'),
            'site_id' => array('filter'=>'int'),
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

        $deal_list = $this->rpc->local("RssService\getNewDealList", array(
            'cate'=>isset($data['cate'])?$data['cate']:0, 
            'page'=>isset($data['page'])?$data['page']:1, 
            'pagesize'=>isset($data['pagesize'])?$data['pagesize']:10, 
            'site_id'=>$data['site_id'], 
            'is_real_site'=>false, 
            'will_return'=>true,
        )); 

        foreach($deal_list['item'] as &$item){
                $item['link'] = FormatLink::formatLink($data['cn'], $data['pubId'], $data['ref'], $item['link']);
        }  


        echo json_encode($deal_list);
    }

}
