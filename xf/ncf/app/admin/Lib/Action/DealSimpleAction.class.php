<?php
/**
 *
 * 标的相关
 */

use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealTagModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealAgencyModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\ServiceAuditModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\dao\dealqueue\DealQueueModel;
use core\dao\repay\DealRepayModel;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\vfs\Vfs;
use libs\db\Db;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use core\enum\DealRepayEnum;
use core\enum\DealAgencyEnum;
use core\enum\ServiceAuditEnum;
use core\enum\UserEnum;
use core\enum\UserAccountEnum;
use core\enum\DealProjectEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\EnterpriseEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\P2pDepositoryEnum;
use core\service\user\UserService;
use core\service\deal\DealTagService;
use libs\utils\Finance;
use core\service\deal\DealService;
use core\service\project\ProjectService;
use core\service\makeloans\MakeLoansService;
use core\service\user\VipService;
use core\service\user\BankService;
use core\service\deal\DealSiteService;
use core\service\coupon\CouponService;
use core\service\deal\state\StateManager;
use core\service\account\AccountService;
use core\dao\jobs\JobsModel;
use core\service\repay\DealRepayService;
use core\dao\deal\DealGroupModel;
use core\dao\supervision\SupervisionIdempotentModel;
use core\dao\project\DealProjectModel;
use core\service\contract\ContractService;
use core\service\contract\CategoryService;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\service\msgbus\MsgbusService;
use NCFGroup\Common\Library\Idworker;
use libs\utils\DBDes;

// 加载标的相关函数
FP::import("app.Lib.deal");

class DealSimpleAction extends CommonAction{

    public $deal_data;
    public $deal_ext_data;
    protected $pageEnable = false;
    protected $template = '';
    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型

    /**
     * 列表
     */
    public function index()
    {
        //开始加载搜索条件

        $map['is_delete'] = 0;
        $map['publish_wait'] = 0;
        if(!isset($_REQUEST['deal_status'])){
            $_REQUEST['deal_status']=4;//默认还款中
        }
        if(intval($_REQUEST['site_id']) > 0){
            $sql  ="select deal_id from ".DB_PREFIX."deal_site where site_id =".intval($_REQUEST['site_id']);

            $id_res = $GLOBALS['db']->get_slave()->getAll($sql);
            $id_arr = array();
            $ids = '';
            foreach($id_res as $dealid){
                $id_arr[] = $dealid['deal_id'];
            }
            if($id_arr){
                $ids = implode(',', $id_arr);
            }
            $map['id'] = array("in",$ids);
        }
        // 编号
        $conf_deal_id_1year = app_conf('ADMIN_DEAL_LIST_ID_1YEAR');
        $conf_deal_id_20160521 = app_conf('ADMIN_DEAL_LIST_ID_20160521');
        if (!empty($conf_deal_id_1year)) {
            if($_REQUEST['history']==1 ){//2016-5-21  到 2018-5-1
                $map['id']  = array('BETWEEN',"{$conf_deal_id_20160521},{$conf_deal_id_1year }");
            }else if($_REQUEST['history']==2  ){
                $map['id']  = array('LT', $conf_deal_id_20160521);//2016-5-21 之前
            }else{
                $map['id']  = array('EGT', $conf_deal_id_1year);// 2018-5-1以後的數據
            }
        }
        if(intval($_REQUEST['id'])>0){
            $map['_string'] =  'id = '. intval($_REQUEST['id']);
        }
        // 标题
        if(trim($_REQUEST['name'])!=''){
            $name = addslashes(trim($_REQUEST['name']));
            $map['name'] = array('like','%'.$name.'%');
        }
        // 借款人姓名
        if(trim($_REQUEST['real_name'])!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $ids = UserService::getUserIdByRealName($real_name);

            if (!empty($ids)) {
                $map['user_id'] = array("in", $ids);
            }else{
                // 远程调用失败
                $map['user_id'] = array("in", '-1');
            }
        }

        // 借款人用户名
        if(trim($_REQUEST['user_name'])!=''){
            $user_name = addslashes(trim($_REQUEST['user_name']));
            $userinfo_id = UserService::getUserByName($user_name,'id');

            if (!empty($userinfo_id['id'])) {
                $map['user_id'] = array("in", $userinfo_id['id']);
            }else{
                // 远程调用失败
                $map['user_id'] = array("in", '-1');
            }
        }
        if(trim($_REQUEST['user_id']) != ''){
            $map['user_id'] = trim($_REQUEST['user_id']);
        }

        // 项目名称
        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` like '%" . trim($_REQUEST['project_name']) . "%')";
        }
        // 放款审批单编号
        if (!empty($_REQUEST['approve_number'])) {
            $map['approve_number'] = array('eq', addslashes(trim($_REQUEST['approve_number'])));
        }

        // 标的状态 有效无效
        if(trim($_REQUEST['is_effect']) != ''){
            $map['is_effect'] = array("eq",intval($_REQUEST['is_effect']));
        }

        $deal_status_repaid = '';
        // 标状态
        if(isset($_REQUEST['deal_status']) && trim($_REQUEST['deal_status']) != '' && trim($_REQUEST['deal_status']) != 'all'){
            $map['deal_status'] = array("eq",intval($_REQUEST['deal_status']));
            $deal_status_repaid = intval($_REQUEST['deal_status']);
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = DI("Deal");
        $userIDArr = array();
        $listOfBorrower = array();
        // 存储合同签署状态
        $deal_contract_list = array();
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map, '',false,true);
            //jira#5361 增加“平台费折扣率”
            $extModel = new DealExtModel();
            foreach($list as $k=>$v){
                $list[$k]['ecid'] = Aes::encryptForDeal($v['id']);
                $extRow = $extModel->findBy("deal_id = " . $v['id'], "discount_rate", array(), true);
                $list[$k]['discount_rate'] = $extRow['discount_rate'];
                // 去重
                if (!isset($userIDArr[$v['user_id']])){
                    $userIDArr[$v['user_id']] = $v['user_id'];
                }

               $contract_info = ContractService::getContractSignStatus($v['id'],$v['user_id'],$v['agency_id'],$v['advisory_id'],$v['entrust_agency_id'],$v['canal_agency_id']);
                $deal_contract_list[$v['id']] = $contract_info;


            }
            $listOfBorrower = UserService::getUserInfoByIds($userIDArr,true);
            $this->assign('list', $list);
        }
        $this->assign("main_title",'借款列表');
        $this->assign('listOfBorrower', $listOfBorrower);
        $this->assign('deal_contract_list', $deal_contract_list);
        $this->assign('sitelist', $GLOBALS['sys_config']['TEMPLATE_LIST']);
        $this->display($this->template);
    }
    public function load_user(){
        $return= array("status"=>0,"message"=>"");
        $id = intval($_REQUEST['id']);
        if($id==0){
            return ajax_return($return);
        }
        $user = UserService ::getUserById($id);
        if(!$user){
            return ajax_return($return);
        }
        $return['status']=1;
        $return['user']=$user;
        return ajax_return($return);
    }

}
?>
