<?php
/**
 *
 * 标的相关
 * 2016-5-21  到 2018-5-1 的借款列表
 */

use libs\utils\Aes;
use libs\utils\Logger;
use libs\vfs\Vfs;
use libs\db\Db;
use libs\utils\Finance;
use NCFGroup\Common\Library\Idworker;
use libs\utils\DBDes;
use core\dao\deal\DealLoanTypeModel;
use core\dao\repay\DealRepayModel;
use core\service\user\UserService;

// 加载标的相关函数
FP::import("app.Lib.deal");

class DealDetailAction extends DealSimpleAction{
    protected $module_name = 'DealDetail';
    protected $title_name = '借款列表';
    protected $history = 0;

    public function index()
    {
        $_REQUEST ['history'] = $this->history;
        $this->assign('module_name',$this->module_name);
        $this->assign('title_name',$this->title_name);
        parent::index();
    }
    /**
     * 列表数据的后续处理
     */
    protected function form_index_list(&$list) {
        $userIDArr = array();
        foreach($list as $key => $value){
            $list[$key]['start_time_format'] = to_date($value['start_time']);
            $list[$key]['success_time_format'] = to_date($value['success_time']);
            $list[$key]['repay_start_time_format'] = to_date($value['repay_start_time'],'Y-m-d');
            $type_name = DealLoanTypeModel::instance()->getLoanNameByTypeId($value['type_id']);
            $list[$key]['type_name'] = $type_name;
            $dealRepay = DealRepayModel::instance()->getExpectRepayStat($value['id']);
            $list[$key]['principal'] =  empty($dealRepay['principal']) ? 0 : $dealRepay['principal'];
            $list[$key]['interest'] =  empty($dealRepay['interest']) ? 0 : $dealRepay['interest'];
            // 去重
            if (!isset($userIDArr[$value['user_id']])){
                $userIDArr[$value['user_id']] = $value['user_id'];
            }
        }

        $listOfBorrowInfo = array();
        if(!empty($userIDArr)){
            $listOfBorrowInfo =  UserService::getUserInfoForContractByUserId($userIDArr);
        }
        foreach($list as $key => $value){
            $list[$key]['id_no'] = !empty($listOfBorrowInfo[$value['user_id']]['enterprise']['credentials_no']) ? $listOfBorrowInfo[$value['user_id']]['enterprise']['credentials_no']
                : $listOfBorrowInfo[$value['user_id']]['user']['idno'];
            $length=  mb_strlen($list[$key]['id_no']);
            $hideLength = ($length -4) > 0 ? 4 : 0;
            $list[$key]['id_no'] =  mb_substr($list[$key]['id_no'], 0, $length-$hideLength) . str_repeat("*", $hideLength);
        }

    }
}
?>
