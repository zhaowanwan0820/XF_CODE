<?php
// +----------------------------------------------------------------------
// | 合同管理
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------
use core\service\contract\ContractService;
use core\service\contract\ContractNewService;
use core\service\contract\ContractSignService;
use core\service\contract\ContractInvokerService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\contract\TplService;

use core\dao\contract\DealContractModel;
use core\dao\contract\ContractContentModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealAgencyModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\contract\OpLogModel;
use core\dao\OpStatusModel;

use libs\utils\Logger;
use core\enum\contract\ContractServiceEnum;

class ContractAction extends CommonAction
{
    // 首页
    public function index()
    {
        $where_array = array();
        $userIds = array();
        if (intval($_REQUEST ['deal_id']) > 0) {
            $deal_info = DealModel::instance()->getDealInfo($_REQUEST ['deal_id'],true);
        }

        if (!empty($deal_info)) {
            // 看是否是附件合同
            $cont_new_service = new ContractNewService();
            if ($cont_new_service->isAttachmentContract($deal_info['contract_tpl_type'])) { // 合同附件
                $cont_list = (new ContractService())->getContractAttachmentByDealLoad($deal_info);
                $this->assign('list', $cont_list);
                $this->display('indexAttachment');
                return;
            }
        }
        if (is_numeric($deal_info['contract_tpl_type'])) {
            if (trim($_REQUEST['cname'])) {
                $where_array[] = "title = '" . trim($_REQUEST['cname']) . "'";
            }
            if (trim($_REQUEST['cnum'])) {
                $where_array[] = "number = '" . trim($_REQUEST['cnum']) . "'";
            }
            if (trim($_REQUEST['cuser_name'])) {
                $user_id_all = UserService::getUserIdByRealName($_REQUEST['cuser_name']);
                if ($user_id_all) {
                    $where_array[] = "(user_id in (" . implode(',', $user_id_all) . ") OR borrow_user_id in (" . implode(',', $user_id_all) . "))";
                }
            }

            if (trim($_REQUEST['cuser_id']) != '') {
                $where_array[] = "(user_id = " . intval(trim($_REQUEST['cuser_id'])) . ' OR borrow_user_id = ' . intval(trim($_REQUEST['cuser_id'])) . ')';
            }
            $where = $where_array ? implode(' and ', $where_array) : '';
            $p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
            $contractResponse = ContractService::getContractByDealId($_REQUEST['deal_id'], intval($p), $where, 0);

            $isNewCont = 1;
            $listRows = $contractResponse['list'];
            foreach ($listRows as &$listRow) {
                if (!in_array($listRow['type'], array(1, 5))) {
                    $isNewCont = 0;
                }
                if (!empty($listRow['user_id'])) {
                    $userIds[] = $listRow['user_id'];
                }
                if (!empty($listRow['borrow_user_id'])) {
                    $userIds[] = $listRow['borrow_user_id'];
                }

                $agency_model = new DealAgencyModel();
                // 担保机构
                $agency_user = $agency_model->getDealAgencyById($listRow ['agency_id'])->_row;
                foreach ($agency_user as $k => &$v) {
                    if (!empty($v['user_id'])) {
                        $userIds[] = $v['user_id'];
                    }
                }
                $listRow['agency_user'][] = $agency_user;
                $listRow['agency_name'] = $agency_user['name'];
                // 咨询机构
                $advisory_user = $agency_model->getDealAgencyById($listRow ['advisory_id'])->_row;
                foreach ($advisory_user as $k => &$v) {
                    if (!empty($v['user_id'])) {
                        $userIds[] = $v['user_id'];
                    }
                }
                $listRow['advisory_user'][] = $advisory_user;
                $listRow['advisory_name'] = $advisory_user['name'];

                // 委托机构
                $entrust_user = $agency_model->getDealAgencyById($listRow ['entrust_agency_id'])->_row;
                foreach ($entrust_user as $k => &$v) {
                    if (!empty($v['user_id'])) {
                        $userIds[] = $v['user_id'];
                    }
                }
                $listRow['entrust_user'][] = $entrust_user;
                $listRow['entrust_name'] = $entrust_user['name'];
                // 渠道机构
                $canal_user = $agency_model->getDealAgencyById($listRow ['canal_agency_id'])->_row;
                foreach ($canal_user as $k => &$v) {
                    if (!empty($v['user_id'])) {
                        $userIds[] = $v['user_id'];
                    }

                }
                $listRow['canal_user'][] = $canal_user;
                $listRow['canal_name'] = $canal_user['name'];
                // 出借金额
                $listRow['load_money'] = $GLOBALS ['db']->getOne("select money from " . DB_PREFIX . "deal_load where id = " . $listRow ['deal_load_id']);
            }
            $userList = $this->getUserInfoByIds($userIds);
            $sign_num_limit = $GLOBALS['dict']['CONT_SIGN_NUM'];
            $this->assign('user_info', $userList);
            $p = new Page ($contractResponse['count']['num'], 10);
            $this->assign('is_new_cont', $isNewCont);
            $this->assign('page', $p->show());
            $this->assign('list', $listRows);
            $this->assign('sign_num_limit', $sign_num_limit ? $sign_num_limit : 20);
            $this->assign('deal_id', intval($_REQUEST ['deal_id']));
            $this->display('contractNew');
            exit();

        }

        $sign_num_limit = $GLOBALS['dict']['CONT_SIGN_NUM'];
        $this->assign('sign_num_limit', $sign_num_limit ? $sign_num_limit : 20);
        $this->assign('deal_id', intval($_REQUEST ['deal_id']));
        $this->display();
    }

    /**
     * 获取用户姓名
     * @param id 用户id
     * @return string
     */
    private function get_real_name($id)
    {
        if (!$id) {
            return false;
        }
        $userinfo = UserService::getUserById($id, ' * ');
        $user_name = !empty($userinfo['real_name']) ? $userinfo['real_name'] : $userinfo['user_name'];
        return $user_name;
    }

    /**
     * 合同所属用户角色显示
     */
    private function contract_character($role)
    {
        $character = array(
            1 => array('role' => 1, 'name' => '借款人'),
            2 => array('role' => 2, 'name' => '出借人'),
            3 => array('role' => 3, 'name' => '保证人'),
            4 => array('role' => 4, 'name' => '担保公司'),
            5 => array('role' => 5, 'name' => '资产管理方'),
        );
        return $character[$role];
    }

    /**
     * export_all
     * 导出一个标下的所有合同
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @access public
     * @return void
     */
    function export_all()
    {
        $deal_id = $_REQUEST['deal_id'];
        if (empty($deal_id)) {
            if (isset($_REQUEST['id'])) {    //如果没有deal_id 代表是从合同管理页面的导出来的
                $this->export_contract();
                return;
            } else {
                $this->error('deal_id 数据有误');
            }
        }

        $deal_info = $GLOBALS ['db']->getRow("SELECT `id`,`name`,`user_id`, `contract_tpl_type`,`agency_id`,`advisory_id`,`deal_type` FROM " . DB_PREFIX . "deal WHERE id = " . $deal_id);
        if (is_numeric($deal_info['contract_tpl_type'])) {
            $response = ContractService::getContractByDealId($deal_id, null, null, 0);
            /*$contractRequest = new RequestGetContractByDealId();
            $contractRequest->setDealId(intval($deal_id));
            $contractRequest->setSourceType($deal_info['deal_type']);
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Contract",
                'method' => "getContractByDealId",
                'args' => $contractRequest,
            ));*/
            $list = $response['list'];
            $content = iconv("utf-8", "gbk", "合同id,合同标题,合同编号,借款人姓名,借款人签署状态,借款人签署时间,投资人姓名,投资人签署状态,投资人签署时间,担保公司名称,担保公司签署状态,担保公司签署时间,资产管理方名称,资产管理方签署状态,资产管理方签署时间,借款标题,合同创建时间,发送状态,投资金额,状态") . "\n";
            $user_service = new UserService();
            $deal_agency_service = new \core\service\deal\DealAgencyService();
            $agency_info = $deal_agency_service->getDealAgency($deal_info['agency_id']);//担保公司信息
            $advisory_info = $deal_agency_service->getDealAgency($deal_info['advisory_id']);//担保公司信息
            foreach ($list as $val) {
                $borrower_name = UserService::getUserRealName($val['borrow_user_id']);
                if ($val['borrower_sign_time'] > 0) {
                    $borrower_sign_status = '已签署';
                    $borrower_sign_time = date('Y-m-d h:i:s', $val['borrower_sign_time']);
                } else {
                    $borrower_sign_status = '未签署';
                    $borrower_sign_time = '';
                }
                $loan_name = UserService::getUserRealName($val['user_id']);
                if ($val['user_sign_time'] > 0) {
                    $loan_sign_status = '已签署';
                    $loan_sign_time = date('Y-m-d h:i:s', $val['user_sign_time']);
                } else {
                    $loan_sign_status = '未签署';
                    $loan_sign_time = '';
                }

                $agency_name = $agency_info['name'];
                if ($val['agency_sign_time'] > 0) {
                    $agency_sign_status = '已签署';
                    $agency_sign_time = date('Y-m-d h:i:s', $val['agency_sign_time']);
                } else {
                    $agency_sign_status = '未签署';
                    $agency_sign_time = '';
                }

                $advisory_name = $advisory_info['name'];
                if ($val['advisory_sign_time'] > 0) {
                    $advisory_sign_status = '已签署';
                    $advisory_sign_time = date('Y-m-d h:i:s', $val['advisory_sign_time']);
                } else {
                    $advisory_sign_status = '未签署';
                    $advisory_sign_time = '';
                }


                if ($val['borrow_user_id'] == 0) {
                    $borrower_name = '--';
                    $borrower_sign_status = '--';
                    $borrower_sign_time = '--';
                }
                if ($val['user_id'] == 0) {
                    $loan_name = '--';
                    $loan_sign_status = '--';
                    $loan_sign_time = '--';
                }
                if ($val['agency_id'] == 0) {
                    $agency_name = '--';
                    $agency_sign_status = '--';
                    $agency_sign_time = '--';
                }
                if ($val['advisory_id'] == 0) {
                    $advisory_name = '--';
                    $advisory_sign_status = '--';
                    $advisory_sign_time = '--';
                }
                $status = $val['status'] == 0 ? '未盖戳' : '已盖戳';
                if ($val['deal_load_id'] > 0) {
                    $deal_load_info = \core\dao\deal\DealLoadModel::instance()->getDealInfoByLoadId($val['deal_load_id']);
                    $money = $deal_load_info['money'];
                } else {
                    $money = 0;
                }
                $row = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                    $val['deal_id'] . '_' . $val['id'], $val['title'], $val['number'], $borrower_name, $borrower_sign_status, $borrower_sign_time, $loan_name, $loan_sign_status, $loan_sign_time, $agency_name, $agency_sign_status, $agency_sign_time, $advisory_name, $advisory_sign_status, $advisory_sign_time, $deal_info['name'], date('Y-m-d h:i:s', $val['create_time']), '已发送', $money, $status);
                $content .= iconv("utf-8", "gbk", $row) . "\n";
            }
        } else {
            $name = $this->getActionName();
            $model = D($name);
            $voList = $model->where(array('deal_id' => $deal_id))->order("id desc")->findAll();
            $list = $this->processContractList($voList, $deal_info, true);
            $content = iconv("utf-8", "gbk", "合同id,合同标题,合同编号,角色,用户姓名,预签状态,签署状态,签署时间,借款标题,合同创建时间,发送状态,合同状态,投资金额,状态") . "\n";
            foreach ($list as $val) {
                $sign_before = $val['agency_id'] > 0 ? '--' : '已预签';
                $val['send_status'] = $val['is_send'] == 0 ? '未发送' : '发送成功';
                $val['status'] = $val['status'] == 0 ? '无效' : '有效';
                $val['money'] = $val['money'] == 0 ? '--' : $val['money'];
                $contract_time = $val['contract_time'] ? date('Y-m-d H:i:s', $val['contract_time']) : '';

                $row = sprintf("%s,%s,\t%s,%s,%s,%s,%s,\t%s,\"%s\",\t%s,%s,%s,%s,%s",
                    $val['id'], $val['title'], $val['number'], $val['usertype']['name'], $val['user_name'], $sign_before, $val['sign_info'],
                    $contract_time, $deal_info['name'] . ' [id:' . $val['deal_id'] . ']', date('Y-m-d H:i:s', $val['create_time']),
                    $val['send_status'], $val['status'], $val['money'], $val['deal_status_cn']);
                $content .= iconv("utf-8", "gbk", $row) . "\n";
            }
        }

        $datatime = date("YmdHis", time());
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=contract_{$datatime}.csv");
        header('Cache-Control: max-age=0');
        echo $content;
    }

    /**
     * formatArray
     * 按要求格式化 数组
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @param array $list 要格式化的数组
     * @param mixed $keys 按哪些key 来格式化
     * @access public
     * @return void
     */
    function formatArray($list, $keys)
    {
        $new_list = array();
        foreach ($list as $row) {
            if (is_array($keys)) {  //拼接key
                $k_arr = array();
                foreach ($keys as $k) {
                    $k_arr[] = $row[$k];
                }
                $key = implode('_', $k_arr);
            } else {
                $key = $row[$keys];
            }
            $new_list[$key] = $row;
        }
        return $new_list;
    }

    //批量获取用户信息
    function getUserInfoByIds($ids)
    {
        $userinfo = UserService::getUserInfoByIds($ids);
        return $userinfo;

    }

    /**
     * 针对单个合同进行异步签署
     * @param $_REQUEST id 合同id，serviceId:服务id（取决于）
     * @param $_REQUEST serviceId 服务id，类型由 serviceType 决定
     * @param $_REQUEST serviceType 服务类型 1:标的；2:项目 默认为 1
     * @return string-json [errno, errmsg]
     */
    public function signTsaSync()
    {
        $id = intval($_REQUEST['id']);
        $service_id = isset($_REQUEST['serviceId']) ? intval($_REQUEST['serviceId']) : null;
        $service_type = isset($_REQUEST['serviceType']) ? intval($_REQUEST['serviceType']) : ContractServiceEnum::SERVICE_TYPE_DEAL;
        if (empty($id) || empty($service_id) || !in_array($service_type, array(ContractServiceEnum::SERVICE_TYPE_DEAL, ContractServiceEnum::SERVICE_TYPE_PROJECT))) {
            echo json_encode(array('errno' => 1, 'errmsg' => '参数有误'));
            exit;
        }
        if (ContractInvokerService::signOneContractByServiceId('signer', $id, $service_id, $service_type)) {
            echo json_encode(array('errno' => 0));
        } else {
            echo json_encode(array('errno' => 1, 'errmsg' => '签署出错！'));
        }
    }

    /**
     * 针对一个标的 或 项目 进行盖戳
     * @param $_REQUEST id 服务id，类型由 serviceType 决定
     * @param $_REQUEST serviceType 服务类型 1:标的；2:项目 默认为 1
     * @return string-json [errno, errmsg]
     */
    public function signTsaWithId()
    {
        $service_id = intval($_REQUEST['id']);
        $service_type = isset($_REQUEST['serviceType']) ? intval($_REQUEST['serviceType']) : ContractServiceEnum::SERVICE_TYPE_DEAL;
        if (empty($service_id) || !in_array($service_type, array(ContractServiceEnum::SERVICE_TYPE_DEAL, ContractServiceEnum::SERVICE_TYPE_PROJECT))) {
            echo json_encode(array('errno' => 1, 'errmsg' => '参数有误'));
            exit;
        }

        if (ContractInvokerService::signAllContractByServiceId('signer', $service_id, $service_type)) {
            echo json_encode(array('errno' => 0));
        } else {
            echo json_encode(array('errno' => 1, 'errmsg' => '签署出错！'));
        }
    }

    /**
     * 检查某个网贷标的合同打戳情况
     */
    public function checkTsa(){
        $dealIds = $_REQUEST['deal_id'];
        $ids = explode(",",$dealIds);
        $list = array();
        foreach($ids as $one){
            $ret = ContractInvokerService::checkTsaWithDealId('signer',intval($one));
            $list[] = $ret;
        }
        $this->assign ( 'list', $list );
        $this->display();
    }

}

?>
