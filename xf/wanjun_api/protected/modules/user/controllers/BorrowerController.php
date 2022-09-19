<?php
class BorrowerController extends XianFengExtendsController
{
   
    /**
     * 上传图片
     * @param content   string  图片的base64内容
     * @return array
     */
    private function upload_base64($content)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)) {
            $pic_type    = $result[2]; // 匹配出图片后缀名
            $dir_address = "uploads/";
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            $pic_name    = time() . rand(10000, 99999) . ".{$pic_type}";
            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address, base64_decode(str_replace($result[1], '', $content)))) {
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
     * 还款凭证 - 详情
     */
    public function actionRepayVoucherInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sex_cn = ['0'=>'女士','1'=>'先生'];
        $sql       = "SELECT real_name,sex FROM firstp2p_user WHERE id = {$user_id} ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        $result['real_name']= $user_info['real_name'];
        $result['sex']= $sex_cn[$user_info['sex']]?:'女士/先生';
        $result['hello']= '您好';
        $sql = "SELECT * FROM xf_borrower_repay_proof WHERE  user_id = {$user_id} order by id desc ";
        $audit = Yii::app()->phdb->createCommand($sql)->queryRow();
       
        if ($audit) {
            $pic_address       = json_decode($audit['proof_photograph'], true);
            $result['picture'] = array();
            foreach ($pic_address as $key => $value) {
                $result['picture'][] = Yii::app()->c->oss_preview_address.$value;
            }
        } else {
            $result['picture'] = array();
        }
        $this->echoJson($result, 0, $XF_error_code_info[0]);
    }

    /**
     * 还款凭证 - 上传
     */
    public function actionRepayVoucherUpload()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        
        if (count($_POST['picture']) > 24) {
            $this->echoJson(array(), 1108, $XF_error_code_info[1108]);
        }
       
        $pic_address = array();
        foreach ($_POST['picture'] as $key => $value) {
            $temp = $this->upload_base64(trim($value));
            if ($temp) {
                $pic_address[] = $temp;
            } else {
                $this->echoJson(array(), 1104, $XF_error_code_info[1104]);
            }
        }
        $pic_oss_address = array();
        foreach ($pic_address as $key => $value) {
            $temp = $this->upload_oss('./'.$value['pic_address'], 'repay_voucher/'.$value['pic_name']);
            if ($temp === false) {
                $this->echoJson(array(), 1105, $XF_error_code_info[1105]);
            } else {
                $pic_oss_address[] = '/repay_voucher/'.$value['pic_name'];
            }
        }
        $time = time();
        $sql = "SELECT * FROM xf_borrower_repay_proof WHERE  user_id = {$user_id}  ";
        $audit = Yii::app()->phdb->createCommand($sql)->queryRow();
       
        $pic_address_json = json_encode($pic_oss_address);

        if ($audit) {
            $sql = "UPDATE xf_borrower_repay_proof SET  proof_photograph = '{$pic_address_json}' WHERE user_id = {$user_id}  ";
        } else {
            $sql = "INSERT INTO xf_borrower_repay_proof ( user_id ,  add_time , proof_photograph) VALUES ( {$user_id} ,  {$time}, '{$pic_address_json}' ) ";
        }
        $result = Yii::app()->phdb->createCommand($sql)->execute();

        if ($result) {
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
        } else {
            $this->echoJson(array(), 1102, $XF_error_code_info[1102]);
        }
    }

    /**
     * 借款人证件号登录
     */
    public function actionCardLogin()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $time = time();
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        // 校验手机号
        if (empty($_POST['number'])) {
            $this->echoJson(array(), 1001, $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        
        // 校验验证码
        if (empty($_POST['code'])) {
            $this->echoJson(array(), 1010, $XF_error_code_info[1010]);
        }
        $code       = trim($_POST['code']);

        $captchaCheck  = new CaptchaCheck();
        $result = $captchaCheck->ValidCaptcha($code, true);//验证并销毁验证码
        if ($result['code']) {
            $this->echoJson(array(), $result['code'], $XF_error_code_info[$result['code']]);
        }

        $card_number    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT id AS user_id , is_online FROM firstp2p_user WHERE idno = '{$card_number}' AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1014, $XF_error_code_info[1014]);
        }
        $data = [];
       
        $sql       = "SELECT * FROM xf_yr_borrower WHERE user_id = {$user_info['user_id']} ";
        $borrower_info = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$borrower_info) {
            $this->echoJson($data, 2008, $XF_error_code_info[2008]);
        }
        $token = JwtClass::getToken($user_info);
        $data['token'] = $token;

        $this->echoJson($data, 0, $XF_error_code_info[0]);
    }
}
