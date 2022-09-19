<?php
/**
* @file StaffService.php
* @author cxt <chengxiaotian@izouzi.com>
* @date 2016/11/10 
*/
use \itzlib\sdk\CurlRestClient;
class StaffService extends ItzInstanceService
{
	private $curl = null;

	private $request_url = '';
			
	function __construct()
	{
		if($this->curl == null){
			$this->curl = CcsCurlService::getInstance();
		}
		parent::__construct();
	}

	private function getRequestUrl(){
		if(empty($this->request_url)){
			$this->request_url = Yii::app()->c->ipassport_request_url;
		}
        return $this->request_url;
	}

	/**
	 * @param  integer $page 当前页数
	 * @param  integer $limit 每页大小
	 * @return array
	 */
	function getStaffList($page=1,$limit=10,$realname='',$dept_id=0,$leader_id=''){
		$returnData = array('code'=>0,'info'=>'获取成功','data'=>'');
		$param = array(
			'page_num'	=> $page,
			'page_size'	=> $limit,
            'realname'  => trim($realname),
            'dept_id'   => $dept_id,
            'leader_id' => base64_encode($leader_id),
			);
        $url = $this->getRequestUrl().'/iprofile/v1/staff?page_size='.$param['page_size'];
        if(!empty($page)){
            $url .= '&page_num='.$param['page_num'];
        }
        if(!empty($realname)){
            $url .= '&realname='.urlencode($param['realname']);
        }
        if(!empty($dept_id) && $dept_id>=0){
            $url .= '&dept_id='.$param['dept_id'];
        }
        if(!empty($leader_id)){
            $url .= '&leader_id='.$param['leader_id'];
        }
		$result = json_decode($this->curl->get($url),true);
		if(0 == $result['code']){
            if(count($result['data']['list']) > 0) {
                $staff_names = $this->getStaffInfo($result['data']['list']);
                if(empty($staff_names)){
                    $returnData['code'] = 1031;
                    $returnData['info'] = '获取员工名失败';
                }else{
                    foreach($result['data']['list'] as $k => &$v){
                        $v['login_id'] = base64_decode($v['login_id']);
                        $v['leader_id'] = base64_decode($v['leader_id']);
                        $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                        $v['modify_time'] = date('Y-m-d H:i:s',$v['modify_time']);
                        $v['join_time'] = date('Y-m-d',$v['join_time']);
                        $v['staff_name'] = $staff_names[$v['login_id']]['staff_name'];
                        $v['mobile'] = $staff_names[$v['login_id']]['mobile'];
                        $v['email'] = $staff_names[$v['login_id']]['email'];
                    }
                    $returnData['data']['listTotal'] = $result['data']['total'];
                    $returnData['data']['listInfo']  = $result['data']['list'];
                }
            }
		}else{
			$returnData = array('code'=>-1,'info'=>'网络错误');
			Yii::log('get staff list error,info:'.json_encode(array('request'=>$param,'response'=>$result)).' time:'.date('Y-m-d H:i:s'));
		}
		return $returnData;
	}

	/**
	 * 添加员工
	 */
	function addStaff($data){
		$returnData = array('code'=>0,'info'=>'添加成功','data'=>'');
        $valid = $this->checkInput($data);
        if($valid['code'] != 0){
            return $valid;
        }
        $logParams = array(
            'user_id'=> Yii::app()->user->getState('_user')['login_id'],
            'system' => 'newuser',
            'action' => 'add',
            'resource'=> 'staff',
            'parameters'=>'',
            'status' => 'false'
        );

        $ipassport_param = array(
            'mobile'        => $data['mobile'],
            'staff_name'    => $data['staff_name'],
//            'staff_password' => 123456,
            'email'         => $data['email'],
            'device_id'     => 1,
            'channel_id'    => 1,
            'create_ip'     => $_SERVER['SERVER_ADDR']
        );
        $login_id = '';
        //查找mobile是否被使用
        $has_reg = json_decode($this->curl->get($this->getRequestUrl().'/ipassport/v1/credential/mobile/'.$data['mobile']),true);
        if($has_reg['code'] == 0){
            $login_id = $has_reg['data']['login_id'];
        }else{
            $searchByStaffName = json_decode($this->curl->get($this->getRequestUrl().'/ipassport/v1/credential/staff-name/'.$data['staff_name']),true);
            if($searchByStaffName['code'] == 0){
                $returnData['code'] = '1048';
                $returnData['info'] = '用户名已存在';
                return $returnData;
            }else{
                $searchByEmail = json_decode($this->curl->get($this->getRequestUrl().'/ipassport/v1/credential/email/'.base64_encode($data['email'])),true);
                if($searchByEmail['code'] == 0){
                    $returnData['code'] = '1047';
                    $returnData['info'] = '邮箱已存在';
                    return $returnData;
                }
            }
            $ipassport_reg = $this->curl->post($this->getRequestUrl().'/ipassport/v1/credential?type=general',json_encode($ipassport_param));

            $ipassport_reg = json_decode($ipassport_reg,true);
            if($ipassport_reg['code'] == 0){
                //ipassport注册成功，返回login_id
                $login_id = $ipassport_reg['data']['login_id'];
            }
        }
        //now login_id是base64之后的
        if(empty($login_id)){
            $returnData['code'] = '100';
            $returnData['info'] = '注册失败';
            return $returnData;
        }
        //添加档案信息
        $archive_param = array(
            'realname' => $data['realname'],
            'login_id' => $login_id,
            'leader_id'=> base64_encode($data['leader_id']),
            'dept_id'  => $data['dept_id'],
            'join_time'=> strtotime($data['join_time']),
            'creator_id'=> Yii::app()->user->getState('_user')['login_id'],
            'is_claim' => $data['is_claim'],
        );

        $archive_reg = json_decode($this->curl->post($this->getRequestUrl().'/iprofile/v1/staff',json_encode($archive_param,JSON_UNESCAPED_UNICODE)),true);

        if(0 == $archive_reg['code']){
			$logParams['status'] = 'success';
			$returnData['data'] = $archive_reg['data'];
            //向旧的数据库中添加一条数据
            $oldId = $this->addToOldTable($data);
            Yii::log('userid: '.$oldId,'user_id');
            //添加新旧id对照
            if(!empty($oldId)){
                $this->addToReferenceTable(base64_decode($archive_reg['data']['login_id']),$oldId);
            }

		}elseif(1252 == $archive_reg['code']){
			$returnData = array('code'=>1014,'info'=>'部门不存在','data'=>'');
		}elseif(1263 == $archive_reg['code']){
			$returnData = array('code'=>1020,'info'=>'主管id不存在','data'=>'');
		}elseif(1261 == $archive_reg['code']){
			$returnData = array('code'=>1030,'info'=>'员工已经存在','data'=>'');
		}else{
            $returnData['code'] = 100;
            $returnData['info'] ='注册失败';
        }
		$logParams['parameters'] = json_encode(array('request'=>$data,'response'=>$archive_reg));
		AuditLog::getInstance()->method('add', $logParams);
		return $returnData;
	}

	/**
	 * [获取员工信息]
	 * @param  int $login_id  员工id
	 * @return array
	 */
	public function staffInfo($login_id){
		$returnData = array('code'=>0,'info'=>'获取成功','data'=>'');
		if($login_id < 0){
			return array("code"=>1021,'info'=>"非法id",'data'=>'');
		}
		$user = json_decode($this->curl->get($this->getRequestUrl().'/iprofile/v1/staff/'.base64_encode($login_id)),true);
		if(0==$user['code']){
            $staff_name = $this->getStaffInfo(array(0=>$user['data']));
            $login_id = base64_decode($user['data']['login_id']);
            $user['data']['staff_name'] = $staff_name[$login_id]['staff_name'];
            $user['data']['mobile'] = $staff_name[$login_id]['mobile'];
            $user['data']['email'] = $staff_name[$login_id]['email'];
			$returnData['data']['listInfo'] = $user['data'];
		}elseif(1262 == $user['code']){
			$returnData = array('code'=>1022,'info'=>"员工不存在",'data'=>'');
		}else{
			$returnData = array('code'=>-1,'info'=>"未知错误",'data'=>'');
			Yii::log('get staff info error,info:'.json_encode(array('request'=>['staff_id'=>$login_id],'response'=>$user)).' time:'.date('Y-m-d H:i:s'));
		}
		return $returnData;
		
	}

	/**
	 * [获取员工所属的用户组]
	 * @param  int $staff
	 * @return array
	 */
	function getStaffGroup($staff_id){
		if(empty($staff_id)){
			return false;
		}
		$param = array(
			'user_id' 	=> base64_encode($staff_id),
			'page'		=> 1,
			'page_num'  => 20
			);
		$userGroup = array();
		$result = json_decode($this->curl->get('iauth/v1/user-groups',$param),true);
		if(0==$result['code']){
			$userGroup = $result['data']['list'];
			$total = $result['data']['total'];
			$listNum = count($result['data']['list']);
			while($total > $listNum){
				$param['page'] ++;
				$result = json_decode($this->curl->get('iauth/v1/user-groups',$param),true);
				$total = $total = $result['data']['total'];
				$listNum += count($result['data']['list']);
				$userGroup = array_merge($userGroup,$result['data']['list']);
			}
		}
		foreach ($userGroup as $k => &$v) {
			$userGroup[$k]['join_time'] = date('Y-m-d H:i:s',$v['join_time']);
		}
		return $userGroup;
	}

	/**
	 * [获取员工拥有的策略]
	 * @param  int $staff_id
	 * @return array
	 */
	function getStaffPolicy($staff_id){
		if(empty($staff_id)){
			return false;
		}
		$param = array(
			'user_id' 	=> base64_encode($staff_id),
			'page'		=> 1,
			'page_num'  => 20
			);
		$userPolicy = array();
		$result = json_decode($this->curl->get('iauth/v1/user-policies',$param),true);
		if(0==$result['code']){
			$userPolicy = $result['data']['list'];
			$total = $result['data']['total'];
			$listNum = count($result['data']['list']);
			while($total > $listNum){
				$param['page'] ++;
				$result = json_decode($this->curl->get('iauth/v1/user-policies',$param),true);
				$total = $total = $result['data']['total'];
				$listNum += count($result['data']['list']);
				$userPolicy = array_merge($userPolicy,$result['data']['list']);
			}
		}
		return $userPolicy;
	}

	/**
	 * [删除员工]
	 * @param  int $login_id
	 * @return array
	 */
	function deleteStaff($login_id){
        $returnData = array('code'=>0,'info'=>'删除成功','data'=>'');
		if(empty($login_id)){
			return array('code'=>1021,'info'=>'员工id不能为空','data'=>''); 
		}
		$param = array(
            'modifier_id' => Yii::app()->user->getState('_user')['login_id']
        );
		$logParams = array(
				'user_id'=> Yii::app()->user->getState('_user')['login_id'],
				'system' => 'newuser',
				'action' => 'delete',
				'resource'=> 'staff',
				'parameters'=>'',
				'status' => 'false'
			);
		$result = json_decode($this->curl->delete($this->getRequestUrl().'/iprofile/v1/staff/'.base64_encode($login_id),json_encode($param)),true);
        if(0 == $result['code']){
			$logParams['status'] = 'success';
			$returnData = array('code'=>0,'info'=>'删除成功','data'=>'');
		}elseif(1262 == $result['code']){
			$returnData = array('code'=>1022,'info'=>'员工不存在','data'=>'');
		}else{
			$returnData = array('code'=>-1,'info'=>'未知错误','data'=>'');
			Yii::log('delete staff error,info:'.json_encode(array('request'=>$param,'response'=>$result)).' time:'.date('Y-m-d H:i:s'));
		}
		$logParams['parameters'] = json_encode(array('request'=>$param,'response'=>$result));
		AuditLog::getInstance()->method('add', $logParams);
		return $returnData;
	}

	/**
	 * [更新员工信息]
	 * @param  array(
	 * 				'login_id'  => 1,
	 * 				'real_name' =>'zhangsan',
	 * 				'leader_id' => 5,
	 * 				'dept_id'	=> 8,
	 * 				'join_time' => 1412010101011,
	 * 				'status'    => 0,
	 * 				)
	 *   
	 * @return array
	 */
	function updateStaff($param=array()){
        $returnData = array('code'=>0,'info'=>'更新成功','data'=>'');

		if(empty($param['login_id'])){
			return array('code'=>1021,'info'=>"员工id不能为空",'data'=>'');
		}
        $valid = $this->checkInput($param);
        if($valid['code'] != 0){
            return $valid;
        }
		$archives_param = array(
            'realname' => $param['realname'],
            'leader_id' => base64_encode($param['leader_id']),
            'dept_id'   => $param['dept_id'],
            'join_time' => strtotime($param['join_time']),
            'status'    => $param['status'],
            'modifier_id' => Yii::app()->user->getState('_user')['login_id'],
            'is_claim'     => $param['is_claim']
        );

        $ipassport_param = array(
            'modifier_id'   => Yii::app()->user->getState('_user')['login_id'],
            'email'         => $param['email'],
            'staff_name'         => $param['staff_name'],
            'mobile'         => $param['mobile'],
        );

        //审计日志
		$logParams = array(
				'user_id'=> Yii::app()->user->getState('_user')['login_id'],
				'system' => 'newuser',
				'action' => 'delete',
				'resource'=> 'staff',
				'parameters'=>'',
				'status' => 'false'
			);
		$result = json_decode($this->curl->post($this->getRequestUrl().'/iprofile/v1/staff/'.base64_encode($param['login_id']),json_encode($archives_param,JSON_UNESCAPED_UNICODE)),true);

        if(0 == $result['code']){
            $ipassport_res = json_decode($this->curl->patch($this->getRequestUrl().'/ipassport/v1/credential/staff/'.base64_encode($param['login_id']),json_encode($ipassport_param,JSON_UNESCAPED_UNICODE)),true);
			if($ipassport_res['code'] == 0){
                $logParams['status'] = 'success';
                $returnData = array('code'=>0,'info'=>'更新成功','data'=>'');
                $this->updateOldTable($param);
            }elseif($ipassport_res['code'] == 1007){
                $returnData['code'] = 1050;
                $returnData['info'] = '用户名，手机号或邮箱已存在';
                return $returnData;
            }else{
                $returnData['code'] = 100;
                $returnData['info'] = '修改信息失败';
                return $returnData;
            }
		}elseif(1252 == $result['code']){
            $returnData = array('code'=>1014,'info'=>'部门不存在','data'=>'');
		}elseif(1263 == $result['code']){
            $returnData = array('code'=>1020,'info'=>'主管id不存在','data'=>'');
		}else{
            $returnData = array('code'=>-1,'info'=>'未知错误','data'=>'');
			Yii::log('update staff error,info:'.json_decode(array('request'=>$param,'response'=>$result)).' time:'.date('Y-m-d H:i:s'));
		}
		$logParams['parameters'] = json_encode(array('request'=>$param,'response'=>$result));
		AuditLog::getInstance()->method('add', $logParams);
		return $returnData;
	}

	/**
	 * [查找员工]
	 * @param  string $realname 员工姓名（支持前缀模糊匹配）
	 * @param  string $leader_id
	 * @param  int $dept_id
	 * @return array
	 */
	function listStaff($realname,$leader_id,$dept_id){

		$param['page_num'] = 1;
		$param['page_size'] = 20;
        $url = $this->getRequestUrl().'/iprofile/v1/staff?page_size='.$param['page_size'];
        $url .= '&page_num='.$param['page_num'];
        if(!empty($realname)){
			$realname = trim($realname);
            $url .= '&realname='.urlencode($realname);
        }
        if(!empty($dept_id)){
            $url .= '&dept_id='.$dept_id;
        }
        if(!empty($leader_id)){
            $url .= '&leader_id='.$leader_id;
        }
        $result = json_decode($this->curl->get($url),true);

		$staffs = array();
		if(0==$result['code']){
			$staffs = $result['data']['list'];
			$total = $result['data']['total'];
			$listNum = count($result['data']['list']);
			while($total > $listNum){
				$param['page_num'] ++;
                $url = $this->getRequestUrl().'/iprofile/v1/staff?page_size='.$param['page_size'];
                $url .= '&page_num='.$param['page_num'];

                if(!empty($realname)){
                    $url .= '&realname='.urlencode($realname);
                }
                if(!empty($dept_id)){
                    $url .= '&dept_id='.$dept_id;
                }
                if(!empty($leader_id)){
                    $url .= '&leader_id='.$leader_id;
                }
                $result = json_decode($this->curl->get($url),true);
				$total = $total = $result['data']['total'];
				$listNum += count($result['data']['list']);
				$staffs = array_merge($staffs,$result['data']['list']);
			}
            $staffNames = $this->getStaffInfo($staffs);
            foreach($staffs as $k=>&$v){
                $v['login_id'] = base64_decode($v['login_id']);
                $v['leader_id'] = base64_decode($v['leader_id']);
                $v['create_time'] = $v['create_time'];
                $v['modify_time'] = $v['modify_time'];
                $v['join_time']   = date('Y-m-d',$v['join_time']);
                $v['staff_name'] = $staffNames[$v['login_id']]['staff_name'];
                $v['mobile']    = $staffNames[$v['login_id']]['mobile'];
                $v['email']     = $staffNames[$v['login_id']]['email'];
            }
			$returnData = array('code'=>0,'info'=>'获取成功','data'=>array('listTotal'=>$total,'listInfo'=>$staffs));
		}else{
			$returnData = array('code'=>-1,'info'=>'未知错误','data'=>'');
			Yii::log('search staff error,info:'.json_encode(array('request'=>$param,'response'=>$staffs)).' time:'.date('Y-m-d H:i:s'));
		}
		
		return $returnData;

	}


    private function getStaffInfo($login_arr){
        $login_ids_arr = array();
        foreach($login_arr as $k => &$v){
            $login_ids_arr[] = $v['login_id'];
        }
        $login_ids_str = implode(',',$login_ids_arr);

        $result = json_decode($this->curl->get($this->getRequestUrl().'/ipassport/v1/identity?login_id='.$login_ids_str),true);
        $return = array();
        if($result['code'] == 0 && !empty($result['data'])){
            $staffs = $result['data'];
            foreach($staffs as $k => $v){
                $return[base64_decode($v['login_id'])] = $v;
            }
        }else{
            return false;
        }
        return $return;
    }

    /**
     * 向itz_user表中添加一条数据
     * @param $data
     * @return bool
     */
    public function addToOldTable($data){
        $userInfo = array(
            'username' => $data['staff_name'],
            'email'    => $data['email'],
            'phone'    => $data['mobile'],
            'is_claim' => $data['is_claim'],
            'realname' => $data['realname'],
            'sector'   => $data['dept_id']-1,
            'updatetime' => time()
        );


        //日志审计参数
        $logParams = array(
            'user_id'=>Yii::app()->user->getState('_user')['login_id'],'system'=>'newuser','action'=>'add','resource'=>'newuser/staff','parameters'=>'','status'=>'fail'
        );
        $condition = '(email=:email or username=:username or phone=:phone) and status=1';
        $params = array(
            ':email' => $userInfo['email'],
            ':username' => $userInfo['username'],
            ':phone'    => $userInfo['phone']
        );
        $criteria = new CDbCriteria();
        $criteria->condition = $condition;
        $criteria->params = $params;
        $model=new ItzUser();
        $is_exist = $model->find($criteria);
        if(empty($is_exist)){
            $pwd = md5('weefd$@dfd12)');
            $model->attributes  = $userInfo;
            $model->password    = md5($pwd);
            $model->addtime     = time();
            $model->operator_id = 12;
            $model->operator_ip = Yii::app()->request->userHostAddress;

            if($model->save()){
                $logParams['parameters'] = json_encode(array('invest_user_id'=>$model->attributes['id']));
                $logParams['status'] = 'success';
                AuditLog::getInstance()->method('add', $logParams);
                return $model->attributes['id'];
            }else{
                AuditLog::getInstance()->method('add', $logParams);
                Yii::log('添加失败');
                return false;
            }
        }else{
            return $is_exist->attributes['id'];
        }

    }

    public function updateOldTable($data){

        $userId = ArcUserStaff::model()->find(['condition'=>'login_id='.$data['login_id']]);

        if(empty($userId)){
            Yii::log('update user info faild,info: '.json_encode($data));
            return false;
        }
        $oldId = $userId->attributes['user_id'];
        $userInfo = array(
            'id'  => $oldId,
            'username' => $data['staff_name'],
            'email'    => $data['email'],
            'phone'    => $data['mobile'],
            'is_claim' => $data['is_claim'],
            'realname' => $data['realname'],
            'sector'   => $data['dept_id']-1,
            'updatetime' => time()
        );


        //日志审计参数
        $logParams = array(
            'user_id'=>Yii::app()->user->getState('_user')['login_id'],'system'=>'newuser','action'=>'update','resource'=>'newuser/staff','parameters'=>'','status'=>'fail'
        );


        $model= ItzUser::model()->findByPk($oldId);
        $model->attributes = $userInfo;

        if($model->save()){
            $logParams['parameters'] = json_encode(array('invest_user_id'=>$model->attributes['id']));
            $logParams['status'] = 'success';
            AuditLog::getInstance()->method('add', $logParams);
            return true;
        }else{
            AuditLog::getInstance()->method('add', $logParams);
            Yii::log('添加失败');
            return false;
        }

    }

    /**
     * 添加到对照表
     * @param $new_id
     * @param $old_id
     * @return bool
     */
    function addToReferenceTable($new_id,$old_id){
        Yii::log("yong hu id :".$new_id."  ".$old_id);
        if(empty($new_id) || empty($old_id)){
            return false;
        }
        $model = new ArcUserStaff();
        $model->user_id = $old_id;
        $model->login_id = $new_id;
        $model->create_time = time();
        if($model->save()){
            return $model->attributes['id'];
        }else{
            return false;
        }
    }

    function checkInput($data){
        if(empty($data['dept_id'])){
            return array('code'=>1045,'info'=>'不可在公司下添加员工','data'=>'');
        }
        if(empty($data['realname'])){
            return array('code'=>1017,'info'=>'员工姓名不能为空','data'=>'');
        }
        if(empty($data['mobile'])){
            return array('code'=>1018,'info'=>'手机号不能为空','data'=>'');
        }
        if(empty($data['join_time']) || strtotime( date('Y-m-d', strtotime($data['join_time'])) ) !== strtotime($data['join_time'])){
            return array('code'=>1046,'info'=>'入职时间格式不正确','data'=>'');
        }
        if(!FunctionUtil::IsMobile($data['mobile'])){
            return array('code'=>1043,'info'=>'手机号码格式不正确','data'=>'');
        }
        if(empty($data['staff_name'])){
            return array('code'=>1019,'info'=>'用户名不能为空','data'=>'');
        }
        if(strlen($data['staff_name']) > 50){
            return array('code'=>1048,'info'=>'用户名长度不能超过50','data'=>'');
        }
        if(!preg_match('/^[\w|0-9]+$/',$data['staff_name'])){
            return array('code'=>1046,'info'=>'用户名只能是数字字母下划线','data'=>'');
        }
        if(!preg_match('/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/',$data['email'])){
            return array('code'=>1047,'info'=>'邮箱格式不正确','data'=>'');
        }
        if(preg_match("/\s|\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\\' | \`|\-|\=|\\\|\|/",trim($data['realname']))){
            return array('code'=>1046,'info'=>'真实姓名中不能有特殊字符','data'=>'');
        }
        return array('code'=>0,'info'=>'success','data'=>'');
    }

}