<?php
use iauth\models\AuthAssignment;

class DebtBuyerController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
               'Success','Error','AssigneeChangeUserId'
          );
    }
    /**
     * 成功提示页 张健
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionSuccess($msg = '成功', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页 张健
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }
    /**
    * 上传压缩文件 张健
    * @param name  string  压缩文件名称
    * @return array
    */
    private function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的压缩文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的压缩文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '压缩文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有压缩文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '压缩文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '压缩文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => '压缩文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建压缩文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存压缩文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存压缩文件失败' , 'data' => '');
        }
    }
    /**
     * 受让方列表 - 列表
     */
    public function actionAssigneeList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND assi.user_id = {$user_id} ";
            }
            // 校验姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND user.real_name = '{$real_name}' ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验证件号码
            if (!empty($_POST['idno'])) {
                $idno = trim($_POST['idno']);
                $idno = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.idno = '{$idno}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']);
                $where .= " AND assi.status = {$sta} ";
            }
           
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql = "SELECT count(assi.id) AS count FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT assi.id ,assi.trading_amount, assi.user_id ,assi.area_id,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time  FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 {$where} ORDER BY assi.id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            $status[1] = '待审核';
            $status[2] = '审核通过';
            $status[3] = '审核通过（暂停）';
            $buyer_type = array(1 => '通用受让方' , 2 => '指定借款ID受让方');
            $area_list = ArrayUntil::array_column(Yii::app()->c->xf_config['area_list'], 'name', 'id');
            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            foreach ($list as $key => $value) {
                $value['area_name'] =  $area_list[$value['area_id']];
                if ($value['mobile']) {
                    $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                }
                if ($value['idno']) {
                    $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                }
                $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
                $value['transferability_limit']         = number_format($value['transferability_limit'], 2, '.', ',');
                $value['transferred_amount']            = number_format($value['transferred_amount'], 2, '.', ',');
                $value['trading_amount']                = number_format($value['trading_amount'], 2, '.', ',');
                $value['status_name']                   = $status[$value['status']];
                $value['buyer_type_name']               = $buyer_type[$value['buyer_type']];
                $temp = explode('/', $value['agreement_url']);
                if ($temp[0] == 'upload') {
                    $value['agreement_url'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='/{$value['agreement_url']}' download title='下载合作框架协议'><i class='layui-icon'>&#xe601;</i>下载</a>";
                } elseif ($temp[0] == 'assignee_info') {
                    $value['agreement_url'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='".Yii::app()->c->oss_preview_address."/".$value['agreement_url']."' target='_blank' title='下载合作框架协议'><i class='layui-icon'>&#xe601;</i>下载</a>";
                }
                $value['edit_status']    = 0;
                $value['verify_status']  = 0;
                $value['del_status']     = 0;
                $value['suspend_status'] = 0;
                if (!empty($authList) && strstr($authList, '/debtMarket/DebtBuyer/EditAssignee') || empty($authList)) {
                    $value['edit_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/debtMarket/DebtBuyer/VerifyAssignee') || empty($authList)) {
                    $value['verify_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/debtMarket/DebtBuyer/DelAssignee') || empty($authList)) {
                    $value['del_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/debtMarket/DebtBuyer/SuspendAssignee') || empty($authList)) {
                    $value['suspend_status'] = 1;
                }
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $add_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/AddAssignee') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('AssigneeList', array('add_status' => $add_status));
    }

    /**
     * 文件上传OSS
     * @param $filePath
     * @param $ossPath
     * @return bool
     */
    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            unlink($filePath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * 受让方列表 - 添加
     */
    public function actionAddAssignee()
    {
        if (\Yii::app()->request->isPostRequest) {
            if (empty($_POST['area_id']) || !is_numeric($_POST['area_id'])) {
                return $this->actionError('请选择专区', 5);
            }
            if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                return $this->actionError('请正确输入用户ID', 5);
            }
            $user_id = intval($_POST['user_id']);
            $sql     = "SELECT * FROM firstp2p_user WHERE id = {$user_id}";
            $res     = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('用户ID输入错误', 5);
            }
            if ($res['is_effect'] != 1) {
                return $this->actionError('此用户账号无效', 5);
            }
            if ($res['is_delete'] != 0) {
                return $this->actionError('此用户账号已被放入回收站', 5);
            }
            if ($res['user_type'] != 1) {
                return  $this->actionError('该用户非企业用户', 5);
            }
            $sql   = "SELECT * FROM xf_debt_assignee_info WHERE user_id = {$user_id} AND status != 0 and area_id={$_POST['area_id']}";
            $check = Yii::app()->phdb->createCommand($sql)->queryRow();
            if ($check) {
                return $this->actionError('此用户ID已被加入受让方', 5);
            }
            if (empty($_POST['limit']) || !is_numeric($_POST['limit']) || $_POST['limit'] <= 0 || $_POST['limit'] > 1000000000) {
                return $this->actionError('请正确输入受让额度', 5);
            }
            $transferability_limit = round($_POST['limit'], 2);
            $file = $this->upload_rar('file');
            if ($file['code'] !== 0) {
                return $this->actionError($file['info'], 5);
            }
            $upload_oss = $this->upload_oss('./'.$file['data'], 'assignee_info/'.$file['data']);
            if ($upload_oss === false) {
                return $this->actionError('合作框架协议上传至OSS失败', 5);
            }
            unlink('./'.$file['data']);
            $agreement_url = 'assignee_info/'.$file['data'];
          
            $time        = time();
          
        
            $op_user_id = Yii::app()->user->id ?: 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $sql = "INSERT INTO xf_debt_assignee_info (`user_id` , area_id, transferability_limit , agreement_url , `status` , add_user_id , add_ip , add_time , update_time) VALUES ( {$user_id} ,{$_POST['area_id']} , {$transferability_limit} , '{$agreement_url}' , 1 , {$op_user_id} , '{$ip}' , {$time} , {$time}) ";
            $add_assignee_info = Yii::app()->phdb->createCommand($sql)->execute();

            if (!$add_assignee_info) {
                return $this->actionError('添加受让方失败', 5);
            }
            return $this->actionSuccess('添加受让方成功', 3);
        }

        return $this->renderPartial('AddAssignee', ['area_list'=>Yii::app()->c->xf_config['area_list']]);
    }


    /**
     * 受让方列表 - 编辑
     */
    public function actionEditAssignee()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                return $this->actionError('请输入ID', 5);
            }
            $fdb  = Yii::app()->fdb;
            $time = time();
            $id   = intval($_POST['id']);
            $sql  = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time, assi.area_id  FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = $fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            if ($res['status'] == 1) {
                if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                    return $this->actionError('请正确输入用户ID', 5);
                }
                $user_id   = intval($_POST['user_id']);
                $sql       = "SELECT * FROM firstp2p_user WHERE id = {$user_id}";
                $user_info = $fdb->createCommand($sql)->queryRow();
                if (!$user_info) {
                    return $this->actionError('用户ID输入错误', 5);
                }
                if ($user_info['is_effect'] != 1) {
                    return $this->actionError('此用户账号无效', 5);
                }
                if ($user_info['is_delete'] != 0) {
                    return $this->actionError('此用户账号已被放入回收站', 5);
                }
                if ($res['user_type'] != 1) {
                    return  $this->actionError('该用户非企业用户', 5);
                }
                $sql   = "SELECT * FROM xf_debt_assignee_info WHERE user_id = {$user_id} AND status != 0 AND id != {$res['id']}";
                $check = Yii::app()->phdb->createCommand($sql)->queryRow();
                if ($check) {
                    return $this->actionError('此用户ID已被加入受让方', 5);
                }
                $user_id_sql  = " , user_id = {$user_id} ";
                $add_user_sql = " , add_user_id = {$op_user_id} , add_ip = '{$ip}' , add_time = {$time} ";
            } else {
                $user_id_sql  = "";
                $user_id      = $res['user_id'];
                $add_user_sql = " , update_user_id = {$op_user_id} , update_ip = '{$ip}' , update_time = {$time} ";
            }
            if (empty($_POST['limit']) || !is_numeric($_POST['limit']) || $_POST['limit'] < $res['transferred_amount'] || $_POST['limit'] > 1000000000) {
                return $this->actionError('请正确输入受让额度', 5);
            }
            $transferability_limit = round($_POST['limit'], 2);
            $file = $this->upload_rar('file');
            if ($file['code'] === 0) {
                $upload_oss = $this->upload_oss('./'.$file['data'], 'assignee_info/'.$file['data']);
                if ($upload_oss === false) {
                    return $this->actionError('合作框架协议上传至OSS失败', 5);
                }
                unlink('./'.$file['data']);
                $agreement_url     = 'assignee_info/'.$file['data'];
                $agreement_url_sql = " , agreement_url = '{$agreement_url}' ";
            } else {
                $agreement_url_sql = "";
            }
           
         
            $sql = "UPDATE xf_debt_assignee_info SET update_time = {$time} , transferability_limit = {$transferability_limit} {$user_id_sql} {$agreement_url_sql} {$add_user_sql}  WHERE id = {$res['id']} ";
            $update_assignee_info = Yii::app()->phdb->createCommand($sql)->execute();

            if (!$update_assignee_info) {
                return $this->actionError('保存失败', 5);
            }
         
            return $this->actionSuccess('保存成功', 3);
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time ,assi.area_id FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            if ($res['mobile']) {
                $res['mobile'] = GibberishAESUtil::dec($res['mobile'], Yii::app()->c->idno_key);
            }
            if ($res['idno']) {
                $res['idno'] = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            }
            $agreement_url         = explode('/', $res['agreement_url']);
            if ($agreement_url[0] == 'upload') {
                $res['agreement_name'] = $agreement_url[2];
            } elseif ($agreement_url[0] == 'assignee_info') {
                $res['agreement_name'] = $agreement_url[3];
            }
        } else {
            return $this->actionError('请输入ID', 5);
        }

        return $this->renderPartial('EditAssignee', array('res' => $res,'area_list'=>Yii::app()->c->xf_config['area_list']));
    }

    /**
     * 受让方列表 - 审核
     */
    public function actionVerifyAssignee()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1, '请输入ID');
            }
            $fdb       = Yii::app()->fdb;
          
            $id  = intval($_POST['id']);
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time  FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = $fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 2, 'ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array(), 3, '状态错误');
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $time       = time();
           
            $sql = "UPDATE xf_debt_assignee_info SET update_time = {$time} , status = 2 , update_user_id = {$op_user_id} , update_ip = '{$ip}' WHERE id = {$res['id']} ";
            $update_assignee_info = Yii::app()->phdb->createCommand($sql)->execute();
            if (!$update_assignee_info) {
                $this->echoJson(array(), 100, '数据更新失败，请重试');
            }
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 受让方列表 - 移除
     */
    public function actionDelAssignee()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1, '请输入ID');
            }
            $fdb = Yii::app()->fdb;
            $id  = intval($_POST['id']);
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = $fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 2, 'ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array(), 3, '状态错误');
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $time       = time();
           
            $sql = "UPDATE xf_debt_assignee_info SET update_time = {$time} , status = 0 , update_user_id = {$op_user_id} , update_ip = '{$ip}' WHERE id = {$res['id']} ";
            $result = Yii::app()->phdb->createCommand($sql)->execute();
            
            if (!$result) {
                $this->echoJson(array(), 4, '操作失败');
            }
         
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 受让方列表 - 暂停
     */
    public function actionSuspendAssignee()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1, '请输入ID');
            }
            $fdb       = Yii::app()->fdb;
          

            $id  = intval($_POST['id']);
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time  FROM ncfph.xf_debt_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = $fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 2, 'ID输入错误');
            }
            if (!in_array($res['status'], [2,3])) {
                $this->echoJson(array(), 3, '状态错误');
            }
            $status = $res['status']==2?3:2;
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $time = time();
            $sql = "UPDATE xf_debt_assignee_info SET update_time = {$time} , status = {$status} , update_ip = '{$ip}' WHERE id = {$res['id']} ";
            $update_assignee_info = Yii::app()->phdb->createCommand($sql)->execute();

            if (!$update_assignee_info) {
                $this->echoJson(array(), 5, '操作失败');
            }
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 受让方列表 - AJAX校验用户ID
     */
    public function actionAssigneeChangeUserId()
    {
        if (!empty($_POST['user_id'])) {
            $user_id = trim($_POST['user_id']);
            if (!is_numeric($user_id) || $user_id < 1) {
                $this->echoJson(array(), 2, '请正确输入用户ID');
            }
            $user_id = intval($user_id);
            $sql     = "SELECT * FROM firstp2p_user WHERE id = {$user_id}";
            $res     = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 3, '用户ID输入错误');
            }
            if ($res['is_effect'] != 1) {
                $this->echoJson(array(), 4, '此用户账号无效');
            }
            if ($res['is_delete'] != 0) {
                $this->echoJson(array(), 5, '此用户账号已被放入回收站');
            }
            if ($res['user_type'] != 1) {
                $this->echoJson(array(), 5, '该用户非企业用户');
            }
            
            $result['user_id']   = $res['id'];
            $result['real_name'] = $res['real_name'];
            if ($res['mobile']) {
                $result['mobile'] = GibberishAESUtil::dec($res['mobile'], Yii::app()->c->idno_key);
            } else {
                $result['mobile'] = '';
            }
            if ($res['idno']) {
                $result['idno'] = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            } else {
                $result['idno'] = '';
            }
            $this->echoJson($result, 0, '查询成功');
        } else {
            $this->echoJson(array(), 1, '请输入用户ID');
        }
    }
}
