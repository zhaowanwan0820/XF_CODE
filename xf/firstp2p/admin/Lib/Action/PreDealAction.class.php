<?php
/**
 * 贷款预发布
 * @author changlu
 */
FP::import("libs.libs.msgcenter");
FP::import("app.deal");
class PreDealAction extends CommonAction{

     /**
      * 未通过
      * (non-PHPdoc)
      * @see CommonAction::index()
      */
     public function index(){

          //列表过滤器，生成查询Map对象
          $map = $this->_search();

          $map['status'] = getRequestInt("status");

          if(trim($_REQUEST['name'])!=''){
               $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
          }

          $map['is_delete'] = getRequestInt("is_delete");
          $is_delete = getRequestInt("is_delete");
          if($is_delete){
               unset($map['status']);
          }


          if (method_exists($this, '_filter')) {
               $this->_filter($map);
          }
          $name = $this->getActionName();
          $model = D($name);
          if (!empty ($model)) {
               $this->_list($model, $map);
          }
          $this->display();
          return;
     }

     /**
      * 添加贷款预发布 和发布贷款一样
      */
     public function add()
     {
          $this->assign("new_sort", M("Deal")->where("is_delete=0")->max("sort")+1);

          $deal_cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
          $deal_cate_tree = D("DealCate")->toFormatTree($deal_cate_tree,'name');
          $this->assign("deal_cate_tree",$deal_cate_tree);

          $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1 ')->order('sort DESC')->findAll();
          $this->assign("deal_agency",$deal_agency);

          $deal_type_tree = M("DealLoanType")->findAll();
          $deal_type_tree = D("DealLoanType")->toFormatTree($deal_type_tree,'name');
          $this->assign("deal_type_tree",$deal_type_tree);

          FP::import("libs.common.app");
          $contract_tpl_type = get_contract_type();
          $this->assign('contract_tpl_type', $contract_tpl_type);     //合同类型

          //从配置文件取公用信息
          $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);          //还款方式
          $this->assign('repay_time', $GLOBALS['dict']['REPAY_TIME']);     //还款期限
          $this->assign('guarantor_relation_list',$GLOBALS['dict']['DICT_RELATIONSHIPS']); //保证人类型

          //add 咨询机构 caolong 2013-12-27
          $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
          $this->assign("deal_advisory",$deal_advisory);
          //
          $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);     //投资人群

        //add by zhangruoshi, about deal muti-site
        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = get_sites_template_list();
        $this->assign('site_list', $site_list);
        //end add

          $this->display();
     }

     /**
      * 添加一个预发布贷款
      * (non-PHPdoc)
      * @see CommonAction::insert()
      */
     public function insert() {

          B('FilterString');
          $data = M(MODULE_NAME)->create ();
                if(($data ['max_loan_money']) > 0 && ($data ['max_loan_money']) < ($data ['min_loan_money'])) {
                    $this->error ( "最大金额不能小于最小金额" );
                }
          if($_FILES['file']){
               $uploadFileInfo = array(
                   'file' => $_FILES['file'],
                   'isImage' => 0,
                   'asAttachment' => 1,
                   'asPrivate' => true,
               );
               $result = uploadFile($uploadFileInfo);
               $data['pic'] = $result['aid'];
          }
          $data['pic'] = intval($data['pic']);
          $adm_info= es_session::get(md5(conf("AUTH_KEY")));
          $data['auser'] = $adm_info['adm_name'];
          $data['checker'] = $adm_info['adm_name'];
          // 更新数据
          $data['create_time'] = get_gmtime();
          $data['update_time'] = get_gmtime();

        $m = M(MODULE_NAME);
          $res = $m->add ($data);
          $id = $m->getLastInsID();

        if(false !== $res){
             $this->success(L("INSERT_SUCCESS"),0,u("LoanLnfo/index",array('did'=>$id)));
        }
          $this->error(L("INSERT_FAILED"));
     }

     /**
      * 编辑预发布贷款
      * (non-PHPdoc)
      * @see CommonAction::edit()
      */
     public function edit() {
          $adm_info= es_session::get(md5(conf("AUTH_KEY")));

          $id = intval($_REQUEST ['id']);
          $condition['id'] = $id;
          $vo = M(MODULE_NAME)->where($condition)->find();

          if($vo['pic']){
               $vo['src'] = get_attr($vo['pic'],1);
          }
          $this->assign ( 'vo', $vo );

          $deal_cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
          $deal_cate_tree = D("DealCate")->toFormatTree($deal_cate_tree,'name');
          $this->assign("deal_cate_tree",$deal_cate_tree);

          $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1')->order('sort DESC')->findAll();
          $this->assign("deal_agency",$deal_agency);

          //add 咨询机构 caolong 2013-12-27
          $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
          $this->assign("deal_advisory",$deal_advisory);

          $deal_type_tree = M("DealLoanType")->findAll();
          $deal_type_tree = D("DealLoanType")->toFormatTree($deal_type_tree,'name');
          $this->assign("deal_type_tree",$deal_type_tree);

          //从配置文件取公用信息
          $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);          //还款方式
          $this->assign('repay_time', $GLOBALS['dict']['REPAY_TIME']);     //还款期限

          $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);     //投资人群

          FP::import("libs.common.app");
          $contract_tpl_type = get_contract_type();
          $this->assign('contract_tpl_type', $contract_tpl_type);     //合同类型

          //修改日志
          $log = M("PreDealAudit");
          $loglist = $log->where('deal_id = '.$id)->order('id ASC')->findAll();
          $this->assign('loglist', $loglist);     //合同类型

          $this->display ();
     }

    /**
     * 修改
     * (non-PHPdoc)
     * @see CommonAction::update()
     */
     public function update() {
          B('FilterString');
          $data = M(MODULE_NAME)->create ();
          $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $data['id']))->find();
          $loginfo = $this->get_different($data,$vo);

                if(($data ['max_loan_money']) > 0 && ($data ['max_loan_money']) < ($data ['min_loan_money'])) {
                    $this->error ( "最大金额不能小于最小金额" );
                }
          if($_FILES['file']['name']){
                $uploadFileInfo = array(
                    'file' => $_FILES['file'],
                    'isImage' => 0,
                    'asAttachment' => 1,
                    'asPrivate' => true,
                );
               $result = uploadFile($uploadFileInfo);
               $data['pic'] = $result['aid'];
          }
          $data['pic'] = intval($data['pic']);
        $adm_info= es_session::get(md5(conf("AUTH_KEY")));

        $data['checker'] = $adm_info['adm_name'];
          $data['update_time'] = get_gmtime();
          $data['manager'] = htmlspecialchars($data['manager']);
          $data['manager_mobile'] = htmlspecialchars($data['manager_mobile']);

          $m = M(MODULE_NAME);
          $res = $m->save ($data);

          $log = M("PreDealAudit");
          $log_data['auser'] = $adm_info["adm_name"];
          $log_data['deal_id'] = $data['id'];
          $log_data['create_time'] = get_gmtime();
          $log_data['log'] = $loginfo;
          if($_FILES['img']['name']){
                $uploadFileInfo = array(
                    'file' => $_FILES['img'],
                    'isImage' => 0,
                    'asAttachment' => 1,
                    'asPrivate' => true,
                );
               $results = uploadFile($uploadFileInfo);
               $log_data['pic'] = $results['aid'];
          }
          $log_data['note'] = getRequestString('opinion'); 
          $log->add($log_data);

          if($res){
               $this->success(L("UPDATE_SUCCESS"));
          }
          $this->error(L("INSERT_FAILED"));
     }

     /**
      * 比较连个数组的变化
      * @param unknown $arr1
      * @param unknown $arr2
      */
     protected function get_different($arr1,$arr2){
          if(!$arr1){return false;}
          $key = $this->get_model_comment();

          $diff = '';
          foreach($arr1 as $k=>$v){
               if(trim($arr1[$k]) != trim($arr2[$k])){
                    $key1 = $arr2[$k];
                    $key2 = $arr1[$k];
                    if($k == "loantype"){
                         $key1 = $GLOBALS['dict']['LOAN_TYPE'][$key1];
                         $key2 = $GLOBALS['dict']['LOAN_TYPE'][$key2];
                    }
                    if($k == 'repay_time'){
                         $key1 = $GLOBALS['dict']['REPAY_TIME'][$key1];
                         $key2 = $GLOBALS['dict']['REPAY_TIME'][$key2];
                         if(!$key1){//按天的
                              $key1 = $arr2['repay_time'].'天';
                         }
                         if(!$key2){//按天的
                              $key2 = $arr1['repay_time'].'天';
                         }
                    }
                    $diff .= '<p>'.$key[$k].'&nbsp;&nbsp;<span style="color:#FF0000;" >'.$key1.'</span>-----><span style="color:#FF0000;" >'.$key2.'</span>&nbsp;&nbsp;'.'</p>';
               }
          }
          return $diff;
     }

     /**
      * 获取 标注
      */
     protected function get_model_comment(){
          $sql = "SHOW FULL FIELDS FROM `firstp2p_pre_deal` ";
          $info = M("PreDeal")->query($sql);
          $arr = array();
          foreach($info as $k=>$v){
               $str = explode(" ", $v['Comment']);
               $arr[$v['Field']] = $str[0];
          }
          return $arr;
     }
}
?>
