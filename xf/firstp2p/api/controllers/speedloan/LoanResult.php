<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;

/**
 * LoanResult
 * 借款结果页面
 *
 * @uses BaseAction
 * @package default
 */
class LoanResult extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'orderId' => array('filter' => 'required', 'message' => '订单号不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $this->tpl->assign('token', $data['token']);
        $userInfo = $this->getUserByToken();

        $dealRepayModel = new \core\dao\DealLoanRepayModel();
        $nowTime = get_gmtime();
        $timeEnd = strtotime('+12 months') - 28800;
        $repayItems = $dealRepayModel->getLoanList($userInfo['id'], $nowTime, $timeEnd, [0,10], 'creditloanapi', 1, 0);
        $counts = $repayItems['counts'];
        unset($repayItems['counts']);
        foreach ($repayItems as $k=>$item) {
            $repayItems[$k]['dealInfo'] = \core\dao\DealModel::instance()->find($item['deal_id']);
            $repayItems[$k]['repayDate'] = date('Y-m-d', $item['time']+28800);
            $repayDate = new \DateTime(date('Y-m-d', $item['time']+28800));
            $nowDate = new \DateTime(date('Y-m-d'));
            $intevals = $repayDate->diff($nowDate);
            // 算头不算尾
            $repayItems[$k]['untilDays'] = $intevals->days;
            // 判断是否无效标
            $limitDealTypes = explode(';', trim(app_conf('SPEED_LOAN_OTHER_DEAL_TYPE'), ';'));
            $dealType = $repayItems[$k]['dealInfo']['deal_type'];
            if (!empty($limitDealTypes) && in_array($dealType, $limitDealTypes)) {
                Logger::info('Deal type in other '. $dealType);
                unset($repayItems[$k]);
                continue;
            }

            $dealProductType = $repayItems[$k]['dealInfo']['type_id'];
            $limitProductTypes = explode(';', trim(app_conf('SPEED_LOAN_OTHER_PRODUCT_TYPE'),';'));
            if (!empty($limitProductTypes) && in_array($dealProductType, $limitProductTypes)) {
                Logger::info('Deal product type in other,'. $dealProductType);
                unset($repayItems[$k]);
                continue;
            }

            $limitDealLoanType = explode(';', trim(app_conf('SPEED_LOAN_OTHER_DEAL_REPAY_TYPE'), ';'));
            $dealLoanType = $repayItems[$k]['dealInfo']['loantype'];
            if (!empty($limitDealLoanType) && in_array($dealLoanType, $limitDealLoanType)) {
                Logger::info('Deal repay type in other' . $dealLoanType);
                unset($repayItems[$k]);
                continue;
            }

            $limitDealTags = explode(';', trim(app_conf('SPEED_LOAN_OTHER_DEAL_TAG'), ';'));
            $dealTag = (new \core\service\DealTagService())->getTagByDealId($item['deal_id']);
            if (!empty($limitDealTags) && array_intersect($dealTag, $limitDealTags)) {
                Logger::info('Deal tag match other,' . json_encode($dealTag, JSON_UNESCAPED_UNICODE));
                unset($repayItems[$k]);
                continue;
            }
        }
        $this->tpl->assign('repayItems', $repayItems);
        $this->tpl->assign('counts', $counts);

    }
}
