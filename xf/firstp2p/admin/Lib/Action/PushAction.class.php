<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
/* use libs\push\AndroidPushFactory;
use libs\push\AndroidTestPushFactory; */
FP::import('libs.push.AndroidTestPushFactory');
FP::import('libs.push.AndroidPushFactory');
FP::import('libs.push.IosTestPushFactory');
FP::import('libs.push.IosPushFactory');

class PushAction extends CommonAction{
    protected $_validate = array(
    	array('platform','require','发送平台必选'),
        array('title','require','标题必写'),
        array('content','require','内容必写'),
    );
	public function index()
	{

	    $condition['is_delete'] = 0;
	    $this->assign("default_map",$condition);
/*
	    $list = D('Push')->getList();
	    $this->assign('list',$list);
	    $this->display();
	    return;
 */		parent::index();
	}


	public function add()
	{
	    C('TOKEN_ON',true);
	    $this->assign("default_send_time",to_date(get_gmtime()+3600*24*7));
	    $this->display();
	}

	public function insert() {
	    C('TOKEN_ON',true);
	    if(!isset($_SESSION[C('TOKEN_NAME')])) {
	        $this->redirect(u(MODULE_NAME."/index"));
	    }
	    //开始验证有效性
	    $data = M(MODULE_NAME)->create();
	    if(isset($data['push_id'])) {
	        $this->assign("jumpUrl",u(MODULE_NAME."/resend",array('id'=>$data['id'])));
	    }else{
	        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
	    }
	    if(!count($data['platform']))
	    {
	        $this->error(L("PLATFORM_EMPTY_TIP"));
	        return;
	    }

	    //  如果是立刻发送 把时间去掉
	    if($data['send_type'] == 1 && isset($data['send_time'])) {
	        unset($data['send_time']);
	    }
	    $pfs = $data['platform'];

	    $platform = array();
        //  选择的平台 各自发送
        foreach ($pfs as $key => $type) {
            $arr = $this->_selectPush($type);//dump($arr);    die;
            $push = $arr['factory']->createPush();
            $deal_status = get_buy_status(1, $data['deal_id']);
            $data['message_key'] = $data['deal_id']._.time().'_'.$type;
            $push_data = array('title'=>$data['title'],'content'=>$data['content'],'message_key'=>$data['message_key'],'custom_content'=>array('id'=>$data['deal_id'],'title'=>$data['title'],'notif_title'=>$data['content'],'type'=>'product','status'=>1));
            $rs = $push->push($push_data);

            $send_cnt = 1;
            if($rs['response_params']['success_amount'] > 0) {
                $data['send_status'] = 1;   //  成功
            }else {
                $data['send_status'] = 2;   //  失败
            }
            $data['return'] = json_encode($rs);
            $data['platform'] = $arr['platform'];
            $list=M(MODULE_NAME)->add($data);
        }
	    $this->redirect(u(MODULE_NAME."/index"));
	}


	public function resend() {
	    C('TOKEN_ON',true);
        $id = $_REQUEST['id'];
//        $ajax = intval($_REQUEST['ajax']);
        $row = M('Push')->find(array('where'=>'id='.$id));
        $this->assign('row',$row);
        $this->display();
        return ;

        $arr = $this->_selectPush($row['platform']);
        $push = $arr['factory']->createPush();
        $rs = $push->push(array('title'=>$row['title'],'content'=>$row['content'],'message_key'=>$row['message_key'],'custom_content'=>array('id'=>$row['deal_id'],'title'=>$row['title'],'notif_title'=>$row['content'],'type'=>'product','status'=>1)));
        $row['return'] = json_encode($rs);
        $row['send_time'] = date('Y-m-d H:i:s',time());
        if($rs['response_params']['success_amount'] > 0) {
            $row['send_status'] = 1;   //  成功
        }else {
            $row['send_status'] = 2;   //  失败
        }

        /*
        $row['send_cnt']++;
        $rs = M('Push')->where('id='.$id)->save($row);

        if($rs) {
            $info['id'] = $row['id'];
            $info['title'] = $row['title'];
            $info['content'] = $row['content'];
            $info['platform'] = $row['platform'];
            save_log($info.l("RESEND_SUCCESS"),1);
            $this->success (l("RESEND_SUCCESS"),$ajax);
        }else{
                save_log($info.l("RESEND_FAILED"),0);
                $this->error (l("RESEND_FAILED"),$ajax);
        }*/

	}



	private function _selectPush($type) {
        $type = strtolower($type);
	    switch ($type) {
	        case 'ios': $factory = new IosPushFactory(); $val = 'IOS'; break;
	        case 'ios_test': $factory = new IosTestPushFactory(); $val = 'IOS_TEST';break;
	    	case 'android': $factory = new AndroidPushFactory(); $val = 'ANDROID'; break;
	    	case 'android_test': $factory = new AndroidTestPushFactory(); $val = 'ANDROID_TEST';break;
	    }

	    return array('factory'=>$factory,'platform'=>$val);
	}

	public function delete() {
	    $ajax = intval($_REQUEST['ajax']);

	    $push = M('Push');
	    $data['flag'] = 0;
	    $id = $_REQUEST ['id'];

        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info['title'] = $data['title'];
                $info['content'] = $data['content'];
                $info['platform'] = $data['platform'];
            }
            if($info) $info = implode(",",$info);
            $sdata['is_delete'] = 1;
            $list = M(MODULE_NAME)->where ( $condition )->data($sdata)->save();

            if ($list!==false) {
                save_log($info.l("DELETE_SUCCESS"),1);
                $this->success (l("DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }

//	    $push->where('id='.$id)->save($data);

	    //彻底删除指定记录



	}




}
?>