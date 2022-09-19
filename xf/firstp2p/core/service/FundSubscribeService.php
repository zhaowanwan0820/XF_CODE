<?php
/**
 * FundSubscribeService.php
 * 
 * @date 2014-09-30
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace core\service;

use core\dao\FundSubscribeModel;
use core\dao\UserModel;
/**
 * FundService
 *
 * @uses BaseService
 * @package default
 */
class FundSubscribeService extends BaseService {
    
    /**
     * add
     * 增加预约人员
     *
     * @param mixed $fund_id
     * @param mixed $userid
     * @param mixed $realname
     * @param mixed $phone
     * @param mixed $money
     * @param mixed $comment
     * @access public
     * @return bool
     */
    public function add($fund_id,$userid,$phone,$money,$comment,$platform=0){
        $fundService = new FundService();
        $fund = $fundService->getInfo($fund_id);
        if(!$fund){
            return array('code'=>false,'msg'=>'产品不存在');
        }
        if($money < $fund['loan_money_min_num']){
            return array('code'=>false,'msg'=>"您的投资金额应大于或等于{$fund['loan_money_min']}元");
        }
        if($fund['status'] != 1){
            return array('code'=>false,'msg'=>"项目预约已结束");
        }
        if(is_numeric($money) === false || $money > 30000000 ){
            return array('code'=>false,'msg'=>"预约金额最多为3千万");
        }

        $userModel = new UserModel();
        $userinfo = $userModel->findBy('id=:id','real_name,sex',array(':id'=>$userid));
        if(!$userinfo){
            return array('code'=>false,'msg'=>'用户信息不存在');
        }
        $realname = $userinfo['real_name'];
        $sex = $userinfo['sex'];

        $data = array(
            'fund_id'=>$fund_id,
            'user_id'=>$userid,
            'realname'=>$realname,
            'sex'=>$sex,
            'phone'=>$phone,
            'money'=>$money,
            'comment'=>$comment,
            'platform'=>$platform,
            'create_time'=>get_gmtime(),
            );
        $model = new FundSubscribeModel();
        $ret = $model->add($data);
        if($ret){
            $notice_mail = array(
                'id' => $model->id,
                'username' => $realname,
                'fund_title' => $fund['name'],
                'money' => $money,
                'phone' => $phone,
                'memo' => $comment,
                'time' => $data['create_time'],
                );
            sendSubscribeEmail($userid,$notice_mail);
            return array('code' => true, 'msg' => '');
        }else{
            return array('code' => false, 'msg' => '添加失败');
        }
    }

    /**
     * getList
     * 获取预约人员列表
     *
     * @param mixed $fund_id 基金ID
     * @param mixed $offset
     * @param int $limit
     * @param string $order
     * @param string $sort
     * @access public
     * @return void
     */
    public function getList($fund_id,$offset,$limit=10,$order='create_time',$sort='desc'){
        $data = array();
        $offset = intval($offset);
        $limit = intval($limit);
        if($offset<0){
            $offset = 0;
        }
        $fund_model = new FundSubscribeModel();
        $total = $fund_model->getCount($fund_id);
        $list = $fund_model->getList($fund_id,$offset,$limit,$order,$sort);
        foreach($list as $key => $value){
            $list[$key]['create_time'] = to_date($list[$key]['create_time'],'Y-m-d');
            $list[$key]['username'] = $this->_getUserName($value['realname'],$value['sex']);
        }
        $count = count($list);
        $more = ($total<=($offset+$count));
        $page = array(
            'id' => $fund_id,
            'count'=> $count,
            'more'=> $more,
            );
        return array('page'=>$page,'list'=>$list);
    }

    /**
     * _getUserName
     * 获取用户性别组成的姓名
     * @param mixed $real_name
     * @param mixed $sex
     * @access private
     * @return string
     */
    private function _getUserName($real_name,$sex){
        if (empty($real_name)){
            return false;
        }
        if($sex == -1){
            $sexnum = -1;
            if(strlen($user_info['idno']) == 15){
                $sexnum = substr($user_info['idno'], -1);
            }elseif(strlen($user_info['idno']) == 18){
                $sexnum = substr($user_info['idno'], -2, 1);
            }
            if($sexnum > 0){
                $sex = $sexnum % 2 ? 1 : 0;
            }
        }
        $user_sex_name = $GLOBALS['dict']['USER_SEX'][$sex];

        //先取第一串英文字母，取不到的话，按中文截取
        if(preg_match('/^[a-zA-Z0-9]+/', $real_name, $out)){
            $pre_name = ($out[0] == $real_name) ? substr_replace($real_name, '******', 1, -1) : $out[0];
        }else{
            $pre_name = mb_substr($real_name, 0, 1, 'utf-8');
        }
        return $pre_name.$user_sex_name;
    }
}
