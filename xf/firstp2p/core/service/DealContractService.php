<?php

namespace core\service;

use core\dao\DealModel;
use core\dao\DealContractModel;

class DealContractService extends BaseService
{
    /**
     * 检测项目下所有的标的合同是否都已签署成功
     * @param int $project_id
     * @return boolean
     *
     */
    public function isAllDealContracOfProjectSigned($project_id)
    {
        // 获取所有标的
        $deal_list = DealModel::instance()->getDealByProId(intval($project_id));

        foreach ($deal_list as $deal) {
            // 如果有一个没有签署成功，则返回 false
            if (false == DealContractModel::instance()->getDealContractUnSignInfo($deal['id'])) {
                return false;
            }
        }

        return true;
    }
}
