<?php
use iauth\models\AuthAssignment;
class EnterpriseController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'CheckCompanyName' , 'CheckCredentialsNo' , 'CheckLegalbodyMobile'
        );
    }

    /**
     * 成功提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionSuccess($msg = '成功' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    /**
     * 上传图片
     * @param name  string  图片名称
     * @return string
     */
    private function upload($name)
    {
        $file  = $_FILES[$name];
        $types = array('image/jpg' , 'image/jpeg' , 'image/png' , 'image/pjpeg' , 'image/gif' , 'image/bmp' , 'image/peg');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的图片超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的图片超过了脚本显示' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '图片只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有图片被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '图片写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '图片上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $file_type = $file['type'];
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => '图片类型不匹配' , 'data' => '');
        }
        $new_name = date('His' . rand(1000,9999));
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建图片目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.jpg';
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存图片成功' , 'data' => $new_url , 'new_name' => $new_name . '.jpg');
        } else {
            return array('code' => 2009 , 'info' => '保存图片失败' , 'data' => '');
        }
    }

    /**
     * 文件上传OSS
     * @param filePath
     * @param ossPath
     * @return bool
     */
    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * 法大大企业信息认证 列表
     */
    public function actionEnterprise()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " status != 0 ";
            // 校验企业全称
            if (!empty($_POST['company_name'])) {
                $company_name = trim($_POST['company_name']);
                $where       .= " AND company_name = '{$company_name}' ";
            }
            // 校验企业证件号码
            if (!empty($_POST['credentials_no'])) {
                $credentials_no = trim($_POST['credentials_no']);
                $where         .= " AND credentials_no = '{$credentials_no}' ";
            }
            // 校验法定代表人手机号码
            if (!empty($_POST['legalbody_mobile'])) {
                $legalbody_mobile = trim($_POST['legalbody_mobile']);
                $where           .= " AND legalbody_mobile = '{$legalbody_mobile}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']);
                $where .= " AND status = {$sta} ";
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
            $sql = "SELECT count(*) AS count FROM xf_add_fdd_enterprise WHERE {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE {$where} ORDER BY status ASC , id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            $status[1] = '待审核';
            $status[2] = '审核未通过';
            $status[3] = '审核通过';
            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            foreach ($list as $key => $value) {
                $value['add_time']       = date('Y-m-d H:i:s' , $value['add_time']);
                $value['status_name']    = $status[$value['status']];
                $value['edit_status']    = 0;
                $value['verify_status']  = 0;
                $value['del_status']     = 0;
                if (!empty($authList) && strstr($authList,'/user/Enterprise/EditEnterprise') || empty($authList)) {
                    $value['edit_status'] = 1;
                }
                if (!empty($authList) && strstr($authList,'/user/Enterprise/VerifyEnterprise') || empty($authList)) {
                    $value['verify_status'] = 1;
                }
                if (!empty($authList) && strstr($authList,'/user/Enterprise/DelEnterprise') || empty($authList)) {
                    $value['del_status'] = 1;
                }
                $listInfo[] = $value;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $AddEnterprise = 0;
        if (!empty($authList) && strstr($authList,'/user/Enterprise/AddEnterprise') || empty($authList)) {
            $AddEnterprise = 1;
        }
        return $this->renderPartial('Enterprise', array('AddEnterprise' => $AddEnterprise));
    }

    /**
     * 法大大企业信息认证 新增
     */
    public function actionAddEnterprise()
    {
        if (!empty($_POST)) {
            if (empty($_POST['company_name'])) {
                return $this->actionError('请输入企业全称' , 5);
            }
            if (empty($_POST['credentials_no'])) {
                return $this->actionError('请输入企业证件号码' , 5);
            }
            if (empty($_POST['legalbody_name'])) {
                return $this->actionError('请输入法定代表人姓名' , 5);
            }
            if (empty($_POST['legalbody_credentials_no'])) {
                return $this->actionError('请输入法定代表人证件号码' , 5);
            }
            if (empty($_POST['legalbody_mobile'])) {
                return $this->actionError('请输入企业联系电话(手机号)' , 5);
            }
            if (empty($_POST['registration_address'])) {
                return $this->actionError('请输入企业注册地址' , 5);
            }
            if (empty($_POST['contract_address'])) {
                return $this->actionError('请输入企业联系地址' , 5);
            }
            if (empty($_FILES['credit_code_file'])) {
                return $this->actionError('请上传统一社会信用代码电子版' , 5);
            }
            if (empty($_FILES['power_attorney_file'])) {
                return $this->actionError('请上传授权委托书电子版' , 5);
            }
            $company_name             = trim($_POST['company_name']);
            $credentials_no           = trim($_POST['credentials_no']);
            $legalbody_name           = trim($_POST['legalbody_name']);
            $legalbody_credentials_no = trim($_POST['legalbody_credentials_no']);
            $legalbody_mobile         = trim($_POST['legalbody_mobile']);
            $registration_address     = trim($_POST['registration_address']);
            $contract_address         = trim($_POST['contract_address']);
            $enterprise_id            = intval($_POST['enterprise_id']);
            $user_id                  = intval($_POST['user_id']);

            // 检验企业全称
            $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.company_name = '{$company_name}' AND user.is_effect = 1 AND user.is_delete = 0 ";
            $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_company_name && !empty($check_company_name['yj_fdd_customer_id'])) {
                return $this->actionError('此企业全称已经存在' , 5);
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE company_name = '{$company_name}' AND status = 1 ";
            $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_company_name) {
                return $this->actionError('此企业全称已在申请中并等待审核' , 5);
            }
            // 检验企业证件号码
            $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.credentials_no = '{$credentials_no}' AND user.is_effect = 1 AND user.is_delete = 0 ";
            $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_credentials_no && !empty($check_credentials_no['yj_fdd_customer_id'])) {
                return $this->actionError('此企业证件号码已经存在' , 5);
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE credentials_no = '{$credentials_no}' AND status = 1 ";
            $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_credentials_no) {
                return $this->actionError('此企业证件号码已在申请中并等待审核' , 5);
            }
            // 检验法定代表人手机号码
            $mobile = GibberishAESUtil::enc($legalbody_mobile , Yii::app()->c->idno_key);
            $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
            $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_legalbody_mobile) {
                return $this->actionError('此企业联系电话(手机号)已经存在' , 5);
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE legalbody_mobile = '{$legalbody_mobile}' AND status = 1 ";
            $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_legalbody_mobile) {
                return $this->actionError('此企业联系电话(手机号)已在申请中并等待审核' , 5);
            }
            // 校验统一社会信用代码电子版
            $credit_code_file = $this->upload('credit_code_file');
            if ($credit_code_file['code'] !== 0) {
                return $this->actionError($credit_code_file['info'] , 5);
            }
            // 校验授权委托书电子版
            $power_attorney_file = $this->upload('power_attorney_file');
            if ($power_attorney_file['code'] !== 0) {
                return $this->actionError($power_attorney_file['info'] , 5);
            }

            $fdb         = Yii::app()->fdb;
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "INSERT INTO xf_add_fdd_enterprise (status , add_user_id , add_ip , add_time , update_time , company_name , credentials_no , legalbody_name , legalbody_credentials_no , legalbody_mobile , registration_address , contract_address , credit_code_file , power_attorney_file , enterprise_id , user_id) VALUES (1 , {$add_user_id} , '{$add_ip}' , {$time} , {$time} , '{$company_name}' , '{$credentials_no}' , '{$legalbody_name}' , '{$legalbody_credentials_no}' , '{$legalbody_mobile}' , '{$registration_address}' , '{$contract_address}' , '{$credit_code_file['data']}' , '{$power_attorney_file['data']}' , {$enterprise_id} , {$user_id})";
            $result = $fdb->createCommand($sql)->execute();

            if (!$result) {
                return $this->actionError('新增失败' , 5);
            }
            return $this->actionSuccess('新增成功' , 3);
        }

        return $this->renderPartial('AddEnterprise', array());
    }

    /**
     * 法大大企业信息认证 审核
     */
    public function actionVerifyEnterprise()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                return $this->actionError('请输入申请ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('此申请状态非待审核' , 5);
            }
            if (empty($_POST['status']) || !in_array($_POST['status'] , [2, 3])) {
                return $this->actionError('请正确输入操作参数' , 5);
            }
            if ($res['update_time'] != $_POST['update_time']) {
                return $this->actionError('此申请信息已被修改' , 5);
            }
            $time          = time();
            $check_user_id = Yii::app()->user->id;
            $check_user_id = $check_user_id ? $check_user_id : 0 ;
            $check_ip      = Yii::app()->request->userHostAddress;

            $fdb  = Yii::app()->fdb;
            $phdb = Yii::app()->phdb;
            $fdb->beginTransaction();
            $phdb->beginTransaction();

            if ($_POST['status'] == 2) {

                $sql = "UPDATE xf_add_fdd_enterprise SET status = 2 , check_user_id = {$check_user_id} , check_time = {$time} , check_ip = '{$check_ip}' WHERE id = {$id} ";
                $update_log = $fdb->createCommand($sql)->execute();

                $add_fdb_user     = true;
                $add_phdb_user    = true;
                $update_fdb_user  = true;
                $update_phdb_user = true;
                $add_enterprise   = true;

            } else if ($_POST['status'] == 3 && $res['enterprise_id'] == 0 && $res['user_id'] == 0) {

                // 检验企业全称
                $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.company_name = '{$res['company_name']}' AND user.is_effect = 1 AND user.is_delete = 0";
                $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_company_name && !empty($check_company_name['yj_fdd_customer_id'])) {
                    return $this->actionError('此企业全称已经存在' , 5);
                }
                $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE company_name = '{$res['company_name']}' AND status = 1 AND id != {$id} ";
                $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_company_name) {
                    return $this->actionError('此企业全称已在申请中并等待审核' , 5);
                }
                // 检验企业证件号码
                $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.credentials_no = '{$res['credentials_no']}' AND user.is_effect = 1 AND user.is_delete = 0 ";
                $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_credentials_no && !empty($check_credentials_no['yj_fdd_customer_id'])) {
                    return $this->actionError('此企业证件号码已经存在' , 5);
                }
                $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE credentials_no = '{$res['credentials_no']}' AND status = 1 AND id != {$id} ";
                $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_credentials_no) {
                    return $this->actionError('此企业证件号码已在申请中并等待审核' , 5);
                }
                // 检验法定代表人手机号码
                $mobile = GibberishAESUtil::enc($res['legalbody_mobile'] , Yii::app()->c->idno_key);
                $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
                $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_legalbody_mobile) {
                    return $this->actionError('此企业联系电话(手机号)已经存在' , 5);
                }
                $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE legalbody_mobile = '{$res['legalbody_mobile']}' AND status = 1 AND id != {$id} ";
                $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_legalbody_mobile) {
                    return $this->actionError('此企业联系电话(手机号)已在申请中并等待审核' , 5);
                }
                $idno = GibberishAESUtil::enc($res['credentials_no'] , Yii::app()->c->idno_key);
                $sql = "INSERT INTO firstp2p_user (user_name ,create_time , update_time , is_effect , is_delete , idno , real_name , mobile , user_type , user_purpose) VALUES ('{$res['legalbody_mobile']}' , {$time} , {$time} , 1 , 0 , '{$idno}' , '{$res['company_name']}' , '{$mobile}' , 1 , 1) ";
                $add_fdb_user = $fdb->createCommand($sql)->execute();
                $user_id      = $fdb->getLastInsertID();

                $sql = "INSERT INTO firstp2p_user (id , user_name , create_time , update_time , is_effect , is_delete , idno , real_name , mobile , user_type , user_purpose) VALUES ({$user_id} , '{$res['legalbody_mobile']}' , {$time} , {$time} , 1 , 0 , '{$idno}' , '{$res['company_name']}' , '{$mobile}' , 1 , 1) ";
                $add_phdb_user = $phdb->createCommand($sql)->execute();

                $sql = "INSERT INTO firstp2p_enterprise (user_id , company_purpose , company_name , credentials_type , credentials_no , legalbody_name , legalbody_credentials_type , legalbody_credentials_no , legalbody_mobile , registration_address , contract_address) VALUES ({$user_id} , 1 , '{$res['company_name']}' , 3 , '{$res['credentials_no']}' , '{$res['legalbody_name']}' , 1 , '{$res['legalbody_credentials_no']}' , '{$res['legalbody_mobile']}' , '{$res['registration_address']}' , '{$res['contract_address']}') ";
                $add_enterprise = $fdb->createCommand($sql)->execute();
                $enterprise_id  = $fdb->getLastInsertID();

                // 自动获取企业CA证书
                $credit_code_file    = $_SERVER['DOCUMENT_ROOT'] . "/{$res['credit_code_file']}";
                $power_attorney_file = $_SERVER['DOCUMENT_ROOT'] . "/{$res['power_attorney_file']}";
                $fdd_res = XfFddService::getInstance()->auto_apply_client_numcert($user_id , $res['company_name'] , $res['credentials_no'] , $res['legalbody_name'] , $res['legalbody_credentials_no'] , $res['legalbody_mobile'] , $credit_code_file , $power_attorney_file);
                if ($fdd_res['msg'] !== 'success') {
                    $fdb->rollback();
                    $phdb->rollback();
                    return $this->actionError($fdd_res['data'] , 5);
                }

                $sql = "UPDATE firstp2p_user SET fdd_customer_id = '{$fdd_res['customer_id']}' , yj_fdd_customer_id = '{$fdd_res['customer_id']}' WHERE id = {$user_id} ";
                $update_fdb_user  = $fdb->createCommand($sql)->execute();
                $update_phdb_user = $phdb->createCommand($sql)->execute();

                $credit_code_file_oss = $this->upload_oss('./'.$res['credit_code_file'] , 'add_fdd_enterprise/'.$res['credit_code_file']);
                if ($credit_code_file_oss === false) {
                    $fdb->rollback();
                    $phdb->rollback();
                    return $this->actionError('统一社会信用代码电子版上传至OSS失败' , 5);
                }

                $power_attorney_file_oss = $this->upload_oss('./'.$res['power_attorney_file'] , 'add_fdd_enterprise/'.$res['power_attorney_file']);
                if ($power_attorney_file_oss === false) {
                    $fdb->rollback();
                    $phdb->rollback();
                    return $this->actionError('授权委托书电子版上传至OSS失败' , 5);
                }

                $sql = "UPDATE xf_add_fdd_enterprise SET status = 3 , update_time = {$time} , credit_code_file = 'add_fdd_enterprise/{$res['credit_code_file']}' , power_attorney_file = 'add_fdd_enterprise/{$res['power_attorney_file']}' , check_user_id = {$check_user_id} , check_time = {$time} , check_ip = '{$check_ip}' , user_id = {$user_id} , enterprise_id = {$enterprise_id} , customer_id = '{$fdd_res['customer_id']}' , evidence_no = '{$fdd_res['evidence_no']}' , signature_img_base64 = '{$fdd_res['signature_img_base64']}' , transactionId = '{$fdd_res['transactionId']}' , transaction_id = '{$fdd_res['transaction_id']}' WHERE id = {$id} ";
                $update_log = $fdb->createCommand($sql)->execute();

            } else if ($_POST['status'] == 3 && $res['enterprise_id'] > 0 && $res['user_id'] > 0) {

                // 检验企业全称
                $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.company_name = '{$res['company_name']}' AND user.is_effect = 1 AND user.is_delete = 0";
                $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_company_name && !empty($check_company_name['yj_fdd_customer_id'])) {
                    return $this->actionError('此企业全称已经存在' , 5);
                }
                $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE company_name = '{$res['company_name']}' AND status = 1 AND id != {$id} ";
                $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_company_name) {
                    return $this->actionError('此企业全称已在申请中并等待审核' , 5);
                }
                // 检验企业证件号码
                $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.credentials_no = '{$res['credentials_no']}' AND user.is_effect = 1 AND user.is_delete = 0 ";
                $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_credentials_no && !empty($check_credentials_no['yj_fdd_customer_id'])) {
                    return $this->actionError('此企业证件号码已经存在' , 5);
                }
                $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE credentials_no = '{$res['credentials_no']}' AND status = 1 AND id != {$id} ";
                $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_credentials_no) {
                    return $this->actionError('此企业证件号码已在申请中并等待审核' , 5);
                }
                // 检验法定代表人手机号码
                $mobile = GibberishAESUtil::enc($res['legalbody_mobile'] , Yii::app()->c->idno_key);
                $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
                $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_legalbody_mobile) {
                    return $this->actionError('此企业联系电话(手机号)已经存在' , 5);
                }
                $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE legalbody_mobile = '{$res['legalbody_mobile']}' AND status = 1 AND id != {$id} ";
                $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($check_legalbody_mobile) {
                    return $this->actionError('此企业联系电话(手机号)已在申请中并等待审核' , 5);
                }
                $idno = GibberishAESUtil::enc($res['credentials_no'] , Yii::app()->c->idno_key);

                $add_fdb_user   = true;
                $add_phdb_user  = true;

                $sql = "UPDATE firstp2p_enterprise SET company_name = '{$res['company_name']}' , credentials_no = '{$res['credentials_no']}' , legalbody_name = '{$res['legalbody_name']}' , legalbody_credentials_no = '{$res['legalbody_credentials_no']}' , legalbody_mobile = '{$res['legalbody_mobile']}' , registration_address = '{$res['registration_address']}' , contract_address = '{$res['contract_address']}' , update_time = {$time} WHERE id = {$res['enterprise_id']} ";
                $add_enterprise = $fdb->createCommand($sql)->execute();

                // 自动获取企业CA证书
                $credit_code_file    = $_SERVER['DOCUMENT_ROOT'] . "/{$res['credit_code_file']}";
                $power_attorney_file = $_SERVER['DOCUMENT_ROOT'] . "/{$res['power_attorney_file']}";
                $fdd_res = XfFddService::getInstance()->auto_apply_client_numcert($res['user_id'] , $res['company_name'] , $res['credentials_no'] , $res['legalbody_name'] , $res['legalbody_credentials_no'] , $res['legalbody_mobile'] , $credit_code_file , $power_attorney_file);
                if ($fdd_res['msg'] !== 'success') {
                    $fdb->rollback();
                    $phdb->rollback();
                    return $this->actionError($fdd_res['data'] , 5);
                }

                $sql = "UPDATE firstp2p_user SET idno = '{$idno}' , real_name = '{$res['company_name']}' , mobile = '{$mobile}' , fdd_customer_id = '{$fdd_res['customer_id']}' , yj_fdd_customer_id = '{$fdd_res['customer_id']}' WHERE id = {$res['user_id']} ";
                $update_fdb_user  = $fdb->createCommand($sql)->execute();
                $update_phdb_user = $phdb->createCommand($sql)->execute();

                $credit_code_file_oss = $this->upload_oss('./'.$res['credit_code_file'] , 'add_fdd_enterprise/'.$res['credit_code_file']);
                if ($credit_code_file_oss === false) {
                    $fdb->rollback();
                    $phdb->rollback();
                    return $this->actionError('统一社会信用代码电子版上传至OSS失败' , 5);
                }

                $power_attorney_file_oss = $this->upload_oss('./'.$res['power_attorney_file'] , 'add_fdd_enterprise/'.$res['power_attorney_file']);
                if ($power_attorney_file_oss === false) {
                    $fdb->rollback();
                    $phdb->rollback();
                    return $this->actionError('授权委托书电子版上传至OSS失败' , 5);
                }

                $sql = "UPDATE xf_add_fdd_enterprise SET status = 3 , update_time = {$time} , credit_code_file = 'add_fdd_enterprise/{$res['credit_code_file']}' , power_attorney_file = 'add_fdd_enterprise/{$res['power_attorney_file']}' , check_user_id = {$check_user_id} , check_time = {$time} , check_ip = '{$check_ip}' , customer_id = '{$fdd_res['customer_id']}' , evidence_no = '{$fdd_res['evidence_no']}' , signature_img_base64 = '{$fdd_res['signature_img_base64']}' , transactionId = '{$fdd_res['transactionId']}' , transaction_id = '{$fdd_res['transaction_id']}' WHERE id = {$id} ";
                $update_log = $fdb->createCommand($sql)->execute();
            }
            if (!$update_log || !$add_fdb_user || !$add_phdb_user || !$update_fdb_user || !$update_phdb_user || !$add_enterprise) {
                $fdb->rollback();
                $phdb->rollback();
                return $this->actionError('操作失败' , 5);
            }
            $fdb->commit();
            $phdb->commit();
            return $this->actionSuccess('操作成功' , 3);
        }
        

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('此申请状态非待审核' , 5);
            }
            $credit_code_file = explode('/', $res['credit_code_file']);
            if ($credit_code_file[0] == 'upload') {
                $res['credit_code_file_name'] = '/'.$res['credit_code_file'];
            } else if ($credit_code_file[0] == 'add_fdd_enterprise') {
                $res['credit_code_file_name'] = Yii::app()->c->oss_preview_address.'/'.$res['credit_code_file'];
            }
            $power_attorney_file = explode('/', $res['power_attorney_file']);
            if ($power_attorney_file[0] == 'upload') {
                $res['power_attorney_file_name'] = '/'.$res['power_attorney_file'];
            } else if ($power_attorney_file[0] == 'add_fdd_enterprise') {
                $res['power_attorney_file_name'] = Yii::app()->c->oss_preview_address.'/'.$res['power_attorney_file'];
            }
        } else {
            return $this->actionError('请输入申请ID' , 5);
        }

        return $this->renderPartial('VerifyEnterprise', array('res' => $res));
    }

    /**
     * 法大大企业信息认证 移除
     */
    public function actionDelEnterprise()
    {
        if (!empty($_POST['id'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 1 , '申请ID输入错误');
            }
            if (!in_array($res['status'] , [1, 2])) {
                $this->echoJson(array() , 1 , '此申请状态错误，无法移除');
            }
            $time          = time();
            $check_user_id = Yii::app()->user->id;
            $check_user_id = $check_user_id ? $check_user_id : 0 ;
            $check_ip      = Yii::app()->request->userHostAddress;

            $sql = "UPDATE xf_add_fdd_enterprise SET status = 0 , update_time = {$time} , check_user_id = {$check_user_id} , check_time = {$time} , check_ip = '{$check_ip}' WHERE id = {$id} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array() , 1 , '移除失败');
            }
            $this->echoJson(array() , 0 , '移除成功');
        }
    }

    /**
     * 法大大企业信息认证 编辑
     */
    public function actionEditEnterprise()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                return $this->actionError('请输入申请ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if (!in_array($res['status'] , [1, 2])) {
                return $this->actionError('此申请状态错误，无法编辑' , 5);
            }
            if ($res['update_time'] != $_POST['update_time']) {
                return $this->actionError('此申请信息已被修改' , 5);
            }
            if (empty($_POST['company_name'])) {
                return $this->actionError('请输入企业全称' , 5);
            }
            if (empty($_POST['credentials_no'])) {
                return $this->actionError('请输入企业证件号码' , 5);
            }
            if (empty($_POST['legalbody_name'])) {
                return $this->actionError('请输入法定代表人姓名' , 5);
            }
            if (empty($_POST['legalbody_credentials_no'])) {
                return $this->actionError('请输入法定代表人证件号码' , 5);
            }
            if (empty($_POST['legalbody_mobile'])) {
                return $this->actionError('请输入企业联系电话(手机号)' , 5);
            }
            if (empty($_POST['registration_address'])) {
                return $this->actionError('请输入企业注册地址' , 5);
            }
            if (empty($_POST['contract_address'])) {
                return $this->actionError('请输入企业联系地址' , 5);
            }
            $company_name             = trim($_POST['company_name']);
            $credentials_no           = trim($_POST['credentials_no']);
            $legalbody_name           = trim($_POST['legalbody_name']);
            $legalbody_credentials_no = trim($_POST['legalbody_credentials_no']);
            $legalbody_mobile         = trim($_POST['legalbody_mobile']);
            $registration_address     = trim($_POST['registration_address']);
            $contract_address         = trim($_POST['contract_address']);
            $enterprise_id            = intval($_POST['enterprise_id']);
            $user_id                  = intval($_POST['user_id']);

            // 检验企业全称
            $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.company_name = '{$company_name}' AND user.is_effect = 1 AND user.is_delete = 0 ";
            $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_company_name && !empty($check_company_name['yj_fdd_customer_id'])) {
                return $this->actionError('此企业全称已经存在' , 5);
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE company_name = '{$company_name}' AND status = 1 AND id != {$id} ";
            $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_company_name) {
                return $this->actionError('此企业全称已在申请中并等待审核' , 5);
            }
            // 检验企业证件号码
            $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.credentials_no = '{$credentials_no}' AND user.is_effect = 1 AND user.is_delete = 0 ";
            $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_credentials_no && !empty($check_credentials_no['yj_fdd_customer_id'])) {
                return $this->actionError('此企业证件号码已经存在' , 5);
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE credentials_no = '{$credentials_no}' AND status = 1 AND id != {$id} ";
            $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_credentials_no) {
                return $this->actionError('此企业证件号码已在申请中并等待审核' , 5);
            }
            // 检验法定代表人手机号码
            $mobile = GibberishAESUtil::enc($legalbody_mobile , Yii::app()->c->idno_key);
            $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
            $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_legalbody_mobile) {
                return $this->actionError('此企业联系电话(手机号)已经存在' , 5);
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE legalbody_mobile = '{$legalbody_mobile}' AND status = 1 AND id != {$id} ";
            $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_legalbody_mobile) {
                return $this->actionError('此企业联系电话(手机号)已在申请中并等待审核' , 5);
            }
            // 校验统一社会信用代码电子版
            $credit_code_file = $this->upload('credit_code_file');
            if ($credit_code_file['code'] === 0) {
                $sql_credit_code_file = " , credit_code_file = '{$credit_code_file['data']}' ";
            } else {
                $sql_credit_code_file = '';
            }
            // 校验授权委托书电子版
            $power_attorney_file = $this->upload('power_attorney_file');
            if ($power_attorney_file['code'] === 0) {
                $sql_power_attorney_file = " , power_attorney_file = '{$power_attorney_file['data']}' ";
            } else {
                $sql_power_attorney_file = '';
            }

            $fdb         = Yii::app()->fdb;
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE xf_add_fdd_enterprise SET status = 1 , add_user_id = {$add_user_id} , add_ip = '{$add_ip}' , update_time = {$time} , company_name = '{$company_name}' , credentials_no = '{$credentials_no}' , legalbody_name = '{$legalbody_name}' , legalbody_credentials_no = '{$legalbody_credentials_no}' , legalbody_mobile = '{$legalbody_mobile}' , registration_address = '{$registration_address}' , contract_address = '{$contract_address}' , enterprise_id = {$enterprise_id} , user_id = {$user_id} {$sql_credit_code_file} {$sql_power_attorney_file} WHERE id = {$id} ";
            $result = $fdb->createCommand($sql)->execute();

            if (!$result) {
                return $this->actionError('保存失败' , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if (!in_array($res['status'] , [1, 2])) {
                return $this->actionError('此申请状态错误，无法编辑' , 5);
            }
            $credit_code_file = explode('/', $res['credit_code_file']);
            if ($credit_code_file[0] == 'upload') {
                $res['credit_code_file_name'] = $credit_code_file[2];
            } else if ($credit_code_file[0] == 'add_fdd_enterprise') {
                $res['credit_code_file_name'] = $credit_code_file[3];
            }
            $power_attorney_file = explode('/', $res['power_attorney_file']);
            if ($power_attorney_file[0] == 'upload') {
                $res['power_attorney_file_name'] = $power_attorney_file[2];
            } else if ($power_attorney_file[0] == 'add_fdd_enterprise') {
                $res['power_attorney_file_name'] = $power_attorney_file[3];
            }
        } else {
            return $this->actionError('请输入申请ID' , 5);
        }

        return $this->renderPartial('EditEnterprise', array('res' => $res));
    }

    public function actionCheckCompanyName()
    {
        if (!empty($_POST['company_name'])) {
            $company_name = trim($_POST['company_name']);
            if (!empty($_POST['id'])) {
                $id     = intval($_POST['id']);
                $sql_id = " AND id != {$id} ";
            } else {
                $sql_id = '';
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE company_name = '{$company_name}' AND status = 1 {$sql_id} ";
            $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_company_name) {
                $this->echoJson(array() , 2 , '此企业全称已在申请中并等待审核');
            }
            $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.company_name = '{$company_name}' AND user.is_effect = 1 AND user.is_delete = 0 ";
            $check_company_name = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_company_name && !empty($check_company_name['yj_fdd_customer_id'])) {
                $this->echoJson(array() , 3 , '此企业全称已经存在');
            }
            if ($check_company_name && empty($check_company_name['yj_fdd_customer_id'])) {
                $this->echoJson($check_company_name , 1 , '已存在');
            }
            $this->echoJson(array() , 0 , '不存在');
        }
    }

    public function actionCheckCredentialsNo()
    {
        if (!empty($_POST['credentials_no'])) {
            $credentials_no = trim($_POST['credentials_no']);
            if (!empty($_POST['id'])) {
                $id     = intval($_POST['id']);
                $sql_id = " AND id != {$id} ";
            } else {
                $sql_id = '';
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE credentials_no = '{$credentials_no}' AND status = 1 {$sql_id} ";
            $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_credentials_no) {
                $this->echoJson(array() , 2 , '此企业证件号码已在申请中并等待审核');
            }
            $sql = "SELECT enterprise.* , user.yj_fdd_customer_id FROM firstp2p_enterprise AS enterprise INNER JOIN firstp2p_user AS user ON enterprise.user_id = user.id WHERE enterprise.credentials_no = '{$credentials_no}' AND user.is_effect = 1 AND user.is_delete = 0 ";
            $check_credentials_no = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_credentials_no && !empty($check_credentials_no['yj_fdd_customer_id'])) {
                $this->echoJson(array() , 3 , '此企业证件号码已经存在');
            }
            if ($check_credentials_no && empty($check_credentials_no['yj_fdd_customer_id'])) {
                $this->echoJson($check_credentials_no , 1 , '已存在');
            }
            $this->echoJson(array() , 0 , '不存在');
        }
    }

    public function actionCheckLegalbodyMobile()
    {
        if (!empty($_POST['legalbody_mobile'])) {
            $legalbody_mobile = trim($_POST['legalbody_mobile']);
            if (!empty($_POST['id'])) {
                $id     = intval($_POST['id']);
                $sql_id = " AND id != {$id} ";
            } else {
                $sql_id = '';
            }
            $sql = "SELECT * FROM xf_add_fdd_enterprise WHERE legalbody_mobile = '{$legalbody_mobile}' AND status = 1 {$sql_id} ";
            $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_legalbody_mobile) {
                $this->echoJson(array() , 2 , '此企业联系电话(手机号)已在申请中并等待审核');
            }
            $mobile = GibberishAESUtil::enc($legalbody_mobile , Yii::app()->c->idno_key);
            $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
            $check_legalbody_mobile = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check_legalbody_mobile) {
                $this->echoJson(array() , 3 , '此企业联系电话(手机号)已经存在');
            }
            $this->echoJson(array() , 0 , '不存在');
        }
    }
}