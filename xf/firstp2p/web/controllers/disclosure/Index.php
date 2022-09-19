<?php
/**
 * [信息披露]
 * @author <fanjingwen@ucf>
 * #JIRA3627
 */

namespace web\controllers\disclosure;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\DealAgencyModel;

class Index extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        if (empty($GLOBALS['sys_config']['diclosure_show'])) {
            return;
        }
        // 机构是否展示
        $showConditon = $GLOBALS['sys_config']['diclosure_show'];
        $showCount = 0;
        $agencyType = $GLOBALS['dict']['ORGANIZE_TYPE'];

        // 担保机构
        if (true == $showConditon[$agencyType[1]]) {
            ++$showCount;
            $guarantee_list = $this->rpc->local('DealAgencyService\getDealAgencyListByType', array(DealAgencyModel::TYPE_GUARANTEE));
            $guarantee_list_new = $this->handleAgencyInfo($guarantee_list);
            $this->tpl->assign('guarantee_is_show', 1);
            $this->tpl->assign('guarantee_list', $guarantee_list_new);
        }

        // 咨询机构
        if (true == $showConditon[$agencyType[2]]) {
            ++$showCount;
            $consult_list = $this->rpc->local('DealAgencyService\getDealAgencyListByType', array(DealAgencyModel::TYPE_CONSULT));
            $consult_list_new = $this->handleAgencyInfo($consult_list);
            $this->tpl->assign('consult_is_show', 1);
            $this->tpl->assign('consult_list', $consult_list_new);
        }


        // 平台机构
        if (true == $showConditon[$agencyType[3]]) {
            ++$showCount;
            $platform_list = $this->rpc->local('DealAgencyService\getDealAgencyListByType', array(DealAgencyModel::TYPE_PLATFORM));
            $platform_list_new = $this->handleAgencyInfo($platform_list);
            $this->tpl->assign('platform_is_show', 1);
            $this->tpl->assign('platform_list', $platform_list_new);
        }

        // 支付机构
        if (true == $showConditon[$agencyType[4]]) {
            ++$showCount;
            $payment_list = $this->rpc->local('DealAgencyService\getDealAgencyListByType', array(DealAgencyModel::TYPE_PAYMENT));
            $payment_list_new = $this->handleAgencyInfo($payment_list);
            $this->tpl->assign('payment_is_show', 1);
            $this->tpl->assign('payment_list', $payment_list_new);
        }

        // 管理机构
        if (true == $showConditon[$agencyType[5]]) {
            ++$showCount;
            $management_list = $this->rpc->local('DealAgencyService\getDealAgencyListByType', array(DealAgencyModel::TYPE_MANAGEMENT));
            $management_list_new = $this->handleAgencyInfo($management_list);
            $this->tpl->assign('management_is_show', 1);
            $this->tpl->assign('management_list', $management_list_new);
        }

        // 代垫机构
        if (true == $showConditon[$agencyType[6]]) {
            ++$showCount;
            $advance_list = $this->rpc->local('DealAgencyService\getDealAgencyListByType', array(DealAgencyModel::TYPE_ADVANCE));
            $gadvance_list_new = $this->handleAgencyInfo($advance_list);
            $this->tpl->assign('advance_is_show', 1);
            $this->tpl->assign('advance_list', $gadvance_list_new);
        }

        $this->tpl->assign('show_count', $showCount);
        // 设置面包屑
        $this->set_nav("信息披露");

        $this->tpl->display("web/views/disclosure/agency.html");
    }

    /**
     * [处理获取的机构全量信息，供views使用]
     * @param array [$agencyList:机构信息列表[key => ['id', 'name'...]]]
     * @return array [key:1/2/3...,value:机构信息]
     */
    private function handleAgencyInfo($agencyList)
    {
        $agencyListNew = array();

        $subNum = 1;
        foreach ($agencyList as $agency) {
            $agencyListNew[$subNum]['id'] = $agency['id'];
            $agencyListNew[$subNum]['name'] = $agency['name'];
            $agencyListNew[$subNum]['logo'] = $agency['logo']['full_path'];
            $agencyListNew[$subNum]['url'] = "disclosure/AgencyInfo?jg=" . base64_encode(json_encode(array("id" => $agency['id'])));
            ++$subNum;
        }

        return $agencyListNew;
    }
}