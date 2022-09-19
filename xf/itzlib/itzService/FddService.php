<?php
/**
 * 法大大合同电签服务
 */
class FddService extends ItzInstanceService {
    //private $url = 'http://10.0.0.164:5001/'; //开发环境
    //private $url = 'http://10.81.65.186:5001/'; //线上环境
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
        $this->url = Yii::app()->c->contract['fdd_url'];
    }

    /**
     * 调用个人ca注册
     * @param string $customer_name 名称
     * @param string $email         邮箱
     * @param string $id_card       证件号码
     * @param string $ident_type    证件类型
     * @param string $mobile        手机号
     * @return array|string
     */
    public function invokeSyncPersonAuto($customer_name, $email, $id_card, $ident_type, $mobile){
        $api_name = 'syncPerson_auto';
        $url = $this->url . $api_name;

        $params = [];
        $params['customer_name'] = $customer_name;
        $params['id_mobile'] = $id_card . '|' . $mobile;
        if(!empty($ident_type)){
            if(!isset($this->card_types[$ident_type])){
                return '证件类型异常';
            }
            $params['ident_type'] = $this->card_types[$ident_type];
        }
        $res = $this->post($url, $params);
        if($res['code'] == 1000 || $ident_type != 3){
            return $res;
        }
        //港澳通行证尝试
        $params['ident_type'] = 'B';
        return $this->post($url, $params);
    }

    /**
     * 调用文档传输接口
     * @param string $contract_id 合同编号
     * @param string $doc_title   合同标题
     * @param string $file        合同文件,与doc_url两个只传一个
     * @param string $doc_url     合同文件URL（公网）地址
     * @param string $doc_type    合同类型（.pdf）
     * @return array
     */
    public function invokeUploadDocs($contract_id, $doc_title, $file, $doc_url, $doc_type) {
        $api_name = '';
        $url = $this->url . $api_name;

        return $this->post($url);
    }

    /**
     * 调用上传合同模板接口
     * @param string $template_id 模板ID
     * @param string $file        模板文件
     * @param string $doc_url     模板URL
     * @return array
     */
    public function invokeUploadTemplate($template_id, $file, $doc_url) {
        $api_name = '';
        $url = $this->url . $api_name;

        return $this->post($url);
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
    public function invokeGenerateContract($template_id, $doc_title, $parameter_map, $dynamic_tables = '') {
        $api_name = 'generate_contract';
        $url = $this->url . $api_name;

        $params = [];
        $params['template_id'] = $template_id;
        $params['doc_title'] = $doc_title;
        $params['parameter_map'] = json_encode($parameter_map);
        if(!empty($dynamic_tables)){
            $params['dynamic_tables'] = json_encode($dynamic_tables);
        }
        return $this->post($url, $params);
    }

    /**
     * 调用签署接口（手动签模式）
     * @param string $transaction_id 交易号，长度小于等于32位
     * @param string $customer_id    客户编号
     * @param string $contract_id    合同编号
     * @param string $doc_title      文档标题
     * @param string $sign_keyword   定位关键字
     * @param string $return_url     跳转地址
     * @param string $notify_url     异步通知地址
     * @return array
     */
    public function invokeExtSign($transaction_id, $customer_id, $contract_id, $doc_title, $sign_keyword, $return_url, $notify_url) {
        $api_name = '';
        $url = $this->url . $api_name;

        return $this->post($url);
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
    public function invokeExtSignAuto($customer_id, $contract_id, $doc_title, $sign_keyword) {
        $api_name = 'extsign_auto';
        $url = $this->url . $api_name;

        $params = [];
        $params['customer_id'] = $customer_id;
        $params['contract_id'] = $contract_id;
        $params['doc_title'] = $doc_title;
        $params['sign_keyword'] = $sign_keyword;
        return $this->post($url, $params);
    }

    /**
     * 调用签署接口（自动签模式） - 坐标定位
     * @param string $transaction_id      交易号
     * @param string $customer_id         客户编号
     * @param string $client_role         客户角色
     * @param string $contract_id         合同编号
     * @param string $doc_title           文档标题
     * @param string $signature_positions 定位坐标
     * @param string $notify_url          异步通知地址
     * @return array
     */
    public function invokeExtSignAutoXY($transaction_id, $customer_id, $client_role, $contract_id, $doc_title, $signature_positions, $notify_url) {
        $api_name = '';
        $url = $this->url . $api_name;

        return $this->post($url);
    }

    /**
     * 调用客户签署状态查询接口
     * @param string $contract_id 合同编号
     * @param string $customer_id 客户编号
     * @return array
     */
    public function invokeQuerySignStatus($contract_id, $customer_id) {
        $api_name = '';
        $url = $this->url . $api_name;

        return $this->post($url);
    }

    /**
     * 加水印
     * @param params[
     * contract_id 合同编号
     * stamp_type 水印类型 1-文字，2-图片
     * text_name 文字名称 当水印类型为 1 时，不为空，不超过 100 位
     * font_size 文字大小 当水印类型为 1 时，不为空；不能超过 72
     * picbase64 当水印类型为 2 时，不为空
     * rotate 水印旋转的角度（正角度 为逆时针，负角度为顺时 针），默认不旋转
     * concentration_factor 水印的密集度，PDF 横 向和纵向水印的个数，默 认为 1
     * opacity 透明度，默认为 0.5  范围：0-1
     * ]
     * @return mixed
     */
    public function watermarkPdf($params) {
        //参数校验
        if(empty($params) || !is_array($params)){
            return false;
        }
        //加水印
        return $this->post($this->url . 'watermark_pdf', $params);
    }

    /**
     * 调用合同归档
     * @param string $contract_id 合同编号
     * @return array
     */
    public function invokeContractFilling($contract_id) {
        $api_name = '';
        $url = $this->url . $api_name;

        return $this->post($url);
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
}