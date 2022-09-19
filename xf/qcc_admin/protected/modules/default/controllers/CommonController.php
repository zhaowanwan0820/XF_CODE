<?php
class CommonController extends CController
{
    

    /**
     * Get guid
     *
     * @return string
     */
    protected function getGUID()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid =substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid, 12, 4).$hyphen
        .substr($charid, 16, 4).$hyphen
        .substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * 获取图片二维码的url
     * @author JU
     * @date 2016/04/29
     */
    public function actionQrcode($i)
    {
        require_once(WWW_DIR . '/itzlib/plugins/qrcode/qrcode.php');
        $QRcode = new QRcode;
        echo $QRcode->png($i, false, QR_ECLEVEL_L, 6, 0);//生成二维码
    }


    public function actionPaymentCallback()
    {
        try {
            Yii::log('paymentCallBack yibao data:'.print_r($_REQUEST, true), "info");
      
            $customerIdentification = $_REQUEST["customerIdentification"];//app_10013183371

            $configs = Yii::app()->c->payment_account_config;
            foreach ($configs as $key => $value) {
                if($value['APP_KEY'] ==  $customerIdentification){
                    $config = $value;
                    break;
                }
            }
            Yii::log(__CLASS__.' paymentCallBack config:'.print_r($config, true), "info");

            $source = $_REQUEST["response"];
        
        
            $_data = YopSignUtils::decrypt($source, $config['CFCA_PRIVATE_KEY'], $config['PUBLIC_KEY']);
           
            Yii::log(__CLASS__.' paymentCallBack dec data:'.print_r($_data, true), "info");

            $data = json_decode($_data, true);
        
            Yii::log(__CLASS__.' paymentCallBack array  data:'.print_r($_data, true), "info");


            $res = ExclusivePurchaseService::getInstance()->paymentCallBack($data);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
