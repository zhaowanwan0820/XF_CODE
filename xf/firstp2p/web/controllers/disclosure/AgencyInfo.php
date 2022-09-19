<?php
/**
 * [信息披露-某个机构的信息]
 * @author <fanjingwen@ucf>
 * #JIRA3627
 */

namespace web\controllers\disclosure;

use libs\web\Form;
use web\controllers\BaseAction;

class AgencyInfo extends BaseAction
{
    public function init()
    {
        $this->form = new form();
        $this->form->rules = array(
            'jg' => array('filter' => 'string'),
            "p" => array("filter"=>"int"),
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $data = $this->form->data;
        $agencyIDObj = json_decode(base64_decode($this->form->data["jg"]));
        $agencyID = intval($agencyIDObj->id);
        $page = empty($data['p']) ? 1 : intval($data['p']);

        // 关于机构
        $agencyInfo = $this->rpc->local('DealAgencyService\getDealAgency', array($agencyID));
        $this->tpl->assign('agency_info', $agencyInfo);

        // 关于标
        $deals = $this->rpc->local('DealService\getListByAgency', array($agencyInfo['id'], $agencyInfo['type'], $page, 10));
        foreach ($deals['list']['list'] as $key => $dealInfo) {
            $dealInfo['url'] .= "?jg=" . base64_encode(json_encode(array("id" => $agencyInfo['id'], "name" => $agencyInfo['name'])));
            $deals['list']['list'][$key] = $dealInfo;
        }
        $this->tpl->assign("deal_list", $deals['list']);

        // 关于分页
        $this->tpl->assign("current_page", ($page == 0) ? 1 : $page);
        $this->tpl->assign("pages", ceil($deals['count'] / $deals['page_size']));
        $this->tpl->assign("pagination",pagination(($page == 0) ? 1 : $page, ceil($deals['count'] / $deals['page_size']), 8, 'p=', '&jg=' . base64_encode(json_encode(array("id" => $agencyInfo['id'])))));

        // 设置面包屑
        $this->set_nav(array("信息披露" => url("index", "disclosure"), $agencyInfo['name']));

        $this->tpl->display("web/views/disclosure/agency_info.html");
    }
}