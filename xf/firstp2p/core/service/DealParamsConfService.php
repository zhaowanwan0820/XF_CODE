<?php
/**
 * 上标队列参数配置
 * @author fanjingwen
 **/

namespace core\service;
use core\service\DealTagService;
use core\dao\DealExtModel;
use core\dao\DealModel;
use core\dao\DealParamsConfModel;
use core\dao\DealInfoModel;
use libs\utils\Logger;

class DealParamsConfService extends BaseService
{
    public function applyDealParamsConfByDealId($deal_params_conf_id, $deal_id)
    {
        try {
            if (empty($deal_params_conf_id)) {
                return true;
            }
            $deal_params_conf = DealParamsConfModel::instance()->findViaSlave($deal_params_conf_id);
            if (empty($deal_params_conf)) {
                return true;
            }

            $deal_obj = DealModel::instance()->find($deal_id);
            $deal_ext_info = DealExtModel::instance()->getDealExtByDealId($deal_id);

            $deal_ext_data = array();
            empty($deal_params_conf['deal_tag_name']) or $deal_obj->deal_tag_name = $deal_params_conf['deal_tag_name'];
            empty($deal_params_conf['deal_tag_desc']) or $deal_obj->deal_tag_desc = $deal_params_conf['deal_tag_desc'];
            empty($deal_params_conf['min_loan_money']) or $deal_obj->min_loan_money = $deal_params_conf['min_loan_money'];
            empty($deal_params_conf['max_loan_money']) or $deal_obj->max_loan_money = $deal_params_conf['max_loan_money'];

            // 费率相关
            empty($deal_params_conf['loan_fee_rate']) or ($deal_obj->loan_fee_rate += $deal_params_conf['loan_fee_rate']);
            empty($deal_params_conf['consult_fee_rate']) or ($deal_obj->consult_fee_rate += $deal_params_conf['consult_fee_rate']);
            empty($deal_params_conf['guarantee_fee_rate']) or ($deal_obj->guarantee_fee_rate += $deal_params_conf['guarantee_fee_rate']);
            empty($deal_params_conf['pay_fee_rate']) or ($deal_obj->pay_fee_rate += $deal_params_conf['pay_fee_rate']);
            if (!empty($deal_params_conf['income_base_rate'])) {
                $deal_ext_data['income_base_rate'] = $deal_ext_info['income_base_rate'] + $deal_params_conf['income_base_rate'];
                // （年化收益基本利率 + 年化收益浮动利率）覆盖‘借款年利率’和‘年化出借人收益率’
                $deal_obj->rate = $deal_ext_data['income_base_rate'] + $deal_ext_info['income_float_rate'];
                $deal_obj->income_fee_rate = $deal_obj->rate;
            }

            // 投资限定条件 - 无条件覆盖
            $deal_obj->deal_crowd = $deal_params_conf['deal_crowd'];
            $deal_obj->bid_restrict = $deal_params_conf['bid_restrict'];
            $deal_ext_data['deal_specify_uid'] = $deal_params_conf['specify_uid'];

            $GLOBALS['db']->startTrans();
            // update deal
            if (false === $deal_obj->save()) {
                throw new \Exception('update deal error');
            }

            // update deal-ext
            $cond = sprintf('`deal_id` = %d', $deal_id);
            if (false === DealExtModel::instance()->updateBy($deal_ext_data, $cond)) {
                throw new \Exception('update deal-ext error');
            }

            // update tag_names
            $deal_tag_service = new DealTagService();
            if (false === $deal_tag_service->insert($deal_id, $deal_params_conf['tag_names'])) {
                throw new \Exception('update deal-tag error');
            }

            // add activity introduction to nosql
            if (!empty($deal_params_conf['activity_introduction'])) {
                $deal_nosql_obj = new DealInfoModel();
                if (false === $deal_nosql_obj->saveDealActivityIntroduction($deal_id, $deal_params_conf['activity_introduction'])) {
                    Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $deal_id, 'fail:saveDealActivityIntroduction')));
                }
            }

            // 投资限定条件1 - 指定用户组可投
            if ($deal_obj->deal_crowd == '35') {
                $condition1 = json_decode($deal_params_conf['condition_params'], true);
                $groupIds = $condition1['group_id'];
                $saveGroupIds = (new \core\service\DealCustomUserService())->saveGroupIds($deal_id, $groupIds, 0);
                if (!$saveGroupIds) {
                    throw new \Exception('update condition 1 deal crowd 35 group ids error');
                }
            }

            $GLOBALS['db']->commit();
            $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, APP, $deal_id, 'succes'));
            $res = true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, APP, $deal_id, 'fail ' . $e->getMessage()));
            $res = false;
        }
        Logger::info($log_info);
        return $res;
    }
}
