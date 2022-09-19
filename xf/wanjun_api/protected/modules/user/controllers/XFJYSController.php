<?php
class XFJYSController extends XianFengExtendsController
{
    /**
     * 首页
     */
    public function actionindex()
    {
        echo '欢迎使用<br>123';
    }

    /**
     * 上传图片
     * @param content   string  图片的base64内容
     * @return array
     */
    private function upload_base64($content)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/' , $content , $result)) {
            $pic_type    = $result[2]; // 匹配出图片后缀名
            $dir_address = "uploads/";
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            $pic_name    = time() . rand(10000 , 99999) . ".{$pic_type}";
            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address , base64_decode(str_replace($result[1] , '' , $content)))) {

                return array('pic_address' => $pic_address , 'pic_name' => $pic_name);
            } else {
                return false;
            }
        } else {
            return false;
        }
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
     * 交易所用户信息审核 - 校验用户信息
     */
    public function actionCheckUserInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 校验姓名
        $real_name = trim($_POST['name']);
        if (empty($real_name)) {
            $this->echoJson(array() , 1109 , $XF_error_code_info[1109]);
        }
        // 校验身份证号
        $id_number = trim($_POST['id_number']);
        if (empty($id_number)) {
            $this->echoJson(array() , 1110 , $XF_error_code_info[1110]);
        }
        $idno = GibberishAESUtil::enc($id_number, Yii::app()->c->idno_key);
        $sql = "SELECT * FROM firstp2p_user WHERE idno = '{$idno}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if ($user_info) {
            $this->echoJson(array() , 1111 , $XF_error_code_info[1111]);
        }
        $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE real_name = '{$real_name}' AND idno = '{$idno}' AND status = 1 AND type = 4 ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array() , 1112 , $XF_error_code_info[1112]);
        }
        $this->echoJson(array() , 0 , $XF_error_code_info[0]);
    }

    /**
     * 交易所用户信息审核 - 提交申请
     */
    public function actionAddJYSUserInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 校验姓名
        $real_name = trim($_POST['name']);
        if (empty($real_name)) {
            $this->echoJson(array() , 1109 , $XF_error_code_info[1109]);
        }
        // 校验身份证号
        $id_number = trim($_POST['id_number']);
        if (empty($id_number)) {
            $this->echoJson(array() , 1110 , $XF_error_code_info[1110]);
        }
        $idno = GibberishAESUtil::enc($id_number, Yii::app()->c->idno_key);
        $sql = "SELECT * FROM firstp2p_user WHERE idno = '{$idno}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if ($user_info) {
            $this->echoJson(array() , 1111 , $XF_error_code_info[1111]);
        }
        $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE real_name = '{$real_name}' AND idno = '{$idno}' AND status = 1 AND type = 4 ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array() , 1112 , $XF_error_code_info[1112]);
        }
        // 校验新手机号
        $new_mobile = trim($_POST['new_mobile']);
        if (empty($new_mobile)) {
            $this->echoJson(array() , 1070 , $XF_error_code_info[1070]);
        }
        $check_number = preg_match('/^1[3-9]\d{9}$/' , $new_mobile);
        if ($check_number === 0) {
            $this->echoJson(array() , 1078 , $XF_error_code_info[1078]);
        }
        $new_mobile_str = GibberishAESUtil::enc($new_mobile, Yii::app()->c->idno_key);
        // 校验新手机号是否被使用
        $sql        = "SELECT * FROM firstp2p_user WHERE mobile = '{$new_mobile_str}' AND is_effect = 1 AND is_delete = 0 ";
        $check_user = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check_user) {
            $this->echoJson(array() , 1082 , $XF_error_code_info[1082]);
        }
        // 校验新手机号是否在审核中
        $sql   = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$new_mobile_str}' AND status = 1 ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array() , 1086 , $XF_error_code_info[1086]);
        }
        // 校验验证码
        $code = trim($_POST['new_mobile_code']);
        if (empty($code)) {
            $this->echoJson(array() , 1071 , $XF_error_code_info[1071]);
        }
        $check_code = preg_match('/^\d{6}$/' , $code);
        if ($check_code === 0) {
            $this->echoJson(array() , 1079 , $XF_error_code_info[1079]);
        }
        $redis     = Yii::app()->rcache;
        $redis_key = "add_jys_user_info_get_SMS_{$new_mobile}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array() , 1012 , $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array() , 1083 , $XF_error_code_info[1083]);
        }
        // 校验4张照片
        $id_pic_front   = trim($_POST['id_pic_front']);
        $id_pic_back    = trim($_POST['id_pic_back']);
        $user_pic_front = trim($_POST['user_pic_front']);
        $user_pic_back  = trim($_POST['user_pic_back']);
        $contract_pic   = trim($_POST['contract_pic']);
        $evidence_pic   = trim($_POST['evidence_pic']);
        if (empty($id_pic_front)) {
            $this->echoJson(array() , 1072 , $XF_error_code_info[1072]);
        }
        if (empty($user_pic_front)) {
            $this->echoJson(array() , 1074 , $XF_error_code_info[1074]);
        }
        if (empty($contract_pic)) {
            $this->echoJson(array() , 1101 , $XF_error_code_info[1101]);
        }
        if (empty($evidence_pic)) {
            $this->echoJson(array() , 1114 , $XF_error_code_info[1114]);
        }
        $id_pic_front_res = $this->upload_base64($id_pic_front);
        if ($id_pic_back) {
            $id_pic_back_res = $this->upload_base64($id_pic_back);
        } else {
            $id_pic_back_res = false;
        }
        $user_pic_front_res = $this->upload_base64($user_pic_front);
        if ($user_pic_back) {
            $user_pic_back_res = $this->upload_base64($user_pic_back);
        } else {
            $user_pic_back_res = false;
        }
        $contract_pic_res = $this->upload_base64($contract_pic);
        $evidence_pic_res = $this->upload_base64($evidence_pic);
        if (!$id_pic_front_res) {
            $this->echoJson(array() , 1087 , $XF_error_code_info[1087]);
        }
        if (!$id_pic_back_res && $id_pic_back) {
            $this->echoJson(array() , 1088 , $XF_error_code_info[1088]);
        }
        if (!$user_pic_front_res) {
            $this->echoJson(array() , 1089 , $XF_error_code_info[1089]);
        }
        if (!$user_pic_back_res && $user_pic_back) {
            $this->echoJson(array() , 1090 , $XF_error_code_info[1090]);
        }
        if (!$contract_pic_res) {
            $this->echoJson(array() , 1104 , $XF_error_code_info[1104]);
        }
        if (!$evidence_pic_res) {
            $this->echoJson(array() , 1115 , $XF_error_code_info[1115]);
        }
        $id_pic_front_oss = $this->upload_oss('./'.$id_pic_front_res['pic_address'] , 'add_jys_user_info/'.$id_pic_front_res['pic_name']);
        if ($id_pic_back_res) {
            $id_pic_back_oss = $this->upload_oss('./'.$id_pic_back_res['pic_address'] , 'add_jys_user_info/'.$id_pic_back_res['pic_name']);
        } else {
            $id_pic_back_oss = false;
        }
        $user_pic_front_oss = $this->upload_oss('./'.$user_pic_front_res['pic_address'] , 'add_jys_user_info/'.$user_pic_front_res['pic_name']);
        if ($user_pic_back_res) {
            $user_pic_back_oss = $this->upload_oss('./'.$user_pic_back_res['pic_address'] , 'add_jys_user_info/'.$user_pic_back_res['pic_name']);
        } else {
            $user_pic_back_oss = false;
        }
        $contract_pic_oss = $this->upload_oss('./'.$contract_pic_res['pic_address'] , 'add_jys_user_info/'.$contract_pic_res['pic_name']);
        $evidence_pic_oss = $this->upload_oss('./'.$evidence_pic_res['pic_address'] , 'add_jys_user_info/'.$evidence_pic_res['pic_name']);
        if ($id_pic_front_oss === false) {
            $this->echoJson(array() , 1091 , $XF_error_code_info[1091]);
        }
        if ($id_pic_back_oss === false && $id_pic_back) {
            $this->echoJson(array() , 1092 , $XF_error_code_info[1092]);
        }
        if ($user_pic_front_oss === false) {
            $this->echoJson(array() , 1093 , $XF_error_code_info[1093]);
        }
        if ($user_pic_back_oss === false && $user_pic_back) {
            $this->echoJson(array() , 1094 , $XF_error_code_info[1094]);
        }
        if ($contract_pic_oss === false) {
            $this->echoJson(array() , 1105 , $XF_error_code_info[1105]);
        }
        if ($evidence_pic_oss === false) {
            $this->echoJson(array() , 1116 , $XF_error_code_info[1116]);
        }
        if ($id_pic_back_oss !== false) {
            $id_pic_back_sql = "/add_jys_user_info/{$id_pic_back_res['pic_name']}";
        } else {
            $id_pic_back_sql = '';
        }
        if ($user_pic_back_oss !== false) {
            $user_pic_back_sql = "/add_jys_user_info/{$user_pic_back_res['pic_name']}";
        } else {
            $user_pic_back_sql = '';
        }
        $time = time();
        $sql = "INSERT INTO xf_user_mobile_edit_log (user_id , real_name , idno , old_mobile , new_mobile , status , type , id_pic_front , id_pic_back , user_pic_front , user_pic_back , add_time , contract_pic , evidence_pic) VALUES (0 , '{$real_name}' , '{$idno}' , '' , '{$new_mobile_str}' , 1 , 4 , '/add_jys_user_info/{$id_pic_front_res['pic_name']}' , '{$id_pic_back_sql}' , '/add_jys_user_info/{$user_pic_front_res['pic_name']}' , '{$user_pic_back_sql}' , {$time} , '/add_jys_user_info/{$contract_pic_res['pic_name']}' , '/add_jys_user_info/{$evidence_pic_res['pic_name']}') ";
        $result = Yii::app()->db->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson(array() , 1113 , $XF_error_code_info[1113]);
        }
        $this->echoJson(array() , 0 , $XF_error_code_info[0]);
    }

}