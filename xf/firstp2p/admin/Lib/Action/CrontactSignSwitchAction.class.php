<?php
/**
 * CrontactSignSwitchAction.php
 *
 * 合同代签开关
 * 
 * @date 2016-05-04
 * @author gengkuan<gengkuan@ucfgroup.com>
 */
use core\dao\ContractSignSwitchModel;

class CrontactSignSwitchAction extends CommonAction {
    const TYPE_JK = 1;
    const TYPE_DB = 2;
    const TYPE_ZCGL = 3;
    private static $sign_type = array(self::TYPE_JK=>'借款人合同 ',self::TYPE_DB=>'担保合同 ',self::TYPE_ZCGL=>'资产管理方合同');
    private static $sign_status = array('0'=>'停止 ','1'=>'开始 ');
    public function index() {
        $type = $this ->checkuerType();

        $signList = array();
        if(!empty($type)){
            $type  = implode(',',$type);
            $signList = M("ContractSignSwitch")->where( "type in ({$type})")->order('id ')->findAll();
            foreach($signList as &$sign){
                $sign['typename'] = self::$sign_type[$sign['type']];
                $sign['statusname'] = self::$sign_status[$sign['status']];
            }
        }
        $this->assign("sign",$signList);
        $this->display();
    }

    public function sign_change() {
        $type = $this ->checkuerType();
        $ajax = intval($_REQUEST['ajax']);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false ;
        if (!$id) {
            //ID 不能为空
            $this->error ("ID不能为空", $ajax);
        }
        if(!isset($_REQUEST['status']) ||  !isset(self::$sign_status[$_REQUEST['status']])){
            $this->error ('状态值不存在', $ajax);
        }
        $adm = es_session::get(md5(conf("AUTH_KEY")));
        if (!$adm) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $data['status'] =  intval($_REQUEST['status']);
        $sign_info = ContractSignSwitchModel::instance()->find($id);
        if(empty($sign_info)) {
            $this->error ('选择信息错误', $ajax);
        }
       if(!in_array($sign_info->type, $type)){
           $this->error ('你没有该开关的权限', $ajax);
       }
        $data['update_time'] = time();
        $data['adm_id'] =  $adm["adm_id"];
        $return = $sign_info->update($data);
        if(!$return){
            $this->error ('更新失败', $ajax);
        }
        save_log($adm["adm_name"].self::$sign_status[$data['status']].self::$sign_type[$sign_info->type].'实时代签功能', 1);
       $this->success('操作成功',$ajax);
    }
    private  function checkuerType(){
        $jk_adm_name = app_conf("SIGN_WT_JK");//借款人合同代签委托人
        $db_adm_name = app_conf("SIGN_WT_DB");//担保合同代签委托人
        $zcgl_adm_name = app_conf("SIGN_WT_ZCGL");//资产管理方合同代签委托人
        $adm = es_session::get(md5(conf("AUTH_KEY"))); //获取当前登录人
        $type = array();
        if($adm["adm_name"] ==$jk_adm_name ){
            $type[] = self::TYPE_JK;
        }
        if($adm["adm_name"] == $db_adm_name ){
            $type[] = self::TYPE_DB;
        }
        if($adm["adm_name"] == $zcgl_adm_name){
            $type[] = self::TYPE_ZCGL;
        }
        return $type;
    }
}