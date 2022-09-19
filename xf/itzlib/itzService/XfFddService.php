<?php
/**
 * 法大大合同电签服务
 */
class XfFddService extends ItzInstanceService {
 
    private $card_types = [
        1=>"0",//身份证
        2=>"2",//军人身份证
        3=>"C",//台湾居民来往大陆通行证
        4=>"1",//护照
        //5=>'',//营业执照
        6=>"P",//外国人永久居留证
    ];

    public function __construct()
    {
        parent::__construct();
        $this->config = array(
            'app_id'      => ConfUtil::get('xf_fdd_app_id')
            ,'timestamp'  => date('YmdHis' , time())
            ,'v'          => '2.0'
        );
        $this->app_secret = ConfUtil::get('xf_fdd_app_secret');
        $this->fdd_url    = ConfUtil::get('xf_fdd_url');

        #提测试注释
        //$this->return_url    = 'http://qa1.xfuser.com/debt/#/authSign';
        //$this->return_url01    = 'http://qa1api.xfuser.com/user/XFUser/FddApi';
       // $this->center_url = 'http://qa1.xfuser.com/#/claims/complete';//置换合同签署成功页
        $this->return_url01    = 'https://api.xfuser.com/user/XFUser/FddApi';
        $this->return_url    = 'https://m.xfuser.com/debt/#/authSign';
        $this->center_url    = 'https://m.xfuser.com/#/claims/complete';

        // Yii::log("XfFddService: app_id:".print_r($this->config, true), "info");
        // Yii::log("XfFddService: app_secret:".print_r($this->app_secret, true), "info");
        // Yii::log("XfFddService: fdd_url:".print_r($this->fdd_url, true), "info");
    }

    /**
     * 生成消息摘要
     */
    private function make_msg_digest($set_md5, $set_sha1, $other = '')
    {
        $app_id   = $this->config['app_id'];
        $set_md5  = strtoupper(md5($set_md5));
        $set_sha1 = strtoupper(sha1($set_sha1));
        $string   = base64_encode(strtoupper(sha1($app_id . $set_md5 . $set_sha1 . $other)));
        Yii::log("XfFddService: make_msg_digest:".print_r($string, true), "info");

        return $string;
    }

    /**
     * 注册账号 生成客户编号
     */
    private function account_register($open_id , $account_type)
    {
        $url                    = $this->fdd_url . 'account_register.api';
        $params                 = $this->config;
        $params['open_id']      = $open_id;
        $params['account_type'] = $account_type;
        $set_md5                = $this->config['timestamp'];
        $set_sha1               = $this->app_secret . $params['account_type'] . $open_id;
        $params['msg_digest']   = $this->make_msg_digest($set_md5, $set_sha1);
        $result                 = $this->post($url, $params);
        Yii::log("XfFddService: account_register:".print_r($result, true), "info");

        if ($result['msg'] == 'success') {
            return $result['data'];
        } else {
            return '';
        }
    }

    /**
     * 个人实名信息存证 生成存证编号
     */
    private function person_deposit($customer_id, $preservation_name, $preservation_data_provider, $name, $document_type, $idcard, $mobile, $transactionId)
    {
        $url                                  = $this->fdd_url . 'person_deposit.api';
        $params                               = $this->config;
        $params['customer_id']                = $customer_id;
        $params['preservation_data_provider'] = $preservation_data_provider;
        $params['preservation_name']          = $preservation_name;
        $params['name']                       = $name;
        $params['idcard']                     = $idcard;
        $params['document_type']              = $document_type;
        $params['verified_type']              = '2';
        $params['mobile']                     = $mobile;
        $params['mobile_essential_factor']    = json_encode(array('transactionId' => $transactionId));
        $params['cert_flag']                  = '1';
        $set_md5                              = $this->config['timestamp'];
        $set_sha1 = $this->app_secret . $params['cert_flag'] . $customer_id . $document_type . $idcard . $mobile . $params['mobile_essential_factor'] . $name . $preservation_data_provider . $preservation_name . $params['verified_type'];
        $params['msg_digest']                 = $this->make_msg_digest($set_md5, $set_sha1);
        $result                               = $this->post($url, $params);
        Yii::log("XfFddService: person_deposit:".print_r($result, true), "info");

        if ($result['msg'] == 'success') {
            return $result['data'];
        } else {
            return '';
        }
    }

    /**
     * 生成个人签章
     */
    private function custom_signature($customer_id, $content)
    {
        $url                   = $this->fdd_url . 'custom_signature.api';
        $params                = $this->config;
        $params['customer_id'] = $customer_id;
        $params['content']     = $content;
        $set_md5               = $this->config['timestamp'];
        $set_sha1              = $this->app_secret . $content . $customer_id;
        $params['msg_digest']  = $this->make_msg_digest($set_md5, $set_sha1);
        $result                = $this->post($url, $params);
        Yii::log("XfFddService: custom_signature:".print_r($result, true), "info");

        if ($result['msg'] == 'success') {
            return $result['data']['signature_img_base64'];
        } else {
            return '';
        }
    }

    /**
     * 调用个人ca注册
     * @param string $customer_name 用户姓名
     * @param string $user_id       用户ID
     * @param string $id_card       证件号码
     * @param string $ident_type    证件类型
     * @param string $mobile        手机号
     * @return array|string
     */
    public function invokeSyncPersonAuto($customer_name, $user_id, $id_card, $ident_type, $mobile)
    {
        $card_types = $this->card_types;
        if(!isset($card_types[$ident_type])){
            return array("msg" => "failed", "data" => "证件类型异常", "code" => 0);
        }
        $document_type = $card_types[$ident_type];

        // 注册账号 生成客户编号
        $customer_id = $this->account_register($user_id , 1);
        if ($customer_id == '') {
            return array("msg" => "failed", "data" => "客户编号生成失败", "code" => 0);
        }

        // 个人实名信息存证 生成存证编号
        $preservation_data_provider = '有解'; // 存证提供方
        $transactionId              = date('YmdHis' , time());
        $evidence_no = $this->person_deposit($customer_id, $customer_name, $preservation_data_provider, $customer_name, $document_type, $id_card, $mobile, $transactionId);
        if ($evidence_no == '') {
            return array("msg" => "failed", "data" => "存证编号生成失败", "code" => 0);
        }

        // 生成个人签章
        $signature_img_base64 = $this->custom_signature($customer_id, $customer_name);
        if ($signature_img_base64 == '') {
            return array("msg" => "failed", "data" => "个人签章生成失败", "code" => 0);
        }

        Yii::log("XfFddService: invokeSyncPersonAuto:".print_r($customer_id, true).' '.print_r($evidence_no, true).' '.print_r($signature_img_base64, true), "info");

        return array(
            "msg"                  => "success",
            "code"                 => "1",
            "customer_id"          => $customer_id,
            "evidence_no"          => $evidence_no,
            "signature_img_base64" => $signature_img_base64
        );
    }

    /**
     * 调用生成合同接口
     * @param string $template_id    合同模板编号
     * @param string $contract_id    合同编号
     * @param string $doc_title      签署文档标题
     * @param string $font_size      字体大小 不传则为默认值9
     * @param string $font_type      字体类型 0-宋体；1-仿宋；2-黑体；3-楷体；4-微软雅黑
     * @param string $parameter_map  填充内容
     * @param string $dynamic_tables 动态表单
     * @return array
     */
    public function invokeGenerateContract($template_id, $doc_title, $parameter_map, $dynamic_tables = '')
    {
        $url                   = $this->fdd_url . 'generate_contract.api';
        $params                = $this->config;
        $contract_id = str_replace('.', '', uniqid('' , true));
        if (!empty($dynamic_tables)) {
            $params['dynamic_tables'] = json_encode($dynamic_tables);
        }
        $params['contract_id']   = $contract_id;
        $params['template_id']   = $template_id;
        $params['doc_title']     = $doc_title;
        $params['parameter_map'] = json_encode($parameter_map);
        $set_md5                 = $this->config['timestamp'];
        $set_sha1                = $this->app_secret . $template_id . $contract_id;
        $params['msg_digest']    = $this->make_msg_digest($set_md5, $set_sha1, $params['parameter_map']);
        $result                  = $this->post($url, $params);
        Yii::log("XfFddService: invokeGenerateContract:".print_r($result, true), "info");

        $result['contract_id'] = $contract_id;
        return $result;
    }

    /**
     * 调用签署接口（自动签模式） - 关键字定位
     * @param string $transaction_id   交易号
     * @param string $customer_id      客户编号
     * @param string $client_role      客户角色
     * @param string $contract_id      合同编号
     * @param string $doc_title        文档标题
     * @param string $sign_keyword     定位关键字
     * @param string $keyword_strategy 签章策略
     * @param string $notify_url       异步通知地址
     * @return array
     */
    public function invokeExtSignAuto($customer_id, $contract_id, $doc_title, $sign_keyword)
    {
        $url                      = $this->fdd_url . 'extsign_auto.api';
        $params                   = $this->config;
        $transaction_id           = str_replace('.', '', uniqid('' , true));
        $timestamp                = date('YmdHis' , time());
        $params['transaction_id'] = $transaction_id;
        $params['contract_id']    = $contract_id;
        $params['customer_id']    = $customer_id;
        $params['doc_title']      = $doc_title;
        $params['client_role']    = '1';
        $params['timestamp']      = $timestamp;
        $params['sign_keyword']   = $sign_keyword;
        $set_md5                  = $transaction_id . $timestamp;
        $set_sha1                 = $this->app_secret . $customer_id;
        $params['msg_digest']     = $this->make_msg_digest($set_md5, $set_sha1);
        $result                   = $this->post($url, $params);
        Yii::log("XfFddService: invokeExtSignAuto:".print_r($result, true), "info");

        return $result;
    }

    /**
     * 加水印
     * @param data[
     * @param contract_id 合同编号
     * @param stamp_type 水印类型 1-文字，2-图片
     * @param text_name 文字名称 当水印类型为 1 时，不为空，不超过 100 位
     * @param font_size 文字大小 当水印类型为 1 时，不为空；不能超过 72
     * @param picbase64 当水印类型为 2 时，不为空
     * @param rotate 水印旋转的角度（正角度 为逆时针，负角度为顺时 针），默认不旋转
     * @param concentration_factor 水印的密集度，PDF 横 向和纵向水印的个数，默 认为 1
     * @param opacity 透明度，默认为 0.5  范围：0-1
     * ]
     * @return mixed
     */
    public function watermarkPdf($data)
    {
        // 参数校验
        if(empty($data) || !is_array($data)){
            return false;
        }
        Yii::log("XfFddService: watermarkPdf:".print_r($data, true), "info");
        $url                  = $this->fdd_url . 'watermark_pdf.api';
        $contract_id          = $data['contract_id'];
        $stamp_type           = $data['stamp_type'];
        $text_name            = !empty($data['text_name']) ? $data['text_name'] : '';
        $font_size            = !empty($data['font_size']) ? $data['font_size'] : '10';
        $pickbase64           = !empty($data['picbase64']) ? $data['picbase64'] : '';
        $rotate               = !empty($data['rotate']) ? $data['rotate'] : '0';
        $concentration_factor = !empty($data['concentration_factor']) ? $data['concentration_factor'] : '1';
        $opacity              = !empty($data['opacity']) ? $data['opacity'] : '0.5';

        $params                         = $this->config;
        $params['contract_id']          = $contract_id;
        $params['stamp_type']           = $stamp_type;
        $params['text_name']            = $text_name;
        $params['font_size']            = $font_size;
        $params['pickbase64']           = $pickbase64;
        $params['rotate']               = $rotate;
        $params['concentration_factor'] = $concentration_factor;
        $params['opacity']              = $opacity;
        $set_md5                        = $this->config['timestamp'];
        $set_sha1                       = $this->app_secret . $concentration_factor . $contract_id . $font_size . $opacity . $pickbase64 . $rotate . $stamp_type . $text_name;
        $params['msg_digest']           = $this->make_msg_digest($set_md5, $set_sha1);
        $result                         = $this->post($url, $params);
        Yii::log("XfFddService: watermarkPdf:".print_r($result, true), "info");

        return $result;
    }

    private function post($url, $body)
    {
        return $this->request('post', $url, $body);
    }

    private function get($url)
    {
        return $this->request('get', $url);
    }

    //请求
    private function request($methord = 'get', $url = '', $body = '')
    {
        // 1.初始化
        $curl = curl_init();
        // 2.设置属性
        curl_setopt($curl, CURLOPT_URL, $url);          // 需要获取的 URL 地址
        curl_setopt($curl, CURLOPT_HEADER, 0);          // 设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 要求结果为字符串且输出到屏幕上

        // Set headers
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers); // 设置 HTTP 头字段的数组
        //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        switch ($methord) {
            case 'get':
                break;
            case 'post':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'delete':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'patch':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'put':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            default:

        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        //curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        //curl_setopt($curl, CURLOPT_NOSIGNAL, true);
        // 3.执行并获取结果
        $res = curl_exec($curl);
        // 4.释放句柄
        curl_close($curl);
        Yii::log($methord . ':' . $url . ' body:' . print_r($body, true) . ' return:' . json_encode($res, JSON_UNESCAPED_UNICODE));
        return json_decode($res, true)?:$res;
    }

    /**
     * 企业信息实名存证 生成存证编号
     */
    private function company_deposit($data)
    {
        $url                                     = $this->fdd_url . 'company_deposit.api';
        $param                                   = $this->config;
        $param['cert_flag']                      = $data['cert_flag'];
        $param['company_name']                   = $data['company_name'];
        $param['company_principal_type']         = $data['company_principal_type'];
        $param['company_principal_verified_msg'] = $data['company_principal_verified_msg'];
        $param['credit_code']                    = $data['credit_code'];
        $param['credit_code_file']               = $data['credit_code_file'];
        $param['customer_id']                    = $data['customer_id'];
        $param['document_type']                  = $data['document_type'];
        $param['power_attorney_file']            = $data['power_attorney_file'];
        $param['preservation_data_provider']     = $data['preservation_data_provider'];
        $param['preservation_name']              = $data['preservation_name'];
        $param['transaction_id']                 = $data['transaction_id'];
        $param['verified_mode']                  = $data['verified_mode'];

        $set_md5  = $this->config['timestamp'];
        $set_sha1 = $this->app_secret . $param['cert_flag'] . $param['company_name'] . $param['company_principal_type'] . $param['company_principal_verified_msg'] . $param['credit_code'] . $param['customer_id'] . $param['document_type'] . $param['preservation_data_provider'] . $param['preservation_name'] . $param['transaction_id'] . $param['verified_mode'];
        $param['msg_digest'] = $this->make_msg_digest($set_md5, $set_sha1);
        $result = $this->post($url, $param);
        Yii::log("XfFddService: company_deposit:".print_r($result, true), "info");

        if ($result['msg'] == 'success') {
            return $result['data'];
        } else {
            return '';
        }
    }

    /**
     * 自动获取企业CA证书
     * @param string $user_id                  firstp2p_user.id
     * @param string $company_name             firstp2p_enterprise.company_name
     * @param string $credentials_no           firstp2p_enterprise.credentials_no
     * @param string $legalbody_name           firstp2p_enterprise.legalbody_name
     * @param string $legalbody_credentials_no firstp2p_enterprise.legalbody_credentials_no
     * @param string $legalbody_mobile         firstp2p_enterprise.legalbody_mobile
     * @param string $credit_code_file         统一社会信用代码电子版绝对地址
     * @param string $power_attorney_file      授权委托书电子版绝对地址
     * @return array|string
     */
    public function auto_apply_client_numcert($user_id , $company_name , $credentials_no , $legalbody_name , $legalbody_credentials_no , $legalbody_mobile , $credit_code_file , $power_attorney_file)
    {
        // 注册账号 生成客户编号
        $customer_id = $this->account_register($user_id , 2);
        if ($customer_id == '') {
            return array("msg" => "failed", "data" => "客户编号生成失败", "code" => 0);
        }

        // 企业信息实名存证 生成存证编号
        $transactionId  = date('YmdHis' , time()).rand(10000 , 99999); // 法人存证交易号
        $transaction_id = date('YmdHis' , time()).rand(10000 , 99999); // 企业存证交易号
        $param                                   = array();
        $param['customer_id']                    = $customer_id;
        $param['preservation_name']              = $company_name;
        $param['preservation_data_provider']     = '有解'; // 存证数据提供方
        $param['company_name']                   = $company_name;
        $param['document_type']                  = '1'; // 证件类型 1:三证合一
        $param['credit_code']                    = $credentials_no;
        $param['credit_code_file']               = '@'.$credit_code_file;
        $param['verified_mode']                  = '1'; // 实名认证方式 1:授权委托书
        $param['power_attorney_file']            = '@'.$power_attorney_file;
        $param['company_principal_type']         = '1'; // 企业负责人身份:1.法人
        $param['company_principal_verified_msg'] = json_encode(array(
                                                        'customer_id'                => $customer_id,
                                                        'preservation_name'          => $legalbody_name,
                                                        'preservation_data_provider' => '有解',
                                                        'name'                       => $legalbody_name,
                                                        'idcard'                     => $legalbody_credentials_no,
                                                        'verified_type'              => '2',
                                                        'mobile'                     => $legalbody_mobile,
                                                        'mobile_essential_factor'    => array(
                                                                                            'transactionId'    => $transactionId,
                                                                                            'verifiedProvider' => '有解'
                                                                                        ),
                                                    )); // json 企业负责人实名存证信息
        $param['transaction_id']                 = $transaction_id;
        $param['cert_flag']                      = '1';
        $evidence_no = $this->company_deposit($param);
        if ($evidence_no == '') {
            return array("msg" => "failed", "data" => "存证编号生成失败", "code" => 0);
        }

        // 生成个人签章
        $signature_img_base64 = $this->custom_signature($customer_id, $company_name);
        if ($signature_img_base64 == '') {
            return array("msg" => "failed", "data" => "个人签章生成失败", "code" => 0);
        }

        Yii::log("XfFddService: auto_apply_client_numcert:".print_r($customer_id, true).' '.print_r($evidence_no, true).' '.print_r($signature_img_base64, true), "info");

        return array(
            "msg"                  => "success",
            "code"                 => "1",
            "customer_id"          => $customer_id,
            "evidence_no"          => $evidence_no,
            "signature_img_base64" => 'data:image/png;base64,'.$signature_img_base64,
            "transactionId"        => $transactionId,
            "transaction_id"       => $transaction_id
        );
    }

    /**
     * 个人信息注册并获取实名认证地址
     * @param string $customer_name 用户姓名
     * @param string $user_id       用户ID
     * @param string $id_card       证件号码
     * @param string $ident_type    证件类型
     * @param string $mobile        手机号
     * @param string $customer_id        法大大客户编号
     * @return array|string
     */
    public function invokeSyncVerifyUrl($customer_name, $user_id, $id_card, $ident_type, $mobile,$bank_card_no,$customer_id=0,$real_src='')
    {
        $card_types = $this->card_types;
        if(!isset($card_types[$ident_type])){
            return array("msg" => "failed", "data" => "证件类型异常", "code" => 0);
        }
        $document_type = $card_types[$ident_type];

        if($customer_id == 0){
            // 注册账号 生成客户编号
            $customer_id = $this->account_register($user_id , 1);
            if ($customer_id == '') {
                return array("msg" => "failed", "data" => "客户编号生成失败", "code" => 0);
            }
        }

        // 个人实名信息存证 生成存证编号
        $verify_url_ret = $this->get_person_verify_url($customer_id,$bank_card_no,$customer_name,$mobile,$id_card,$document_type,$real_src);
        if ($verify_url_ret == '') {
            return array("msg" => "failed", "data" => "获取实名认证地址失败", "code" => 0);
        }

        Yii::log("XfFddService: invokeSyncVerifyUrl:".print_r($verify_url_ret, true) , "info");

        return array(
            "msg" => "success",
            "code" => "1",
            "customer_id" => $customer_id,
            "fdd_real_transaction_no" => $verify_url_ret['transactionNo'],
            "fdd_real_url" => base64_decode($verify_url_ret['url']),
        );
    }

    /**
     * 获取个人实名认证地址
     */
    private function get_person_verify_url($customer_id,$bank_card_no,$name,$mobile,$idcard,$document_type,$real_src='')
    {
        $return_url = $real_src == '' ? $this->return_url : $this->return_url01;

        $url = $this->fdd_url . 'get_person_verify_url.api';
        $params = $this->config;
        $params['customer_id'] = $customer_id;
        $params['bank_card_no'] = $bank_card_no;
        $params['customer_name'] = $name;
        $params['customer_ident_no'] = $idcard;
        $params['mobile'] = $mobile;
        $params['cert_type'] = $document_type;
        $params['verified_way'] = '4';
        $params['page_modify'] = '2';
        $params['return_url'] = $return_url;//同步回调地址
        $params['cert_flag'] = '1';


        /*

        $params['preservation_data_provider'] = $preservation_data_provider;

        $params['name']                       = $name;
        $params['idcard']                     = $idcard;
        $params['document_type']              = $document_type;
        $params['verified_type']              = '2';
        $params['mobile']                     = $mobile;
        $params['mobile_essential_factor']    = json_encode(array('transactionId' => $transactionId));
        $params['cert_flag']                  = '1';
        */

        $set_md5 = $this->config['timestamp'];
        $set_sha1 = $this->app_secret . $params['bank_card_no'] . $params['cert_flag'] . $params['cert_type'] . $customer_id . $params['customer_ident_no'] . $params['customer_name'] . $params['mobile'] . $params['page_modify'] . $params['return_url'] . $params['verified_way'];
        $params['msg_digest'] = $this->make_msg_digest($set_md5, $set_sha1);
        $result = $this->post($url, $params);
        Yii::log("XfFddService: get_person_verify_url:".print_r($result, true), "info");
        if ($result['msg'] == 'success') {
            return $result['data'];
        } else {
            return '';
        }
    }

    public function checkRealSign($transactionNo, $personName, $status, $authenticationType){
        $personName = urlencode($personName);
        $set_sha1 = $this->app_secret . $transactionNo . $personName . $status . $authenticationType ;
        $app_id = $this->config['app_id'];
        $set_sha1 = strtoupper(sha1($set_sha1));
        $sign = base64_encode(strtoupper(sha1($app_id . $set_sha1 )));
        return $sign;
    }

    /**
     * 调用签署接口（手动签模式） - 关键字定位
     * @param string $transaction_id   交易号
     * @param string $customer_id      客户编号
     * @param string $client_role      客户角色
     * @param string $contract_id      合同编号
     * @param string $doc_title        文档标题
     * @param string $sign_keyword     定位关键字
     * @param string $keyword_strategy 签章策略
     * @param string $notify_url       异步通知地址
     * @param string $is_exchange      是否积分兑换
     * @return array
     */
    public function invokeExtSign($customer_id, $contract_id, $doc_title, $sign_keyword,$transaction_id,$is_exchange=0)
    {
        $url                      = $this->fdd_url . 'extsign.api';
        $params                   = $this->config;
        $timestamp                = date('YmdHis' , time());
        $params['transaction_id'] = $transaction_id;
        $params['contract_id']    = $contract_id;
        $params['customer_id']    = $customer_id;
        $params['doc_title']      = $doc_title;
        $params['timestamp']      = $timestamp;
        $params['sign_keyword']   = $sign_keyword;
        if($is_exchange == 1){
            $params['return_url']    = $this->return_url01;
        }else{
            $params['return_url']    = $this->return_url;
        }
        $params['read_time']      = 5;
        $set_md5                  = $transaction_id . $timestamp;
        $set_sha1                 = $this->app_secret . $customer_id;
        $params['msg_digest']     = $this->make_msg_digest($set_md5, $set_sha1);
        //$result                   = $this->post($url, $params);


        $doc_title = urlencode($doc_title);
        $return_url = urlencode($params['return_url']);
        $sign_url = $url."?app_id={$this->config['app_id']}&timestamp=$timestamp&v=2.0&transaction_id=$transaction_id&contract_id=$contract_id&customer_id=$customer_id&doc_title=$doc_title&sign_keyword=$sign_keyword&return_url=$return_url&read_time=5&msg_digest={$params['msg_digest']}";

        Yii::log("XfFddService: invokeExtSign:".$sign_url, "info");
        return $sign_url;
    }


    public function checkContractSign($timestamp, $transaction_id){
        $app_id = $this->config['app_id'];
        $set_sha1 = strtoupper(sha1($this->app_secret . $transaction_id));
        $set_md5  = strtoupper(md5($timestamp));
        //$set_md5 = $this->config['timestamp'];
        $sign = base64_encode(strtoupper(sha1($app_id . $set_md5 . $set_sha1 )));
        return $sign;
    }

    /**
     * 查询个人实名认证信息
     * @param string $verified_serialno 实名认证流水号
     */
    public function findPersonCertInfo($verified_serialno)
    {

        $url = $this->fdd_url . 'find_personCertInfo.api';
        $params = $this->config;
        $params['verified_serialno'] = $verified_serialno;


        $set_md5 = $this->config['timestamp'];
        $set_sha1 = $this->app_secret . $params['verified_serialno'];
        $params['msg_digest'] = $this->make_msg_digest($set_md5, $set_sha1);
        $result = $this->post($url, $params);
        Yii::log("XfFddService: find_personCertInfo:".print_r($result, true), "info");
        if ($result['msg'] == 'success') {
            return $result['data'];
        } else {
            return '';
        }
    }


    public function gotoBatchSemiautoSignPage($batch_id,$batch_title,$sign_data,$customer_id,$customer_mobile )
    {
        $url = $this->fdd_url . 'gotoBatchSemiautoSignPage.api';
        $timestamp = date('YmdHis' , time());
        //$params['customer_mobile'] = $customer_mobile;
        $set_md5 = $batch_id . $timestamp;
        $set_sha1 = $this->app_secret . $customer_id;
        $msg_digest = $this->make_msg_digest($set_md5, $set_sha1);
        $batch_title = urlencode($batch_title);
        $return_url = urlencode($this->center_url);
        $notify_url = urlencode($this->return_url01);
        $sign_data = json_encode($sign_data,256);
        $sign_data = urlencode($sign_data);
        $sign_url = $url."?app_id={$this->config['app_id']}&timestamp=$timestamp&v=2.0&msg_digest=$msg_digest&batch_id=$batch_id&batch_title=$batch_title&sign_data=$sign_data&customer_id=$customer_id&return_url=$return_url&notify_url=$notify_url&customer_mobile=$customer_mobile";
        Yii::log("XfFddService: queryBatchSignUrl:".$sign_url, "info");
        return $sign_url;
    }


}
