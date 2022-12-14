<?php

use libs\utils\Logger;
use core\service\GoldService;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\utils\Rpc;

class GoldUserAction extends CommonAction {

    public function index(){
        $request = new RequestCommon();
        $userModel = new \core\dao\UserModel ();
        $user_num = addslashes(trim($_REQUEST['user_num']));
        $name = addslashes(trim($_REQUEST['name']));
        $real_name = addslashes(trim($_REQUEST['real_name']));
        $mobile = addslashes(trim($_REQUEST['mobile']));
        if($user_num!=''){
            $userInfo=$userModel->find( trim(de32Tonum($user_num)));
            $userId= !empty($userInfo) ? $userInfo['id'] : -1 ;
            $request->setVars(array('userId'=>$userId));
        }
        if($name !=''){
            $userInfo=$userModel->getUserinfoByUsername($name);
            $userId= !empty($userInfo) ? $userInfo['id'] : -1 ;
            $request->setVars(array('userId'=>$userId));
        }
        if($real_name!=''){
            $userInfo=$userModel->getUserIdsByRealName($real_name);
            $userIds= !empty($userInfo) ? $userInfo : -1 ;
            $request->setVars(array('userIds'=>$userIds));
        }
        if($mobile!=''){
            $userInfo=$userModel->getUserinfoByUsername($mobile);
            $userId= !empty($userInfo) ? $userInfo['id'] : -1 ;
            $request->setVars(array('userId'=>$userId));
        }
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $request->setVars(array("isWhite"=>1,"pageNum"=>$pageNum,"pageSize"=>$pageSize));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\User',
            'method' =>'whiteListUser',
            'args' => $request,
        ));
        $data=array();

        foreach($response['data']['data'] as $k => $v){
            $userInfo=$userModel->find( $v['userId']);
            $data[$k]['id']= $v['id'];
            $data[$k]['user_id']= $v['userId'];
            $data[$k]['user_num']= numTo32($v['userId']);
            $data[$k]['mobile']=moblieFormat($userInfo['mobile']);
            $data[$k]['name']=$userInfo['user_name'];
            $data[$k]['real_name']=$userInfo['real_name'];
        }
        $page = new Page($response['data']['totalNum'], $pageSize);
        $this->assign('page', $page->show());
        $this->assign('nowPage', $p);
        $this->assign('data',$data);
        $this->display();
    }

    public function add(){
        $this->display();
    }

    public function import_csv(){
        if (empty ( $_FILES ['upfile'] ['name'] )) {
            $this->error ( "???????????????????????????" );
        }
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            $this->error ( "?????????csv??????????????????" );
        }
        $max_import_line = 1000;
        // ???????????????session
        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
        $csv_content = file_get_contents ( $_FILES ['upfile'] ['tmp_name'] );
        $csv_content = trim ($csv_content);
        if (empty ( $csv_content )) {
            $this->error ( '????????????????????????' );
        }
        $total_line = explode ( "\n", iconv ( 'GBK', 'UTF-8', $csv_content ) );
        // ????????????????????????Title
        $count_total_line = count ( $total_line ) - 1;
        // ???????????????????????????????????????
        if (empty ( $total_line [$count_total_line] )) {
            $count_total_line -= 1;
        }
        if ($count_total_line > $max_import_line) {
            $this->error ( '????????????' . $max_import_line . '?????????' );
        }
        $i=$j=0;
        if (($handle = fopen ( $_FILES ['upfile'] ['tmp_name'], "r" )) !== false) {
            if (fgetcsv ( $handle ) !== false) { // ??????????????????????????????????????????
                while ( ($row_data = fgetcsv ( $handle )) !== false ) {
                    $error_msg = $this->check_csv_datas ( $row_data , $i );
                    if (! empty ( $error_msg ['error_msg'] )) {
                        $error_total_num ++;
                        $error_str .= $error_msg ['error_msg']; 
                        unset ( $error_msg );
                        $i ++;
                        continue;
                    }
                    if($row_data [0] !== $error_data [0]){
                        $user_id=trim(de32Tonum($row_data[0]));
                        $csv_row[$user_id]=$row_data[1];
                        if($row_data[1]==1){
                            $j++;
                        }else{
                            $d++;
                        }
                    }
                    $i ++;
                }
            }
            fclose ( $handle );
            @unlink ( $_FILES ['upfile'] ['tmp_name'] );
            $this->import_result($csv_row,$j,$_FILES ['upfile'] ['name'],$error_str);
        } else {
            $this->error ( "????????????????????????" );
        }
    }

    public function import_result($correct,$correct_count,$file_name,$error){
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,'correct_csv : '.json_encode($correct))));
        $error_tmp=explode("\n",$error);
        $error_count=count($error_tmp)-1;
        if(empty($correct)){
            $this->error("????????????????????????");
        }else{
            $goldService=new GoldService();
            $result=$goldService->changeWhiteUser($correct);
            if(!empty($result)){
                save_log('?????????????????????????????????'.L("UPDATE_SUCCESS"),C('SUCCESS'),'',$file_name.' | '.json_encode($correct));
                $this->assign('file_name',$file_name);
                $this->assign('correct_count',$correct_count);
                $this->assign('error_count',$error_count);
                $this->assign('error',$error);
                $this->display('import_result');
            }else{
                $this->error("????????????");
            }
        }
    }

    public function delete(){
        $ajax = intval($_REQUEST['ajax']);
        $id_arr = isset($_REQUEST ['id']) ? explode(',', $_REQUEST ['id']) : array();
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,'id_arr : '.json_encode($id_arr))));
        foreach($id_arr as $v){
            $correct[$v]=0;
        }
        $goldService=new GoldService();
        $result=$goldService->changeWhiteUser($correct);
        if ($result ==false){
            $this->error ("????????????");
        }else{
            $this->success ("????????????",$ajax);
        }
    }
    /**
     * ??????csv ??????
     */
    private function check_csv_datas($data, $line) {
        $ret = array (
                'error_msg' => ''
                );
        // ??????????????????????????????
        if (empty ( $data[0] )) {
            $error_str = ' ????????????????????????';
        } else {
            $userModel = new \core\dao\UserModel ();
            $userInfo=$userModel->find( trim(de32Tonum($data [0])) );
            // ????????????????????????
            if (empty ( $userInfo )) {
                $error_str="???????????????";
            }
        }
        if (! empty ( $error_str )) {
            $error_list = $data[0] .','.$error_str."\n";
        }
        $ret ['error_msg'] = $error_list;
        return $ret;
    }

}
