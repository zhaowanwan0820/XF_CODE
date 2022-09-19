<?php
/*
*图形验证码的检查接口
*/
class CaptchaCheck{

  /*产生业务代码
  *@return string 返回业务分类代码 
  */
   private function getBusHash()
   {
      $ref =   $_SERVER['HTTP_REFERER'];
      return substr(md5($ref),1,5);
   }

   /*
   *销毁验证码的缓存
   *@param string $hash 哈希
   */
   private function destroy($hash){
       Yii::app()->dcache->delete($hash);
       Yii::log("captcha destory","info");
   }
   /*
   *验证图形验证码输入的正确性
   *@param string $code 传入的验证码字符串
   *@return bool 返回验证码的正确性true为正确否则为错误
   */
   public function ValidCaptcha($chcode,$destory=true){
        $info = array();
        $cookie_name = 'caphash_'.$this->getBusHash();
        $hash = $_COOKIE[$cookie_name];
        if($hash)
        {
           if($code = Yii::app()->dcache->get($hash))
           {
            if($destory)
            {
                $this->destroy($hash);
            }
            if($code['code']==strtolower($chcode)){
                $info['code'] = 0;
            }else{
                $info['code'] = 2106;
            }
           }
           else
           {
               $info['code'] = 2105;
               Yii::log('Can not get code from cache ','error', 'CaptchaCheck.ValidCaptcha');
           }
            
        }
        else
        {
            $info['code'] = 2105;
            Yii::log('Can not get data from COOKIE ', 'error', 'CaptchaCheck.ValidCaptcha');
        }
        return $info;
   }
}