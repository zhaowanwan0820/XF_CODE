<?php
//sdk方式的通道需要实现的接口
interface SdkInterface{
    public function mobileForm($formData);//包装sdk需要的数据
}



?>