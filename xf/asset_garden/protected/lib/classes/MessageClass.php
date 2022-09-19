<?php
/*
 * 消息
 */

class MessageClass  {
	
    /**
     * 获取未读消息
     **/
    public function getMessages($user_id){
        $MessageModel = new Message();
        $criteria = new CDbCriteria; 
        $attributes = array(
            "receive_user"    =>   $user_id, 
            "status"     =>   0,
            "deltype"    =>   0,
        );
        $MessageResult =$MessageModel->findAllByAttributes($attributes,$criteria);
        return $MessageResult;
    }
     
    public function send($receiveUserId, $sentUserId, $name, $content, $type = 'system', $status = '0') {
    
    	if(empty($type)) {
    		$type = 'system';
    	}
    	
    	$MessageModel = new Message();
    	$MessageModel->receive_user = $receiveUserId;
    	$MessageModel->sent_user = $sentUserId;
    	$MessageModel->type = $type;
    	$MessageModel->name = $name;
    	$MessageModel->content = $content;
    	$MessageModel->status = $status;
    	
    	$MessageModel->addtime = time();
    	$MessageModel->addip = FunctionUtil::ip_address();
    	
    	if($MessageModel->save() == false){
    		return false;
    	}else{
    		return true;
    	}
    }
    
}
