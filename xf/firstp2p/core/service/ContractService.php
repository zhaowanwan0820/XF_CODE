<?php
/**
 * ContractService.php
 *
 * @date 2014-03-25
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\ContractRenewModel;
use core\dao\LoanOplogModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\ContractModel;
use core\dao\AgencyUserModel;
use core\dao\AgencyContractModel;
use core\dao\ContractContentModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\DealContractModel;
use core\dao\OpLogModel;
use core\dao\JobsModel;
use core\dao\ContractSignSwitchModel;

use core\dao\DealLoanTypeModel;
use core\dao\DealExtModel;

use core\service\DealService;
use core\service\DealLoadService;
use core\service\UserService;
use core\service\EarningService;
use core\service\OpLogService;
use core\service\ContractNewService;

use core\event\ContractSignEvent;
use core\event\DealContractEvent;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Common\Library\ApiService;
use libs\utils\Rpc;
use core\dao\ContractCategoryTmpModel;

/**
 * Class ContractService
 * @package core\service
 */
class ContractService extends ApiService {

    const ROLE_BORROWER = 1; //借款人
    const ROLE_LENDER = 2; //出借人
    const ROLE_GUARANTOR = 3; //保证人（已无保证人）
    const ROLE_AGENCY = 3; //担保公司
    const ROLE_ADVISORY = 4;//资产管理方
    const CONTRACT__TYPE_LOAN = '1';//借款合同，转让合同
    const CONTRACT__TYPE_BORROW_PROTOCAL = '5';

    const CONTRACT_BORROW_PROTOCAL_NUM = 1;//每个标的借款方咨询服务协议份数固定为1份

    private static $funcMap = array(
        /**
         * 根据dealId,id获取合同
         * @return array
         */
        'getContractByCid' => array('dealId','id','sourceType'),

        /**
         * 根据合同编号获取合同记录
         * @param      int    dealId
         * @param   string    number
         * @param      int    sourceType
         * @return array
         */
        'getContractByNumber' => array('dealId','number','sourceType'),
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (isset($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        return self::rpc('contract', 'contract/'.$name, $args);
    }

    /**
     * 查询合同
     *
     * @param int $id 合同id
     * @return array
     */
    public function getContract($id, $need_content = false, $old = false){
        $contact = ContractModel::instance()->find($id, '*', true);
        if($need_content){
            if($old){
                $renewContractInfo = '';
            }else{
                $renewContractInfo = \core\dao\ContractRenewModel::instance()->findByViaSlave("`number`='".$contact['number']."'");
            }

            if($renewContractInfo){
                $text = $renewContractInfo['content'];
            }
            if($text == ''){
                $text = ContractContentModel::instance()->find($id);
            }
            $contact['content'] = $text;
        }
        return $contact;
    }

    /**
     * 获取标的合同数量
     * @param unknown $deal_id
     */
    public function getCountByDealid($deal_id){
        $condition = "deal_id = ':deal_id'";
        $params = array(':deal_id' => intval($deal_id));
        return ContractModel::instance()->countViaSlave($condition, $params);
    }

    /**
     * 获取未签署的合同列表
     * @param unknown $deal_id
     */
    public function getNotSginCountByDealid($deal_id){
        $condition = "`deal_id` = ':deal_id' AND `status` = 0";
        $params = array(':deal_id' => intval($deal_id));
        return ContractModel::instance()->countViaSlave($condition, $params);
    }

    /**
     * 合同列表，查看合同
     *
     * @param int $id 合同id
     * @param bool $is_pre 是否为预览
     * @return array
     */
    public function showContract($id,$old = false){
        $cont_info = $this->getContract($id, true, $old);
        //如果没有合同纪录，直接返回
        if(empty($cont_info)){
            return false;
        }
        // 如果只是没有合同内容，补发合同修复先,不打戳
        if(empty($cont_info['content'])){
            $dealId = $cont_info['deal_id'];
            $this->contractRenew($dealId, '', array($id) ,false);
            $cont_info = $this->getContract($id, true, $old);
        }

        //如果补发完了还尼玛为空，直接扩容 aerospike
        if(empty($cont_info['content'])){
            return false;
        }

        $deal_info = DealModel::instance()->find($cont_info['deal_id'],'contract_tpl_type',true);

        //更新合同的签署时间
        if(in_array($cont_info['type'], array(1,5))){
            if(((substr($deal_info['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal_info['contract_tpl_type'],0,5)) === 'NQYZR')) {
                $update_sign_time = $this->updateContSigntimeNew($cont_info);
            }else{
                $update_sign_time = $this->updateContSigntime($cont_info);
            }

            if($update_sign_time){
                $cont_info = $this->getContract($id, true, $old);
            }
        }elseif($cont_info['type'] == 7){
            $is_update_backtime = $this->updateContBackTime($cont_info);
            if($is_update_backtime){
                $cont_info = $this->getContract($id, true, $old);
            }
        }

        //给指定合同内容去掉nl2br换行
        if (app_conf("CONTRACT_END_TIME") > $cont_info['create_time']){
            $cont_info['content'] = nl2br ($cont_info['content']);
        }

        return $cont_info;
    }

    /**
     * 获取某个用户合同相关的借款列表 NEW
     *
     * @param int $user_id 用户id
     * @param string $limit 分页
     * @return array
     */
    public function getContDealList($user_info, $page = 1, $page_size = 10){

        $user_service = new UserService();
        $deal_service = new DealService();
        $deal_loan_type = new DealLoanTypeService();
        $contract_model = new ContractModel();
        $deal_contract_model = new DealContractModel();


        $user_id = $user_info['id'];

        //判断是否为担保
        $user_agency_info = $user_service->getUserAgencyInfoNew($user_info);
        $is_agency = intval($user_agency_info['is_agency']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $user_advisory_info = $user_service->getUserAdvisoryInfo($user_info);
        $is_advisory = intval($user_advisory_info['is_advisory']);

        //委托机构
        $user_entrust_info = $user_service->getUserEntrustInfo($user_info);
        $is_entrust = intval($user_entrust_info['is_entrust']);

        $deal_list = array();
        if($is_agency == 1){
            $deal_list = $deal_contract_model->getAgencyUserContDeals($user_id, $user_agency_info['agency_info'], $page, $page_size);
        }
        else if($is_advisory == 1){
            $deal_list = $deal_contract_model->getAgencyUserContDeals($user_id, $user_advisory_info['advisory_info'], $page, $page_size);
        }else if($is_entrust == 1){
            $deal_list = $deal_contract_model->getAgencyUserContDeals($user_id, $user_entrust_info['entrust_info'], $page, $page_size);
        }
        else
        {
            $deal_model = new DealModel();
            $is_borrower = $deal_model->isBorrowUser($user_id);
            if ($is_borrower) {
                $contract_new_service = new ContractNewService();
                $deal_list = $contract_new_service->getBorrowUserContDeals($user_id, $page, $page_size);
                $request = new \NCFGroup\Protos\Duotou\RequestCommon();
                $rpc = new Rpc('duotouRpc');
                foreach($deal_list['list'] as $key => &$deal_one){
                    if($deal_service->isDealDT($deal_one['id'])){
                        $dealIdArr[] = $deal_one['id'];
                    }
                }
                if(count($dealIdArr) > 0){
                    $vars = array(
                        'deals' => $dealIdArr,
                    );
                    $request->setVars($vars);
                    $response = $rpc->go('NCFGroup\Duotou\Services\DealMapping', 'isHasMappingP2pDeals', $request);
                    if($response['errCode'] === 0){
                        $hasLoanMapping = $response['data'];
                    }
                }
            } else {
                $deal_list = $contract_model->getUserContDeals($user_id, true, $page, $page_size, false);
            }
        }

        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);

        if(isset($deal_list['list'])){
            $deal_model = new DealModel();
            foreach($deal_list['list'] as &$deal_one){
                //当前登录用户 该借款是否已经签署通过
                $is_have_sign = 1;
                if($is_agency || $is_advisory|| $is_entrust|| $deal_one['user_id'] == $user_id){
                    $is_have_sign = $deal_one['sign_status'];
                }
                $isBxt = 0;
                $userNameTitle = 0; // 0 代表‘融资人’
                $dealExtInfo = DealExtModel::instance()->getDealExtByDealId($deal_one['id']);
                if($deal_one['type_id'] == $bxtTypeId){
                    $deal_one['max_rate'] = number_format($dealExtInfo['max_rate'],2);
                    $isBxt = 1;
                    $userNameTitle = 1; // 1 代表‘受托人’
                }
                $isDealZX = $deal_service->isDealEx($deal_one['deal_type']);
                $deal_one['isDealZX'] = $isDealZX;
                if(isset($hasLoanMapping[$deal_one['id']])){
                    $deal_one['dt_mapping'] = $hasLoanMapping[$deal_one['id']];
                }else{
                    $deal_one['dt_mapping'] = -1;
                }
                $deal_user_info = UserModel::instance()->find($deal_one['user_id'], 'real_name', true);
                $deal_one['old_name'] = $deal_one['name'];
                $deal_one['name'] = msubstr($deal_one['name'], 0, 24);
                $deal_one['borrow_amount_format_detail'] = format_price($deal_one['borrow_amount'] / 10000,false);
                if($deal_one['deal_type'] == 0){
                    $deal_one['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE_CN'][$deal_one['loantype']];
                }else{
                    $deal_one['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal_one['loantype']];
                }
                $deal_one['income_fee_rate_format'] = number_format($deal_one['income_fee_rate'], 2);
                $deal_one['is_have_sign'] = $is_have_sign;
                $deal_one['user_real_name'] = $deal_model->getDealUserName($deal_one['user_id']);

                $deal_one['isBxt'] = $isBxt;
                $deal_one['user_name_title'] = $userNameTitle;
                $deal_one['income_base_rate'] = number_format($dealExtInfo['income_base_rate'],2);
            }
        }
        return $deal_list;
    }

    /**
     * 获取用户某个借款的合同列表
     *
     * @param int $user_id 用户id
     * @param int $deal_id 借款id
     * @param string $limit 分页
     * @return array
     */
    public function getDealContList($user_id, $deal_id, $is_agency, $page = 1, $page_size = 10, $agency_id = null){
        //获取合同列表
        $option = array(
            'page' => $page,
            'page_size' => $page_size,
            'make_page' => true,
            'need_list' => true,
            'only_need_sign' => false,
            'agency_id' => $agency_id
        );
        $contract_model = new ContractModel();
        $contract_list = $contract_model->getDealConts($user_id, $deal_id, $is_agency, $option);

        //组织列表数据
        foreach ( $contract_list['list'] as &$cont ) {
            //当前用户该合同的签署状态
            $cont = array_merge($cont, $contract_model->getContSignStatus($cont, $user_id));
            //为借款合同展示期限、金额
            if ($cont['type'] == 1){
                $bid_info = $contract_model->getLoadByCont($cont);
                if($bid_info){
                    $cont['bid_money'] = format_price($bid_info['money'], false);
                }
            }
        }
        return $contract_list;
    }

    /**
     * 签署单条合同
     * @param $cont_id 合同id
     * @param $user_id 用户id
     * @return boolean
     */
    public function signOneContNew($cont_info, $user_info){
        if(!$this->checkContractNew($cont_info, $user_info, true)){
            return false;
        }
        $contract_model = new ContractModel();
        $res = $contract_model->contSignById($cont_info['id']);
        if($res){
            $contract_model->syncContractStatus($cont_info['id']);
            $this->checkContAllSign($cont_info['deal_id']);
        }
        return $res;
    }

    /**
     * 二次签署
     * @param $cont_id 合同id
     * @param $user_id 用户id
     * @return boolean
     */
    public function resignOneContNew($cont_info, $user_info, $status){
        if(!$this->checkContractNew($cont_info, $user_info, false)){
            return false;
        }
        return ContractModel::instance()->contResignById($cont_info['id'], $status);
    }

    /**
     * 将一键签署换成异步任务，前台点击处理更新deal_contract表并添加异步任务
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     * @param $admID 后台代理借款人签署合同的id
     * @return bool
     */
    public function signAll($deal_id, $user_id, $is_agency=0, $agency_id = 0, $admID = 0 ,$autoSign = false) {
        $deal_contract_model = new DealContractModel();
        try {
            $GLOBALS['db']->startTrans();
            if ($deal_contract_model->startSign($deal_id, $user_id, $is_agency, $agency_id, $admID) === false) {
                throw new \Exception("strat contract sign fail");
            }

            $dealService = new DealService();
            $deal_info = $dealService->getDeal($deal_id, true, false);

            if (is_numeric($deal_info['contract_tpl_type'])) {
                //获取用户role
                if($is_agency == 0){
                    if($user_id == $deal_info['user_id']){
                        $role = 1;
                    }else{
                        throw new \Exception("borrow user error!");
                    }
                }else{
                    if($agency_id == $deal_info['agency_id']){
                        $role = 2;
                    }elseif($agency_id == $deal_info['advisory_id']){
                        $role = 3;
                    }else{
                        throw new \Exception("agency error");
                    }
                }

                $contractService = new ContractNewService();
                $sign_info = $contractService->signAll($deal_id, $role, $user_id, $admID ,$autoSign);
            } else {
                throw new \Exception("deal contract tpl type is fail");
            }
            if (!$sign_info || $sign_info['status'] != 0) {
                throw new \Exception("contract sign add jobs fail");
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $user_id, $is_agency, $e->getMessage(), $e->getLine())));
            return false;
        }
        return true;
    }

    /**
     * 一键签署,借款人或担保公司单方的（前台调用）
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     */
    public function signDealContNew($deal_id, $user_id, $is_agency = 0, $agency_id = 0){
        try {
            $GLOBALS['db']->startTrans();
            $res = ContractModel::instance()->contSignByDeal($deal_id, $user_id, $is_agency,  $agency_id);

            if ($res === true) {
                $this->checkContAllSign($deal_id);
            } else {
                throw new \Exception("contract sign false");
            }

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
    }

    /**
     * 一键签署,签署整个标的（后台调用）
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     */
    public function signDealAllCont($deal_id){
        ContractModel::instance()->contSignAllByDeal($deal_id);
        return $this->checkContAllSign($deal_id);
    }

    /**
     * 获取一个标某个用户的签署信息
     * @param $deal_id 标的id
     * @param $user_id 用户id
     * @param $is_agency 是否担保公司
     * @return array
     */
    public function getDealContByUser($deal_id, $user_id, $is_agency=0) {
        return DealContractModel::instance()->getDealContByUser($deal_id, $user_id, $is_agency);
    }

    /**
     * 获取一个标某个用户已签署数据
     * @param $deal_id 标的id
     * @param $user_id 用户id
     * @param $is_agency 是否担保公司
     * @param $contract_count 已知合同总数
     * @return array
     */
    public function getContSignNum($deal_id, $user_id, $is_agency = 0, $contract_count = 0, $agency_id = null){
        //获取合同总数
        if($contract_count == 0){
            $contract_model = new ContractModel();
            if($is_agency){
                $option = array('need_list' => false,'only_need_sign' => true,'agency_id' => $agency_id);
            }else{
                $option = array('need_list' => false,'only_need_sign' => true);
            }
            $contract = $contract_model->getDealConts($user_id, $deal_id, $is_agency, $option);
            $contract_count = $contract['count'];
        }
        //contract表的签署数量
        $sign_count = ContractModel::instance()->getContSignNumByDeal($deal_id, $user_id, $is_agency, $agency_id);

        //旧的签署表的签署数量
        if($sign_count < $contract_count){
            $sign_count += AgencyContractModel::instance()->getContSignNumByDeal($deal_id, $is_agency, $agency_id);
        }

        //返回数据
        $res['is_sign_all'] = $contract_count <= $sign_count ? true : false;
        $res['contract_num'] = $contract_count;
        $res['sign_num'] = $sign_count;
        return $res;
    }

    /**
     * 检查标是否已签署完
     * @param unknown $deal_id
     * @param unknown $deal_user_id
     * @param int 生成pdf的版本
     */
    public function checkContAllSign($deal_id){
        $deal_info = DealModel::instance()->find($deal_id, 'user_id', true);
        if(empty($deal_info)){
            return false;
        }
        //获取所有需要签的合同总数
        $need_sign_num = ContractModel::instance()->getContNeedSignNumByDeal($deal_id, $deal_info['user_id']);
        //获取所有已签总数
        $have_sign_num = ContractModel::instance()->getContSignAllNumByDeal($deal_id, $deal_info['user_id']);

        if($have_sign_num < $need_sign_num){
            $have_sign_num += AgencyContractModel::instance()->getContSignAllNumByDeal($deal_id);
        }

        if($have_sign_num >= $need_sign_num){
            // 更新该借款的所有合同状态为已通过
            $set = array('status' => 1);
            $where = sprintf("`deal_id` = '%d'", $deal_id);
            $update = ContractModel::instance()->updateAll($set, $where, true);
            if($update){
                //时间戳
                //$this->startSignAllContract($deal_id);
                $event = new \core\event\SendContractMsgEvent($deal_id);
                $task_obj = new GTaskService();
                $task_id = $task_obj->doBackground($event, 3);
                if (!$task_id) {
                    Logger::wLog('添加task失败|SendContractMsgEvent', Logger::INFO, Logger::FILE);
                }
                return true;
            } else {
                Logger::wLog('更新合同状态失败|deal_id:' . $deal_id, Logger::INFO, Logger::FILE);
                throw new \Exception("update contract status fail");
            }
        }
        return false;
    }


    /*
    * 开始针对dealid这个标进行合同的异步签署(批量)
    */
    public function startSignAllContract($deal_id){
        //$list = ContractModel::instance()->getContListByDealId($deal_id);
        $list = ContractModel::instance()->getContractIdNumbersByDealId($deal_id);
        //监控进入gm合同数量
        $monitorNum = count($list);
        $succ = 0;
        $errorContractIds = array();
        $obj = new GTaskService();
        foreach($list as $one){
            $ret = $this->startSignOneContract($obj,$one['id'],$one['number'],$deal_id);
            if($ret === true){
                $succ ++;
            }else{
                $errorContractIds[] = $one['id'];
            }
        }

        if($monitorNum != $succ){
            $alertData = array(
                'deal_id'=>$deal_id,
                'needTsaCount'=>$monitorNum,
                'realTsaCount'=>$suc,
                'errorContractId'=>$errorContractIds,
            );
            \libs\utils\Alarm::push('tsacheck', 'tsacheck 时间戳入队报警', json_encode($alertData));
        }
        return true;
    }
    /*
    * 开始针对contractId这个标进行合同的异步签署(单发)
    */
    public function startSignOneContract($taskService, $contractId, $contractNum, $dealId){

        // 存在的且状态是已经打过的就不打了。米等用
        $exist = ContractFilesWithNumModel::instance()->getAllByContractNum($contractNum);
        if(!empty($exist) && $exist[0]['status'] == ContractFilesWithNumModel::TSA_STATUS_DONE){
            Logger::wLog(implode(" | ", array(__CLASS__, __FUNCTION__, $contractId, $contractNum, "already exist !")), Logger::INFO, Logger::FILE);
            return true;
        }else{
            if(empty($exist)){
                // 现插入一发,状态为0。
                $fileRet = ContractFilesWithNumModel::instance()->addNewRecord($contractId,
                        $contractNum,ContractFilesWithNumModel::FDFS_DEFAULT,ContractFilesWithNumModel::FDFS_DEFAULT,$dealId);
            }
            $event = new ContractSignEvent($contractId);
            $res = $taskService->doBackground($event, 20, Task::PRIORITY_NORMAL, null, 'domq_cpu');
            if (!$res) {
                Logger::wLog(implode(" | ", array(__CLASS__, __FUNCTION__, $contractId, $event)), Logger::INFO, Logger::FILE);
                return false;
            }
            return true;
        }
    }


    /**
     * 借款的所有合同签署通过后下发合同
     *
     * @param int $deal_id 借款id
     * @return bool
     */
    public function sendContAfterSign( $deal_id ) {

        //更新该借款的所有合同状态为已通过
        $data = array('status' => 1);
        $table = ContractModel::instance()->tableName();
        $update = ContractModel::instance()->db->autoExecute($table, $data, 'UPDATE', "deal_id = ".$deal_id);

        //合同下发的通知
        if($update){
            $send_res = send_contract_sign_email($deal_id);
            if($send_res){
                return true;
            }
        }
        return false;
    }

    /**
     * 更新借款某笔投资的所有《借款合同》的签署时间
     *
     * @param int $user_id 用户id
     * @param string $contnum 合同编号
     * @param int $time 签署时间（时间戳）
     * @return bool
     */
    public function makeLoanContSignTime($deal_id, $contnum, $time = ''){
        $time = empty($time) ? time() : $time;
        //查出和当前借款合同 同属一条投资的 所有借款合同
        $cont_all = ContractModel::instance()->getContListByDeal($deal_id, $contnum);
        if($cont_all){
            $content_model = new ContractContentModel();
            foreach($cont_all as $cont_one){
                $content = $content_model->find($cont_one['id']);
                $content = empty($content) ? $cont_one['content'] : $content;

                //替换借款合同乙方时间为 当前时间，签署时间替换之后，保留特殊标记，用于以后获取
                $replace = "<span id='borrow_sign_time'>".date("Y年m月d日",$time)."</span>";
                $content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/",$replace,$content);
                $res = $content_model->update($cont_one['id'], $content);
                //删除已生成的合同文件
                if($res){
                    $this->unlinkContFile($cont_one['id']);
                }
            }
        }
        return true;
    }

    /**
     * 新合同逻辑-更新借款某笔投资的所有《借款合同》的签署时间
     *
     * @param int $user_id 用户id
     * @param string $contnum 合同编号
     * @param int $time 签署时间（时间戳）
     * @return bool
     */
    public function makeLoanContSignTimeNew($deal_id, $contnum, $time = '',$type = ''){
        $time = empty($time) ? time() : $time;
        //查出和当前借款合同 同属一条投资的 所有借款合同
        $cont_all = ContractModel::instance()->getContListByDeal($deal_id, $contnum);
        if($cont_all){
            $content_model = new ContractContentModel();
            foreach($cont_all as $cont_one){
                $content = $content_model->find($cont_one['id']);
                $content = empty($content) ? $cont_one['content'] : $content;
                if($type == 'borrower'){
                    $replace = "<span id='borrow_sign_time'>".date("Y年m月d日",$time)."</span>";
                    $content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/",$replace,$content);
                }elseif($type == 'advisory'){
                    $replace = "<span id='advisory_sign_time'>".date("Y年m月d日",$time)."</span>";
                    $content = preg_replace("/\<span[\s]*id\=\'advisory_sign_time\'\>.*?\<\/span\>/",$replace,$content);
                }elseif($type == 'agency'){
                    $replace = "<span id='agency_sign_time'>".date("Y年m月d日",$time)."</span>";
                    $content = preg_replace("/\<span[\s]*id\=\'agency_sign_time\'\>.*?\<\/span\>/",$replace,$content);
                }else{
                    return false;
                }
                //替换借款合同乙方时间为 当前时间，签署时间替换之后，保留特殊标记，用于以后获取
                $res = $content_model->update($cont_one['id'], $content);
                //删除已生成的合同文件
                if($res){
                    $this->unlinkContFile($cont_one['id']);
                }
            }
        }
        return true;
    }

    /**
     * 签署《借款人平台服务协议》更新签署时间
     *
     * @param array $continfo 合同信息
     * @param int $time 签署时间（时间戳）
     * @return bool
     */
    public function makeLenderProtocalSignTime($continfo, $time = ''){

        $time = empty($time) ? time() : $time;
        $replace = "<span id='borrow_sign_time'>".date("Y年m月d日",$time)."</span>";
        $content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/",$replace,$continfo['content']);

        $content_model = new ContractContentModel();
        $res = $content_model->update($continfo['id'], $content);

        //删除已生成的合同文件
        if($res){
            $this->unlinkContFile($continfo['id']);
        }
        return $res;
    }

    /**
     * 合同新签署规则-《借款人平台服务协议》更新签署时间
     *
     * @param array $continfo 合同信息
     * @param int $time 签署时间（时间戳）
     * @return bool
     */
    public function makeLenderProtocalSignTimeNew($deal_id, $contnum, $time = '',$type = ''){
        $time = empty($time) ? time() : $time;
        //查出和当前借款合同 同属一条投资的 所有借款合同
        $cont_all = ContractModel::instance()->getLenderProtocalDeal($deal_id, $contnum);
        if($cont_all) {
            $content_model = new ContractContentModel();
            foreach ($cont_all as $cont_one) {
                $content = $content_model->find($cont_one['id']);
                $content = empty($content) ? $cont_one['content'] : $content;
                if ($type == 'borrower') {
                    $replace = "<span id='borrow_sign_time'>" . date("Y年m月d日", $time) . "</span>";
                    $content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/", $replace, $content);
                } elseif ($type == 'advisory') {
                    $replace = "<span id='advisory_sign_time'>" . date("Y年m月d日", $time) . "</span>";
                    $content = preg_replace("/\<span[\s]*id\=\'advisory_sign_time\'\>.*?\<\/span\>/", $replace, $content);
                } else {
                    return false;
                }
                $res = $content_model->update($cont_one['id'], $content);
                //删除已生成的合同文件
                if ($res) {
                    $this->unlinkContFile($cont_one['id']);
                }
            }
        }
        return true;
    }

    /**
     * 删除合同pdf文件
     *
     * @param int $cont_id 合同id
     * @param int $cont_num 合同编号
     * @return bool
     */
    public function unlinkContFile($cont_id){
        //删除关联的attach_id，下载合同时会再次生成
        $data = array('id' => $cont_id, 'attach_id' => 0);
        return $this->updateContInfo($data);
    }

    /**
     * 验证某个用户对于某个合同的拥有权限
     *
     * @param int $cont_id 合同id
     * @param int $user_id 用户id
     * @return bool
     */
    public function checkContractNew($cont_info, $user_info, $check_can_sign = false){
        if(empty($cont_info)){
            return false;
        }
        // 委托投资标的说明 用户无关
        if (99 == $cont_info['type']) {
            return true;
        }

        $user_id = $user_info['id'];
        \FP::import("libs.common.dict");
        $deal_info = DealModel::instance()->find($cont_info['deal_id'], '`user_id`,`contract_tpl_type`', true);
        //用户为借款人
        if(($cont_info['borrow_user_id'] == $user_info['id'])||($cont_info['user_id'] == $user_info['id'])){
            if($check_can_sign){
                //只有是借款人并且类型不是7 的情况可以签署
                return ($cont_info['type'] != 7 && $cont_info['user_id'] == $user_id && $deal_info['user_id'] == $user_id) ? true : false;
            }else{
                return true;
            }
        }else{
            $agency_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['agency_id'], $user_info['user_name'], $user_info['id']);
            if(!empty($agency_user)) {
                return true;
            }

            $advisory_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['advisory_id'], $user_info['user_name'], $user_info['id']);
            if(!empty($advisory_user)) {
                return true;
            }

            $entrust_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['entrust_agency_id'], $user_info['user_name'], $user_info['id']);
            if(!empty($entrust_user)) {
                return true;
            }

            return false;
        }

        //如果是借款人或投资人的合同
        if($cont_info['user_id'] > 0){
            if($check_can_sign){
                //只有是借款人并且类型不是7 的情况可以签署
                return ($cont_info['type'] != 7 && $cont_info['user_id'] == $user_id && $deal_info['user_id'] == $user_id) ? true : false;
            }else{
                return ($cont_info['borrow_user_id'] == $user_info['id']||$cont_info['user_id'] == $user_info['id']) ? true : false;
            }
        //汇赢的
        }elseif($deal_info['contract_tpl_type'] == 'HY' && $cont_info['agency_id'] > 0){
            return in_array($user_info['user_name'], \dict::get('HY_DB')) ? true : false;
        //如果是普通担保公司
        }else{
            $agency_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['agency_id'], $user_info['user_name'], $user_info['id']);
            if(empty($agency_user)) {
                if(isset($cont_info['advisory_id']) && $cont_info['advisory_id'] > 0){
                    $advisory_user = AgencyUserModel::instance()->getAgencyUsers($cont_info['advisory_id'], $user_info['user_name'], $user_info['id']);
                    return empty($advisory_user) ? false : true;
                }
                return false;
            }else{
                return true;
            }
        }
    }

    /**
     * 合同下载
     * @param int $cont_id 合同id
     * @param string $cont_num 合同编号
     * @param string $cont_content 合同内容
     * @return string
     */
    public function contractDownload($cont_id){
        $cont = $this->showContract($cont_id);
        if(empty($cont)){
            return false;
        }
        $attach_id = $cont['attach_id'] ? $cont['attach_id'] : $this->makeContVfsPdf($cont);
        if($attach_id){
            $attach_info = \core\dao\AttachmentModel::instance()->find($attach_id);
            if($attach_info){
                header ( "Content-type: application/octet-stream" );
                header ( 'Content-Disposition: attachment; filename="'.basename($attach_info['attachment']).'"');
                header ( "Content-Length: " . $attach_info['filesize']);
                echo \libs\vfs\Vfs::read($attach_info['attachment']);
                exit;
            }
        }
        return false;
    }



    /**
     * 补发合同下载
     * @param int $cont_id 合同id
     * @return string
     */
    public function contractDownloadRenew($cont_id,$view=false){
        $cont = $this->showContract($cont_id);
        if(empty($cont) || empty($cont['number'])){
            return false;
        }

        if($view == true){
            return $cont['content'];
        }
        $file_name = $cont['number'].".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $mkpdf = new \Mkpdf ();
        $mkpdf->mk($file_path, $cont['content']);
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;
    }

     /**
     * 是否存在补发合同
     * @param int $cont_id 合同id
     * @return string
     */
    public function contractRenewExist($cont_id){
        $cont = ContractModel::instance()->find($cont_id, '*', true);
        if(empty($cont) || empty($cont['number'])){
            return false;
        }
        $renewContractInfo = \core\dao\ContractRenewModel::instance()->findByViaSlave("`number`='".$cont['number']."'");
        if(empty($renewContractInfo)){
            return false;
        }else{
            return $renewContractInfo;
        }
    }

    /**
     * 合同pdf生成
     * @param type $data
     * @return boolean
     */
    public function makeContVfsPdf($cont_info, $updateAttach = true){
        //本地生成pdf
        $file_name = md5 ( $cont_info['number'] ) . ".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $mkpdf = new \Mkpdf ();
        $mkpdf->mk ( $file_path, $cont_info['content'] );

        //存储到vfs
        $file['name'] = $file_name;
        $file['type'] = 'application/pdf';
        $file['tmp_name'] = $file_path;
        $file['error'] = 0;
        $file['size'] = filesize($file_path);

        $uploadFileInfo = array(
            'file' => $file,
            'asAttachment' => 1,
            'asPrivate' => true,
        );
        $doupload = uploadFile($uploadFileInfo);

        //合同记录关联附件id
        if($doupload['status'] && $updateAttach == true){
            $data = array('id' => $cont_info['id'], 'attach_id' => $doupload['aid']);
            $this->updateContInfo($data);
        }
        @unlink($file_path);
        return $doupload['aid'];
    }

    /**
     * 更新合同信息
     * @param type $data
     * @return boolean
     */
    public function updateContInfo($data){
        $cont_dao = ContractModel::instance();
        if(empty($data['id'])){
            return false;
        }
        $cont_dao->setRow(array('id'=>$data['id']));
        return $cont_dao->update($data);
    }

    /**
     * 更新合同签署时间
     * @param unknown $cont_info
     * @return boolean|Ambigous <boolean, resource>
     */
    public function updateContSigntime($cont_info){
        if(!in_array($cont_info['type'], array(1,5))){
            return false;
        }
        $res = false;
        preg_match("/\<span[\s]*id\=\'borrow_sign_time\'\>(\d{4}年\d{2}月\d{2}日)\<\/span\>/", $cont_info['content'], $match);
        if(empty($match[1])){
            //查询签署时间
            $sign_time = $this->getContSigntime($cont_info);
            if(empty($sign_time)){
                return $res;
            }
            if($cont_info['type'] == 1){
                $res = $this->makeLoanContSignTime($cont_info['deal_id'], $cont_info['number'], $sign_time);
            }elseif($cont_info['type'] == 5){
                $res = $this->makeLenderProtocalSignTime($cont_info, $sign_time);
            }
        }
        return $res;
    }

    /**
     * 合同新规则-更新合同签署时间
     * @param unknown $cont_info
     * @return boolean|Ambigous <boolean, resource>
     */
    public function updateContSigntimeNew($cont_info){
        if(!in_array($cont_info['type'], array(1,5))){
            return false;
        }
        $res = false;

        preg_match("/\<span[\s]*id\=\'borrow_sign_time\'\>(\d{4}年\d{2}月\d{2}日)\<\/span\>/", $cont_info['content'], $match_borrower);

        preg_match("/\<span[\s]*id\=\'advisory_sign_time\'\>(\d{4}年\d{2}月\d{2}日)\<\/span\>/", $cont_info['content'], $match_advisory);

        preg_match("/\<span[\s]*id\=\'agency_sign_time\'\>(\d{4}年\d{2}月\d{2}日)\<\/span\>/", $cont_info['content'], $match_agency);

        $contract_model = new ContractModel();
        $deal_model = new DealModel();
        $deal_info = $deal_model->find($cont_info['deal_id'],'user_id,agency_id,advisory_id');
        if(empty($match_borrower[1])){
            $query_condition = "number = '{$cont_info['number']}' AND user_id = '{$deal_info['user_id']}' AND ";
            if($cont_info['type'] == 1){
                $borrower_sign_time = $contract_model->findByViaSlave($query_condition."deal_id = '{$cont_info['deal_id']}'  AND type = 1",'`sign_time`');
                if($borrower_sign_time['sign_time'] <> 0){
                    $res = $this->makeLoanContSignTimeNew($cont_info['deal_id'], $cont_info['number'], $borrower_sign_time['sign_time'],'borrower');
                }
            }elseif($cont_info['type'] == 5){
                $borrower_sign_time = $contract_model->findByViaSlave($query_condition."deal_id = '{$cont_info['deal_id']}' AND type = 5",'`sign_time`');
                if($borrower_sign_time['sign_time'] <> 0) {
                    $res = $this->makeLenderProtocalSignTimeNew($cont_info['deal_id'], $cont_info['number'], $borrower_sign_time['sign_time'], 'borrower');
                }
            }
        }
        if(empty($match_advisory[1])){
            $query_condition = "number = '{$cont_info['number']}' AND agency_id='{$deal_info['advisory_id']}' AND ";
            if($cont_info['type'] == 1){
                //资产管理方
                $advisory_sign_time = $contract_model->findByViaSlave($query_condition."deal_id = '{$cont_info['deal_id']}'  AND type = 1",'sign_time');
                if($advisory_sign_time['sign_time'] <> 0){
                    $res = $this->makeLoanContSignTimeNew($cont_info['deal_id'], $cont_info['number'], $advisory_sign_time['sign_time'],'advisory');
                }
            }elseif($cont_info['type'] == 5){
                $advisory_sign_time = $contract_model->findByViaSlave($query_condition."deal_id = '{$cont_info['deal_id']}'  AND type = 5",'sign_time');
                if($advisory_sign_time['sign_time'] <> 0) {
                    $res = $this->makeLenderProtocalSignTimeNew($cont_info['deal_id'], $cont_info['number'], $advisory_sign_time['sign_time'], 'advisory');
                }
            }
        }
        if(empty($match_agency[1])){
            if($cont_info['type'] == 1){
                $query_condition = "number = '{$cont_info['number']}' AND agency_id='{$deal_info['agency_id']}' AND ";
                $agency_sign_time = $contract_model->findByViaSlave($query_condition."deal_id = '{$cont_info['deal_id']}'  AND type = 1",'sign_time');
                if($agency_sign_time['sign_time'] <> 0){
                    $res = $this->makeLoanContSignTimeNew($cont_info['deal_id'], $cont_info['number'], $agency_sign_time['sign_time'],'agency');
                }
            }
        }
        return $res;
    }

    /**
     * 获取《借款合同》和《借款人平台服务协议》的签署时间
     * @param $cont_info 合同内容
     * @return int
     */
    public function getContSigntime($cont_info){
        //获取借款人id
        $sign_time = '';
        $deal_info = DealModel::instance()->find($cont_info['deal_id'], 'user_id', true);
        if($deal_info){
            //获取《借款合同》的签署时间
            if($cont_info['type'] == 1){
                //获取合同编号对应的借款人的合同id
                $borrower_cont = ContractModel::instance()->getContListByUser($cont_info['deal_id'], $deal_info['user_id'], $cont_info['number']);
                if($borrower_cont){
                    if($borrower_cont['sign_time']){
                        $sign_time = $borrower_cont['sign_time'];
                    }else{
                        $sign_info = AgencyContractModel::instance()->getAgencyContractByUser($borrower_cont['id'], $deal_info['user_id']);
                        $sign_time = $sign_info ? $sign_info['create_time'] : '';
                    }
                }
            }
            //获取《借款人平台服务协议》的签署时间
            if($cont_info['type'] == 5){
                if($cont_info['sign_time']){
                    $sign_time = $cont_info['sign_time'];
                }else{
                    $sign_info = AgencyContractModel::instance()->getAgencyContractByUser($cont_info['id'], $deal_info['user_id']);
                    $sign_time = $sign_info ? $sign_info['create_time'] : '';
                }
            }
        }
        return $sign_time;
    }

    /**
     * 根据投标ID获取合同信息 @todo
     * @param $load_id
     * @param bool $deal 可空
     * @param bool $deal_load 可空
     * @param array $contract_type type数组
     * @return bool|\libs\db\Model
     */
    public function getContractByDealLoad($deal_load_id, $deal = false, $deal_load = false, $user_id = '') {
        return ContractModel::instance()->getContractByDealLoad($deal_load_id, $deal, $deal_load, array(1, 4, 5, 7, 8),$user_id);
    }

    /**
     * 更新合同的“回购时间”
     *
     * @param $cont_info 合同信息
     * @return bool
     */
    public function updateContBackTime($cont_info){
        if($cont_info['type'] != 7){
            return false;
        }
        $is_update = false;
        preg_match("/\<span[\s]*id\=\'buyback_time\'\>(\d{4}年\d{2}月\d{2}日)\<\/span\>/", $cont_info['content'], $match);
        if(empty($match[1])){
            //获取借款信息
            if(preg_match("/\<span[\s]*id\=\'buyback_time\'\> 年 月 日\<\/span\>/", $cont_info['content'], $match_new)){
                $deal_info = DealModel::instance()->find($cont_info['deal_id']);
                if($deal_info['repay_start_time']){
                    //还款方式不同，做不同处理
                    if($deal_info['loantype'] == 5){
                        $buyback_time = strtotime("+".$deal_info['repay_time']." day", strtotime(to_date($deal_info['repay_start_time'])));
                    }else{
                        $buyback_time = strtotime("+".$deal_info['repay_time']." month", strtotime(to_date($deal_info['repay_start_time'])));
                    }
                    $this->unlinkContFile($cont_info['id'], $cont_info['number']);
                    $buyback_time_str = "<span id='buyback_time'>".date("Y年m月d日",$buyback_time)."</span>";
                    $content = str_replace($match_new[0], $buyback_time_str, $cont_info['content']);
                    $content_model = new ContractContentModel();
                    $is_update = $content_model->update($cont_info['id'], $content);
                }
            }
        }
        return $is_update;
    }

    /**
     * 补发未发送的合同，通过异步任务
     * @param $deal_id
     * @return bool
     */
    public function contractReissueByOpLog($deal_id){
        $oplog = new OpLogModel();
        $list = $oplog->get_contract_reissue_list($deal_id);
        if($list){
            foreach($list as $row){
                $oplog = new OpLogService();
                $oplog->send_contract($row['deal_id'], $row['load_id'], false);
            }
        }
    }
    /**
     * 根据借款ID补发指定合同类型
     * @param $deal_id
     * @return bool
     */
    public function contractRenew($deal_id, $cont_type = '', $cont_ids = array(), $needTsa = false){

        \FP::import("libs.common.app");
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($deal_id);
        $contract_list = ContractModel::instance()->getRepairContByDeal($deal_id, $cont_type, $cont_ids);
        $num = 0;
        if($deal && $contract_list){
            // 引入异步队列
            $taskService = new GTaskService();

            $contract_libs = new \system\libs\updateContract();  //引入合同操作类
            $dealagency_service = new DealAgencyService();

            $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
            $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
            $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//资产管理方信息

            $guarantor_list = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']);

            $earning_service = new EarningService();
            $all_repay_money = sprintf("%.2f", $earning_service->getRepayMoney($deal['id']));
            $borrow_user_info['repay_money'] = $all_repay_money;
            $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
            $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
            $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
            $borrow_user_info['leasing_money'] = $deal['leasing_money'];
            $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
            $borrow_user_info['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
            $borrow_user_info['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
            $borrow_user_info['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

            $deal_load_model = new DealLoadModel();

            // 这部分代码用来判断重新生成的合同是否已经签署过
            //借款人id
            $borrower_id = $deal['user_id'];

            // 借款人签署条数
            $borrowerContractCount = ContractModel::instance()->getCountOfBorrower($deal_id,$borrower_id);

            // 代理机构签署条数
            $agencyContractCount = ContractModel::instance()->getCountOfAgency($deal_id);

            $timestamp = false;
            if($borrowerContractCount>0 && $agencyContractCount>0){
                $timestamp = true;
            }
            foreach($contract_list as $cont_one){
                $contract_type = $cont_one['type'];
                $role = self::getContractRole($cont_one['user_id'], $cont_one['type'], $cont_one['agency_id'], $deal['user_id']);
                $load_id = $cont_one['deal_load_id'];
                if($load_id <= 0){
                    $load_id = $this->getLoadInfoByCont($cont_one, $role, $borrow_user_info['user_id']);
                }
                if(($contract_type == 5) ||$load_id || ((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                    //投资记录
                    $res = 0;
                    $loan_user_info = $deal_load_model->getLoadDetailInfo($deal['id'], $load_id);
                    if($contract_type == 1){//借款合同
                        if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                            $res = $contract_libs->push_loan_contract_v2($cont_one, $deal, $loan_user_info, $borrow_user_info);
                        }else{
                            $res = $contract_libs->push_loan_contract($cont_one, $deal, $loan_user_info, $borrow_user_info);
                        }
                    }elseif($contract_type == 2){//委托担保合同
                        //担保公司、借款人
                        $res = $contract_libs->push_entrust_warrant_contract($cont_one, $deal, $guarantor_list, $loan_user_info, $borrow_user_info, $agency_info);
                    }elseif($contract_type == 4){//保证合同
                        //出借人、担保公司
                        $res = $contract_libs->push_warrant_contract($cont_one, $deal, $loan_user_info, $borrow_user_info, $agency_info);
                    }elseif($contract_type == 5){//出借人平台服务协议(借款人平台服务协议)
                        if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                            if($role == 1){//借款人
                                $res = $contract_libs->push_borrower_protocal_v2($cont_one, $deal, $borrow_user_info);
                            }elseif($role == 3){//资产管理方
                                $res = $contract_libs->push_borrower_protocal_v2($cont_one, $deal, $borrow_user_info);
                            }
                        }else{
                            if($role == 1){//借款人
                                $res = $contract_libs->push_borrower_protocal($cont_one, $deal, $borrow_user_info);
                            }elseif($role == 2){//出借人
                                $res = $contract_libs->push_lender_protocal($cont_one, $deal, $loan_user_info, $borrow_user_info);
                            }
                        }
                    }elseif($contract_type == 7){//资产收益权回购通知
                        $res = $contract_libs->push_buyback_notification($cont_one, $deal, $loan_user_info, $borrow_user_info);
                    }elseif($contract_type == 99){//资产收益权回购通知
                        $res = $contract_libs->push_project_entrust($cont_one, $deal);
                    }
                    if($res){
                        $this->unlinkContFile($cont_one['id']);
                        $num ++;
                    }

                    // 那就是签过了。这个时候补发合同就需要进行时间戳重新签署，否则不需要重新签署
                    /*
                    // 补发永远不打戳
                    if( $timestamp==true && $needTsa == true){
                        //补发合同
                        $this->startSignOneContract($taskService,$cont_one['id']);
                    }
                    */
                }
            }
        }
        return array('count' => count($contract_list), 'num' => $num);
    }

    /**
     * 获取合同所属用户的角色
     *
     * @param $id 合同id
     * @return bool
     */
    public static function getContractRole($cont_userid, $cont_type, $cont_agencyid, $deal_userid){
        $role = 0;
        if($cont_userid > 0 && $cont_type != 3){
            $role = self::ROLE_LENDER;
            if($deal_userid == $cont_userid){
                $role = self::ROLE_BORROWER;
            }
        }else{
            $role = self::ROLE_GUARANTOR;
            if($cont_userid == 0 && $cont_agencyid > 0){
                $role = self::ROLE_AGENCY;
            }
        }
        return $role;
    }

    /**
     * 获取当下使用的合同分类
     * @return array
     */
    public function getContractType(){
        $condition = "use_status = 1 and is_delete = 0 and is_contract = 1 and type_tag != '' ";
        $type_list = \core\dao\MsgCategoryModel::instance()->findAll($condition, true, 'type_tag,type_name');
        $result = array();
        if($type_list){
            foreach($type_list as $type_info){
                $result[$type_info['type_tag']] = $type_info['type_name'];
            }
        }
        return $result;
    }

    /**
     * 物理删除一个标的合同
     * @param unknown $deal_id
     */
    public function delContByDeal($deal_id){
        $res = ContractModel::instance()->delContByDealId($deal_id);
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $res ? "success" : "fail")));
        return $res;
    }

    /**
     * 根据合同，获取投标记录
     * @return array
     */
    public function getLoadInfoByCont($cont_info, $role, $borrow_user_id){

        $dealload_service = new DealLoadService();
        $load_list = $dealload_service->getDealLoanListByDealId($cont_info['deal_id']);

        if(empty($load_list)){
            return false;
        }

        $deal_service = new DealService();
        $deal_info = $deal_service->getDeal($cont_info['deal_id'], true, false);

        $return = array();
        $contract_type = $cont_info['type'];
        foreach($load_list as $load_one){
            $number = array();
            if($contract_type == 1){
                $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],1);//借款合同
            }elseif($contract_type == 2){
                $number[] = get_contract_number($deal_info, $borrow_user_id, $load_one['id'],2);//委托担保合同
            }elseif($contract_type == 4){
                $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],4);//保证合同
            }elseif($contract_type == 5){
                if($role == 2){
                    $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],5);//出借人平台服务协议
                }elseif($role == 1){
                    $number[] = get_contract_number($deal_info, $borrow_user_id, 000,5);//借款人平台服务协议
                }
            }elseif($contract_type == 6){
                $number[] = get_contract_number($deal_info, $borrow_user_id, 000,6);//出借人平台服务协议
            }elseif($contract_type == 7){    //资产权益回购通知
                $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],7);
            }
            if($number){
                foreach($number as $num){
                    if($num == $cont_info['number']){
                        $return = $load_one;
                        break;
                    }
                }
            }
        }
        return $return;
    }


    /**
     * 获取多投借款合同，转让协议编号
     * @param int $contractType
     * @param int $dtDealId
     * @param int $p2pDealId
     * @param int $redemptionUserId
     * @param int $dealLoadId
     * @return string
     */
    public function createDtDealNumber($contractType,$dtDealId,$p2pDealId,$redemptionUserId,$dealLoadId){
        //合同类型（(01:多投P2P借款合同，09) ,多投标的ID，p2p 标的ID,赎回用户ID，投资ID
        $number = str_pad($contractType,2,"0",STR_PAD_LEFT).str_pad($dtDealId,6,"0",STR_PAD_LEFT).str_pad($p2pDealId,10,"0",STR_PAD_LEFT).str_pad($redemptionUserId,8,"0",STR_PAD_LEFT).str_pad($dealLoadId,10,"0",STR_PAD_LEFT);
        return $number;
    }



    /**
    * 判断某用户是神马角色
    * @param userId, 用户id
    * @param dealId, 标的id
    * @return int  as ROLE_BORROWER,ROLE_LENDER,ROLE_GUARANTOR,ROLE_AGENCY
    */
    public function userRole($userId,$dealId){

        $dealService = new DealService();
        $userService = new UserService();
        $deal = $dealService->getDeal($dealId, true, true);
        $userInfo = $userService->getUserViaSlave($userId);
        $userAgencyInfo = $userService->getUserAgencyInfoNew($userInfo);

        // 是机构
        $isAgency = intval($userAgencyInfo['is_agency']);
        if($isAgency) return self::ROLE_AGENCY;

        // 是借款人
        $isBorrower = ($userId == $deal['user_id']) ? 1 : 0;
        if($isBorrower) return self::ROLE_BORROWER;

        // 是资产管理方
        if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
            $userAdvisoryInfo = $userService->getUserAdvisoryInfo($userInfo);
            $advisoryInfo = $userAdvisoryInfo['advisory_info'];
            $isAdvisory = intval($userAdvisoryInfo['is_advisory']);
        }
        if($isAdvisory) return self::ROLE_ADVISORY;

        // 是投资人
        return self::ROLE_LENDER;
    }

    /***
     * 签署时间戳，更新contract表状态
     * @param dealId, 标的ID
     * @param number, 合同编号
     */
    public function signTsaCallback($dealId,$number,$type=0,$projectId=0){
        $contractModel = new ContractModel();
        try {
            $GLOBALS['db']->startTrans();
            if ($contractModel->signTsaCallback($dealId, $number,$type,$projectId) === false) {
                throw new \Exception("tsa contract sign fail");
            }
            $GLOBALS['db']->commit();

            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis !== NULL){
                $redis->hDel('tsa_deal_'.date('Y-m-d'),$dealId);
            }

            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $number, $e->getMessage(), $e->getLine())));
            return false;
        }

    }

    /***
     * 根据时间段，获取当日所有标的的打戳信息
     * @param start_time, 开始时间（时间戳）
     * @param end_time, 结束时间（时间戳）
     */
    public function getDealTsaInfo($start_time,$end_time){
        $loan_oplog_model = new LoanOplogModel();
        $contract_model = new ContractModel();

        $oplogs = $loan_oplog_model->getDealIdByTime($start_time,$end_time);

        $result = array();

        if(count($oplogs) > 0){
            foreach($oplogs as $oplog){
                $contract_count = $contract_model->countBySql("SELECT COUNT(id) FROM firstp2p_contract WHERE deal_id = '".$oplog['deal_id']."' AND status < 3;");
                if($contract_count > 0){
                    $result[$oplog['deal_id']] = false;
                }
            }
        }

        return $result;
    }


    /***
     * 获取借款人多投宝投资记录的借款合同
     * @param $user_id
     * @param $deal_id
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getBorrowDTContList($user_id, $deal_id, $page = 1, $page_size = 10)
    {
        //获取借款人合同列表
        //获取多投借款人借款方咨询服务协议数量
        $contractModel = new ContractModel();
        $borrowProtocalCount = 1;
        $option = array(
            'page' => $page,
            'page_size' => $page_size,
            'make_page' => true,
            'need_list' => true,
            'only_need_sign' => false,
            'agency_id' => null,
            'deal_type' => self::CONTRACT__TYPE_BORROW_PROTOCAL
        );
        if ((self::CONTRACT_BORROW_PROTOCAL_NUM > ($page - 1) * $page_size)) {
            //取借款人咨询服务协议

            $contract_list = $contractModel->getDealConts($user_id, $deal_id, null, $option);
            foreach($contract_list['list'] as $k => $v){
                if($v['type'] <> self::CONTRACT__TYPE_BORROW_PROTOCAL){
                    unset($contract_list['list'][$k]);
                }
            }

            //取多投宝借款协议
            $dtbCount = $page_size * $page - $contract_list['count'];
        } else {
            //计算偏移量
            if (self::CONTRACT_BORROW_PROTOCAL_NUM > $page_size * ($page - 1)) {
                $start = 0;
            } elseif (self::CONTRACT_BORROW_PROTOCAL_NUM % $page_size == 0) {
                $start = $page_size * ($page - 1) - self::CONTRACT_BORROW_PROTOCAL_NUM;
            } else {
                $start = $page_size * ($page - 1) - self::CONTRACT_BORROW_PROTOCAL_NUM % $page_size;
            }

        }
        $option['page'] = '1';
        $option['page_size'] = '1';
        $option['deal_type'] = '1';
        $contract_loan_list = $contractModel->getDealConts($user_id, $deal_id, null, $option);
        if (count($contract_loan_list) > 0) {
            $loan_title = $contract_loan_list['list'][0]['title'];
        }
        $start = (($start <> '') && ($start >= 0)) ? $start : 0;
        $count = (($dtbCount <> '') && ($dtbCount >= 0)) ? $dtbCount : $page_size;
        if ($start >= 0 && $count >= 0) {
            $request = new \NCFGroup\Protos\Duotou\RequestCommon();
            $rpc = new Rpc('duotouRpc');
            $vars = array(
                'p2p_deal_id' => $deal_id,
                'offset' => $start,
                'num' => $count
            );
            $request->setVars($vars);
            $response = $rpc->go('NCFGroup\Duotou\Services\MappingCollect', 'getCollectMappingByP2pDealId', $request);
            if ($response['errCode'] == 0) {
                $list = $response['data']['list'];
                $dtContNum = $response['data']['count'];
            }
            $i = 0;
            foreach ($list as $key => $value) {
                $dtContractlist[$i]['id'] = $value['id'];
                $dtContractlist[$i]['title'] = $loan_title;
                $dtContractlist[$i]['number'] = str_pad($deal_id, 8, '0', STR_PAD_LEFT) . str_pad(1, 2, '0', STR_PAD_LEFT) . str_pad(10, 2, '0', STR_PAD_LEFT) . str_pad($user_id, 10, '0', STR_PAD_LEFT) . str_pad($value['dtLoanId'], 10, '0', STR_PAD_LEFT);
                $dtContractlist[$i]['type'] = '1';
                $dtContractlist[$i]['dealId'] = $value['p2pDealId'];
                $dtContractlist[$i]['userId'] = $value['userId'];
                $dtContractlist[$i]['bid_money'] = $value['money'];
                $dtContractlist[$i]['isDt'] = 1;
                $i++;
            }
            if (count($contract_list['list']) > 0) {
                $contract_list['list'] = array_merge($contract_list['list'], $dtContractlist);
            } else {
                $contract_list['list'] = $dtContractlist;
            }
        }
        $contract_list['count'] = self::CONTRACT_BORROW_PROTOCAL_NUM + $dtContNum;

        //组织列表数据
        foreach ($contract_list['list'] as &$cont) {
            //当前用户该合同的签署状态
            if ($cont['isDt'] <> 1) {
                $cont = array_merge($cont, $contractModel->getContSignStatus($cont, $user_id));
                //为借款合同展示期限、金额
                if ($cont['type'] == 1) {
                    $bid_info = $contractModel->getLoadByCont($cont);
                    if ($bid_info) {
                        $cont['bid_money'] = format_price($bid_info['money'], false);
                    }
                }
            }
        }
        return $contract_list;
    }
    /**
     * [getEntrustDealInfoList:获取代签状态的代理签署标的的信息列表（JIRA#3255）]
     * @author <fanjingwen@ucfgroup.com>
     * @param int $nowPage [现在的页码]
     * @param int $rowOfPage [每页显示的行数]
     * @param int $condStartTime [option：满标区间的开始时间，时间戳]
     * @param int $condEndTime [option：满标区间的结束时间，时间戳]
     * @param int $condDealID [option：标的id]
     * @param string $condUserIDs [option：标的所属用户的字符串数组，逗号分隔]
     * @return array ['count' => 符合条件的标的总数, 'list' => 标的信息列表]
     */
    public function getEntrustDealInfoList($nowPage, $rowOfPage, $condStartTime, $condEndTime, $condDealID, $condUserIDs)
    {
        $pageStart = $rowOfPage*($nowPage - 1);
        $listOfDealInfo = DealModel::instance()->getEntrustDealInfoList($pageStart, $rowOfPage, $condStartTime, $condEndTime, $condDealID, $condUserIDs);

        return $listOfDealInfo;
    }

    /**
     * 自动代理签署合同
     */
    public function autoAgencySignContract(){
        //获取实时代签打开的开关
        $contractSignSwitchModel = new ContractSignSwitchModel();
        $contractSwitches = $contractSignSwitchModel->getOpenedSwitches();
        if(!empty($contractSwitches)){
            foreach ($contractSwitches as $switch){
                $this->autoSignViaType($switch['type'],$switch['adm_id']);
            }
        }
        return true;
    }

    /**
     * 通过实时代签合同类型签署合同
     * @param int $type
     * @return boolean
     */
    private function autoSignViaType($type, $adm_id){
        //获取委托并且未签署的合同
        $contracts = $this->getUnsignedContactByType($type);
        if(!empty($contracts)){
            foreach($contracts as $contract){
                //不是借款人合同is_agency = 0
                $is_agency = $contract['agency_id'] != 0? 1:0;
                $result = $this->signAll($contract['deal_id'],$contract['user_id'],$is_agency, $contract['agency_id'] ,$adm_id, true);
                if(!$result){
                  Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'type:'.$type, 'error:签署合同添加jobs失败',json_encode($contract))));
                  continue;
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'type:'.$type, 'error:签署合同添加jobs成功',json_encode($contract))));
            }
        }
        return true;
    }

    /**
     * 通过实时代签合同类型获取未签署的合同
     * @param int $type
     * @return array
     */
    public function getUnsignedContactByType($type){
        $dealContractModel = new DealContractModel();
        $condition = '';
        if($type == ContractSignSwitchModel::TypeBorrow){//借款人合同
            $condition = 'dp.entrust_sign = 1 and dc.user_id != 0 and dc.agency_id = 0 and dc.user_id = dp.user_id';
        }elseif($type == ContractSignSwitchModel::TypeAgency){//担保方合同
            $condition = 'dp.entrust_agency_sign = 1 and dc.user_id = 0 and dc.agency_id != 0 and dc.agency_id = d.agency_id';
        }elseif($type == ContractSignSwitchModel::TypeAdvisory){//资产管理方合同
            $condition = 'dp.entrust_advisory_sign = 1 and dc.user_id = 0 and dc.agency_id != 0 and dc.agency_id = d.advisory_id';
        }
        return $dealContractModel->getUnSignedContractByCondition($condition);
    }


    /**
     * 根据合同名（可以不传，支持模糊查询） 查询合同相关信息
     * @param $name
     * @param $page_num
     * @param $page_size
     * @return mixed
     */
    public function getListByTypeName($name, $page_num, $page_size) {
        $pageSize = empty($page_size) ? 5 : intval($page_size);
        $pageNum = empty($page_num) ? 1 : intval($page_num);
        $list = ContractCategoryTmpModel::instance()->getListByTypeName(htmlentities($name), $pageNum, $pageSize);
        return $list;
    }

    /**
     * 智多新 合同编号 落库和查询用
     * @param integer  $loanId   智多新用户投资记录id loanId 受托用户
     * @param integer  $uniqueId
     * @return string 合同编号
     */
    public static function createDtNumber($loanId, $uniqueId)
    {
        // number 最多支持36位
        // 33位
        return  str_pad($loanId, 11, "0", STR_PAD_LEFT) . str_pad($uniqueId, 22, "0", STR_PAD_LEFT);
    }


    /**
     * 标的合同编号
     */
    public static function createDealNumber($dealId,$tplIdentifierId,$userId,$loadId){
        // number 最多支持36位
        $dealId = intval($dealId);
        //编号扩容切兼容模式
        if($dealId >= 100000000){
            // 33位
            return str_pad($dealId,10,"0",STR_PAD_LEFT).'01'.str_pad($tplIdentifierId,3,"0",STR_PAD_LEFT).str_pad($userId,8,"0",STR_PAD_LEFT).str_pad($loadId,10,"0",STR_PAD_LEFT);
        }elseif(($dealId < 100000000) && ($dealId >= 1000000)){
            // 30位
            return str_pad($dealId,8,"0",STR_PAD_LEFT).'01'.str_pad($tplIdentifierId,2,"0",STR_PAD_LEFT).str_pad($userId,8,"0",STR_PAD_LEFT).str_pad($loadId,10,"0",STR_PAD_LEFT);
        }else{
            // 28位
            return str_pad($dealId,6,"0",STR_PAD_LEFT).'01'.str_pad($tplIdentifierId,2,"0",STR_PAD_LEFT).str_pad($userId,8,"0",STR_PAD_LEFT).str_pad($loadId,10,"0",STR_PAD_LEFT);
        }
    }
}
