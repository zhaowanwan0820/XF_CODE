<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\DealMsgListService;
use core\dao\SmsModel;
use core\dao\EmailModel;
use libs\utils\Logger;

FP::import('libs.common.site');
FP::import("libs.common.dict");

class DealMsgListAction extends CommonAction{

    public $errMsg = '';
    public static $msg = array('code'=>'0000','msg'=>'操作成功');

    /**
     * 邮件列表
     */
    public function indexEmail() {
        $this->model = new EmailModel();
        $this->pk_name = "_id";
        if (trim($_REQUEST['dest']) != '') {
            $condition['dest'] = trim($_REQUEST['dest']);
            if (!isset ($_REQUEST ['_order'])) {
                $_REQUEST ['_order'] = "create_time";
            }
        }

        $is_success = trim($_REQUEST['is_success']);
        if ($is_success !== '') {
            $condition['is_success'] = intval($is_success);
        }

        $this->assign("default_map", $condition);
        $this->assign('queryString', http_build_query($condition));
        parent::index();
    }

    protected function form_index_list(&$list) {
        foreach ($list as &$item) {
            if ($this->model instanceof \core\dao\BaseNoSQLModel) { // 同时支持mongo和mysql
                $item['id'] = $item->id;
                $item['show_content'] = "<a href=javascript:show_content('" . $item['id'] . "')>查看</a>";
            }
        }
    }

    public function index() {
        /**
         *  短信发送状态
         *  array('0'=>'发送到队列失败','1'=>'队列处理成功','2'=>'发送到队列','3'=>'队列处理失败',);
         */
        if(trim($_REQUEST['dest'])!='') {
            $condition['dest'] = array('like','%'.trim($_REQUEST['dest']).'%');
        }
        if(trim($_REQUEST['content'])!='') {
            $condition['content'] = array('like','%'.trim($_REQUEST['content']).'%');
        }
        if(trim($_REQUEST['send_type'])!='') {
            $condition['send_type'] = $_REQUEST['send_type'];
        }

        $is_success = trim($_REQUEST['is_success']);
        if ($is_success !== '') {
            $condition['is_success'] = intval($is_success);
        }
        $this->assign("default_map",$condition);
        $this->assign('queryString',http_build_query($condition));
        $this->model = MI('DealMsgList');
        parent::index();
    }

    //展示内容
    public function show_content() {
        $id = $_REQUEST['id'];
        if ($_REQUEST['datatype'] == 'sms') {
            $result = SmsModel::get($id);
        } else if ($_REQUEST['datatype'] == 'email') {
            $result = EmailModel::get($id);
        } else {
            $result = M("DealMsgList")->where("id=" . $id)->find();
        }
        if(!empty($result)) {
            if($result['send_type'] == 0) {
                $content = $this->dealContentStr($result['content'], $result['sms_content']);
                if(!empty($GLOBALS['sys_config']['SMS_TEPLATE_CONFIG'])){
                    foreach ($GLOBALS['sys_config']['SMS_TEPLATE_CONFIG'] as $key =>$v){
                        if ($result['sms_template_id'] == $v){
                            $tpl_info = M("MsgTemplate")->where("name='$key'")->find();
                            $content .= !empty($tpl_info['content']) ? '<br />'.$tpl_info['content'] : '';
                            break;
                        }
                    }
                }
                self::$msg['msg'] = $content;
            }else{
                self::$msg['msg'] = $result['content'];
            }
        }else{
            self::$msg = array('code'=>'4000','msg'=>'操作失败');
        }
        print_r(self::$msg['msg']);
    }

    //内容处理
    private function dealContentStr($content ,$newContent) {
        if(!empty($content) && !empty($newContent)) {
            $newContent = json_decode($newContent);
            preg_match_all('/\{.*?\}/',$content,$arr);
            if(!empty($arr[0])) {
                foreach($newContent as $k=>$v){
                    $content = str_replace($arr[0][$k], $v, $content);
                }
            }
        }
        return $content;
    }

    public function add_msg(){
        parent::index();
    }

    /**
     * 批量重新发送
     * @author  caolong
     * @date    2014-3-18
     */
    public function batchResend() {
        $ids =  $_POST['ids'];
        if(!empty($ids)) {
            $id = explode(',', $ids);
            foreach ($id as $key=>$val ){
                $result = M('DealMsgList')->where('id='.intval($val))->find();
                if(!empty($result)) {
                    if(intval($result['send_type']) == 0  ) { //短信
                        if(!empty($result['sms_template_id']) && !empty($result['sms_template_id'])) {
                            $data = array(
                                    "mobile"  => $result['dest'],
                                    "content" => $result['sms_content'],
                                    "tid"     => $result['sms_template_id'],
                                    "id"      => $result['id'],
                            );
                            SiteApp::init()->sms_queue->send($data);
                        }else{
                            $modle =  M('DealMsgList');
                            $data['id'] = $result['id'];
                            $data['result'] = '短信重发失败';
                            $modle->save($data);
                        }
                    }elseif(intval($result['send_type']) == 1){//邮件
                        $data = array(
                                'content' => $result['content'],
                                'title'   => $result['title'],
                                'is_html' => $result['is_html'],
                                'address' => $result['dest'],
                                'id'      => $result['id'],
                        );
                        SiteApp::init()->prior_queue->send($data);
                    }
                }
            }
        }else{
            self::$msg = array('code'=>'4000','msg'=>'参数为空');
        }
        echo json_encode(self::$msg);
    }

    public function resend() {
        $day = intval($_GET['day']);
        $day = $day<=0 ? 7:$day;
        $day = $day>=7 ? 7:$day;
        $time = time() - 3600 * 24 * $day;

        $list = M("DealMsgList")->where("`is_success`!='1' AND `create_time`>='{$time}'")->findAll();
        foreach ($list as $val) {
            if (intval($val['send_type']) === 0) { //短信
                $data = array(
                    "mobile"  => $val['dest'],
                    "content" => $val['sms_content'],
                    "tid"     => $val['sms_template_id'],
                    "id"      => $val['id'],
                );
                SiteApp::init()->sms_queue->send($data);
            } elseif (intval($val['send_type']) === 1) { //邮件
                $data = array(
                    'content' => $val['content'],
                    'title'   => $val['title'],
                    'is_html' => $val['is_html'],
                    'address' => $val['dest'],
                    'id'      => $val['id'],
                );
                SiteApp::init()->prior_queue->send($data);
            }
        }
        $this->success("操作成功");
    }

    public function do_add_msg(){

        if($_REQUEST['title']=='' && $_REQUEST['msg_type'] == 1){
            $this->error(l("请填写标题"));
        }

        if($_REQUEST['content']==''){
            $this->error(l("请填写内容"));
        }

        if($_REQUEST['to_user']==''){
            $this->error(l("请填写收件人邮箱或地址"));
        }

        //处理多个接收人
        $user_str = str_replace('，', ',', addslashes($_REQUEST['to_user']));//中文逗号转换为英文逗号
        $user_arr = explode(',', $user_str);
        $user_arr = array_unique($user_arr);
        $count=0;
        foreach($user_arr as $k=>$v){
           $msgData=array(
               'dest' => $v,
               'send_type' => intval($_REQUEST['msg_type']),
               'is_send' => 0,
               'create_time' => get_gmtime(),
               'content' => addslashes($_REQUEST['content']),
               'user_id' => 0,
               'title' => addslashes($_REQUEST['title']),
               'is_html' => 0,
               'attachment' => '',
               'is_youhui' =>0,
               'is_success' =>2,
               'youhui_id' =>0,
            );
           $insertId = M("DealMsgList")->add($msgData);
           if(!empty($insertId))
               $count++;
           //update cl 2013-12-5
           if(intval($_REQUEST['msg_type']) === 0) {
               SiteApp::init()->sms_queue->send(array('mobile'=>$msgData['dest'],
                                                       'content'=>$this->dealContent($_REQUEST['content']),
                                                       'id'    =>$insertId,
               ));
           }
           //update cl 2013-12-6
           if(intval($_REQUEST['msg_type']) === 1) {
               $data = array(
                       'content'=>$_REQUEST['content'],
                       'title'=>$_REQUEST['title'],
                       'is_html'=>true,
                       'address'=>$msgData['dest'],
                       'id'=>$insertId,
               );
               SiteApp::init()->prior_queue->send($data);
           }
        }
        $this->success(L("添加了".$count."条"));

    }


        /**
         * 匹配国内手机号码
         * @param unknown $mobile
         * @return boolean
         */
        private function mathMbile($mobile) {
            if(!empty($mobile)) {
                $exp = '/^(13[\d]{9})|(14[\d]{9})|(15[\d]{9})|(17[\d]{9})|(18[\d]{9})/';
                if(preg_match($exp,$mobile)) {
                    return true;
                }else{
                    return false;
                }
            }
            return false;
        }
        /**
         * 验证短信内容必须带 签名 【签名内容】
         * @param string $content
         * @return boolean
         */
        private function checkContent($content= '') {
            //update caolong 2014-2-27 去掉签名验证
            return true;
          /*   $start = strpos($content, '【');
            $end   = strrpos($content, '】');
            return ($end - $start) > 0 ? true :false; */
        }

        //处理短信内容
        private function dealContent($contetn ='') {
            if(!empty($contetn)) {
                if(intval($GLOBALS['sys_config']['SMS_SEND_CONFIG']['SMS_SEND_SIGNATURE']) === 1) {
                    $start = strpos($contetn, '【');
                    $end   = strrpos($contetn, '】');
                    if(($end - $start) > 0) {
                    }else{
                        $contetn.= '【'.$GLOBALS['sys_config']['SMS_SEND_CONFIG']['SMS_SEND_SIGNATURE_CONTENT'].'】';
                    }
                }
            }
            return $contetn;
        }

        /**
         * 手动重新发送短信
         */
        public function send() {
            $id = intval($_REQUEST['id']);
            $msg_item = M("DealMsgList")->getById($id);
            if($msg_item)
            {
                if($msg_item['send_type']==0)
                {
                    //短信 update cl 2013-11-15
                    $smsContent = str_replace(array(" "," "), '', $msg_item['content']);
                    $result = 0;
                    if(!empty($msg_item['dest']) && !empty($smsContent) ) {
                        if($this->checkContent($smsContent)) {
                            if($this->mathMbile($msg_item['dest'])) {
                                $result = SiteApp::init()->sms_queue->send(array('mobile'=>$msg_item['dest'],
                                                                                 'content'=>$msg_item['sms_content'],
                                                                                 'tid'=>$msg_item['sms_template_id'],
                                                                                 'id'    =>$id
                                ));
                                $msg_item['result'] = $result;
                                $msg_item['is_success'] = $result == 1 ? 2 : 0;
                                $msg_item['is_send'] = $result;
                                $msg_item['send_time'] = get_gmtime();
                                M("DealMsgList")->save($msg_item);
                            }else{
                                $this->errMsg = '无效手机号码';
                            }
                        }else{
                            $this->errMsg  = '内如必须附带签名 格式：【签名】';
                        }
                    }else{
                        $this->errMsg  = '手机号码 、内容不能为空';
                    }

                    /* FP::import("libs.utils.es_sms");
                    $sms = new sms_sender();

                    $result = $sms->sendSms($msg_item['dest'],str_replace(array(" "," "), '', $msg_item['content']));
                    $msg_item['result'] = $result['msg'];
                    $msg_item['is_success'] = intval($result['status']);
                    $msg_item['is_send'] = 1;
                    $msg_item['send_time'] = get_gmtime();
                    M("DealMsgList")->save($msg_item); */


                    if($result)
                    {
                        header("Content-Type:text/html; charset=utf-8");
                        echo l("SEND_NOW").l("SUCCESS");
                    }
                    else
                    {

                        header("Content-Type:text/html; charset=utf-8");
                        echo l("SEND_NOW").l("FAILED").$this->errMsg;
                    }
                }
                else if($msg_item['send_type']==1)
                {
                    //edit  2013-12-6 cl
                    if(!empty($msg_item['dest']) && !empty($msg_item['content'])) {
                        $data = array(
                                'content'=>$msg_item['content'],
                                'title'=>$msg_item['title'],
                                'is_html'=>true,
                                'address'=>$msg_item['dest'],
                                'id'=>$id,
                        );
                        if ($msg_item['attachment'] && is_numeric($msg_item['attachment'])) {
                            $data['attachment'] = $msg_item['attachment'];
                        }
                       $result = SiteApp::init()->prior_queue->send($data);
                       $msg_item['result'] = '';
                       $msg_item['is_success'] =  $result == 1 ? 2 : 0;
                       $msg_item['is_send'] = 1;
                       $msg_item['send_time'] = get_gmtime();
                       M("DealMsgList")->save($msg_item);
                       if($result) {
                           header("Content-Type:text/html; charset=utf-8");
                           echo l("SEND_NOW").l("SUCCESS");
                       }else{
                           header("Content-Type:text/html; charset=utf-8");
                           echo l("SEND_NOW").l("FAILED").$mail->ErrorInfo;
                       }
                    }
                    /**
                    //邮件

                    FP::import("libs.utils.es_mail");
                    $mail = new mail_sender();

                    $mail->AddAddress($msg_item['dest']);
                    $mail->IsHTML($msg_item['is_html']);                   // 设置邮件格式为 HTML
                    $mail->Subject = $msg_item['title'];   // 标题
                    $mail->Body = $msg_item['content'];  // 内容
                    $result = $mail->Send();

                    $msg_item['result'] = $mail->ErrorInfo;
                    $msg_item['is_success'] = intval($result);
                    $msg_item['is_send'] = 1;
                    $msg_item['send_time'] = get_gmtime();
                    M("DealMsgList")->save($msg_item);
                    */
                    if($result)
                    {
                        header("Content-Type:text/html; charset=utf-8");
                        echo l("SEND_NOW").l("SUCCESS");
                    }
                    else
                    {

                        header("Content-Type:text/html; charset=utf-8");
                        echo l("SEND_NOW").l("FAILED").$mail->ErrorInfo;
                    }

                }
                else
                {


                    $content = unserialize($msg_item['content']);
                    $json_data = array('data'=>json_encode($content));


                    $cu = curl_init();
                    curl_setopt($cu, CURLOPT_URL, $msg_item['dest']);
                    curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($cu, CURLOPT_POST, 1);
                    curl_setopt($cu, CURLOPT_POSTFIELDS, $json_data );
                    $ret = curl_exec($cu);
                    curl_close($cu);

                    $msg_item['is_send'] = 1;
                    $msg_item['is_success'] = intval($ret);
                    $msg_item['send_time'] = get_gmtime();
                    M("DealMsgList")->save($msg_item);

                    if($ret == 1)
                    {
                        header("Content-Type:text/html; charset=utf-8");
                        echo l("SEND_NOW").l("SUCCESS");
                    }
                    else
                    {
                        header("Content-Type:text/html; charset=utf-8");
                        echo l("SEND_NOW").l("FAILED"). $ret;
                    }
                }
            }
            else
            {
                header("Content-Type:text/html; charset=utf-8");
                echo l("SEND_NOW").l("FAILED");
            }
    }

    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['id'];
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->delete();

                if ($list!==false) {
                    save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                    $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("FOREVER_DELETE_FAILED"),0);
                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 实时发送邮件
     */
    public function sendEmail() {
        $id = $_REQUEST['id'];
        $log_info = array(__CLASS__, __FUNCTION__, APP, $id);
        $msg_item = EmailModel::get($id);
        $msg_item['id'] = $id;//解决bug-立即发送按钮，邮件发送至状态不变

        if (empty($msg_item)) {
            Logger::info(implode(" | ", array_merge($log_info, array('error id'))));
            $this->error("id错误！", true);
        }

        $white_list = dict::get('EMAIL_WHITELIST');
        if (!empty($white_list) && !in_array($msg_item['dest'], $white_list)) {
            Logger::info(implode(" | ", array_merge($log_info, array('cancel by white list'))));
            $this->error("白名单过滤失败！", true);
        }

        $msg_item['address'] = $msg_item['dest'];
        unset($msg_item['dest']);
        $DealMsg = new DealMsgListService();
        $rs = $DealMsg->sendMsg($msg_item);
        if ($rs['msg']) {
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg_item), 'fail'))));
            $this->error($rs['msg'], true);
        }
        Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg_item), 'success'))));
        $this->success("发送成功！", true);
    }

    /**
     * 实时发送邮件
     */
    public function sendMailNow(){
        $id = intval($_REQUEST['id']);
        $msg_item = M("DealMsgList")->getById($id);

        if(!$msg_item){ //不存在或者 不是 email
            save_log($id."实时发送邮件失败",0);
            $this->error ("实时发送邮件失败！",true);
        }
        if($msg_item['send_type'] != 1){
            $this->error ("实时发送邮件失败,类型不符！",true);
        }

        $msg_item['address'] = $msg_item['dest'];
        unset($msg_item['dest']);
        $DealMsg = new DealMsgListService();
        $rs = $DealMsg->sendMsg($msg_item);
        if($rs['msg']){
            $this->error ($rs['msg'],true);
        }
        $this->success ("发送成功！",true);
    }

    public function testSms() {
        $msgcenter = new msgcenter();
        $msgcenter->setMsg('18600207300', '123', 'content', 'TPL_DEAL_PUBLISH_SMS_NEW');
        $msgcenter->save();
    }

    public function testEmail() {
        $email_model = new \core\dao\EmailModel();
        $DealMsg = new DealMsgListService();
        $id = '55644e5497b9d6b8040041a8';
        $msg = $email_model->get($id);
        $msg['address'] = $msg['dest'];
        $msg['id'] = $msg->getId();
        $rs = $DealMsg->sendMsg($msg);
    }

}
?>
