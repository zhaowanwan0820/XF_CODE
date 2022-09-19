<?php

/**
 * LoadList.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use core\service\duotou\DuotouService;
use core\service\duotou\DtEntranceService;

/**
 * 已投资列表接口
 *
 * status（可选）：状态；默认为0；0-全部 1-投资中 2-可转让 3-转让中  4-已转让 5-已结清
 *
 * Class LoadList
 * @package api\controllers\duotou
 */
class LoadList extends DuotouBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "status" => array("filter" => "int", "message" => "status is error", "option" => array('optional' => true)),
            'page' => array('filter' => 'int', "option" => array('optional' => true)),
            'page_size' => array('filter' => 'int', "option" => array('optional' => true)),     );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }

    }

    public function invoke() {
        $userInfo = $this->user;
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $data = $this->form->data;
        $status = isset($data['status']) ? intval($data['status']) : 0;//处理status字段，默认为0；0-全部 1待计息 2 计息中 3 转让中 4 已转让  5-已结清
        if($this->app_version == 460 && $_SERVER['HTTP_OS'] == 'Android'){//4.6版本Android page+1
            $page = (isset($data['page']) && $data['page'] >= 0) ? (intval($data['page']) + 1) : 1;
        }else{
            $page = (isset($data['page']) && $data['page'] > 0) ? intval($data['page']) : 1;
        }
        $pageSize = isset($data['page_size']) ? $data['page_size'] : 5;

        $vars = array(
            'status' => $status,
            'pageNum' => $page,
            'pageSize' => $pageSize,
            'userId' => $userId,
        );
        $response = $this->callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoans',$vars));
        if(!$response) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
        }

        $activityInfos = array();
        $oDtEntranceService = new DtEntranceService();
        foreach ($response['data']['data'] as $value) {
            $text = '';
            if($value['status'] == 3){
                $text = '成功转让/退出后显示到账日';
            }elseif($value['status'] == 4 || $value['status'] == 5) {
                $text = !empty($value['redeemFinishTime']) ? $value['redeemFinishTime'].'本金到账' : '';
            }
            $isOpen = $this->isOpen(strtotime($value['projectInfo']['redemptionStartTime']), strtotime($value['projectInfo']['redemptionEndTime']));

            $tempData = array(
                'projectName' => $value['projectInfo']['name'],
                'hasRepayInterest' => number_format($value['hasRepayInterest'], 2),
                'noRepayInterest' => number_format($value['norepayInterest'], 2),
                'money' => number_format($value['money'], 2),
                'repayStartTime' => (!empty($value['repayStartTime']) && $status != 6) ? $value['repayStartTime'] : '',
                'redeemFinishTime' => !empty($value['redeemFinishTime']) ? $value['redeemFinishTime'] : '',
                'status' => $value['status'] == 0 ? 1 : $value['status'],
                'dealLoanId' => $value['id'],
                'activityId' => $value['activityId'],
                'ownDay' => $value['loadDays'],
                'openTime' => $value['status'] == 2 ? '每日'.date('G:i',strtotime($value['projectInfo']['redemptionStartTime'])).'-'.date('G:i',strtotime($value['projectInfo']['redemptionEndTime'])).'开放转让/退出' : '',
                'isOpen' => $isOpen ? 1 : 0,
                'hasRepayInterestTag' => '已到账（元）',
                'noRepayInterestTag' => '未到账（元）',
                'text' => $text,
                'quitTime' => empty($value['quitTime']) ? '-' : date('Y-m-d', $value['quitTime']),
            );
            if($value['activityId'] > 0) { //参与了活动
                $activityId = $value['activityId'];
                if(isset($activityInfos[$activityId])) {
                    $tempData['activityInfo'] = $activityInfos[$activityId];
                } else {
                    $activityInfo = $oDtEntranceService->getEntranceInfo($activityId);
                    $tempData['activityInfo'] = $activityInfos[$activityId] = $activityInfo;
                }
            }
            $res[] = $tempData;

        }
        $this->json_data = $res;
    }

}
