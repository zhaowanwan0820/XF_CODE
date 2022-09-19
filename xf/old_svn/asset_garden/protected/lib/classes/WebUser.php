<?php
/*
 * 用户类，用于用户登录、记录用户信息等等
 */

class WebUser extends CWebUser {

	public function init() {
		parent::init();
	}
    
    // This is a function that checks the field 'type'
    // type == 16 ,means is guar role
    // access it by Yii::app()->user->isAdmin()
    function isGuar(){
        $UserClass = new UserClass();
        $user = $UserClass->getByUserName(Yii::app()->user->id);

        if (!empty($user)&&$user->type_id==16){
           return 1;
        }
        else{
            return false;
        }
    }

    // This is a function that checks the field 'type'
    // type == 4 ,means is 公司
    // access it by Yii::app()->user->isAdmin()
    function isCorp(){
        $UserClass = new UserClass();
        $user = $UserClass->getByUserName(Yii::app()->user->id);

        if (!empty($user)&&$user->type_id==4){
           return TRUE;
        }
        else{
            return false;
        }
    }

    function isUnion(){
        if(isset(Yii::app()->user->_is_union)){
            return Yii::app()->user->_is_union;
        }else{
            return false;
        }

    }
	
}
