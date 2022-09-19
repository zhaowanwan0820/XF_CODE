<?php
/**
 * UserFeedbackService class file.
 *
 * @author 温彦磊 <wenyanlei@ucfgroup.com>
 **/

namespace core\service;

use core\dao\UserFeedbackModel;

/**
 * UserFeedback service
 *
 * @packaged default
 * @author 温彦磊 <wenyanlei@ucfgroup.com>
 **/
class UserFeedbackService extends BaseService
{
    
    /**
     * 获取反馈列表
     * 
     * @param $page 页数
     * @param $page_size 每页条数
     * @return array
     **/
    public function feedbackList($page = 1, $page_size = 0)
    {
        $page = $page <= 0 ? 1 : $page ;
        $page_size = $page_size <= 0 ? app_conf("PAGE_SIZE") : $page_size ;
        
        $user_feedback_model = new UserFeedbackModel();
        return $user_feedback_model->getList($page, $page_size);
    }
    
    /**
     * 添加反馈
     * 
     * @param $user_id 用户id
     * @param $feedback 反馈内容
     * @param $client 客户端名称
     * @return bool
     **/
    public function feedbackInsert($data){

        $user_feedback_model = new UserFeedbackModel();
        
        $user_feedback_model->user_id = intval($data['user_id']);
        $user_feedback_model->content = htmlentities($data['content'],ENT_COMPAT, 'UTF-8');
        $user_feedback_model->image_id = htmlentities($data['image_id'],ENT_COMPAT, 'UTF-8');
        $user_feedback_model->mobile = $data['mobile'];
        $user_feedback_model->sysver = htmlentities($data['sysver'],ENT_COMPAT, 'UTF-8');
        $user_feedback_model->softver = htmlentities($data['softver'],ENT_COMPAT, 'UTF-8');
        $user_feedback_model->models = htmlentities($data['models'],ENT_COMPAT, 'UTF-8');
        $user_feedback_model->imei = htmlentities($data['imei'],ENT_COMPAT, 'UTF-8');
        $user_feedback_model->create_time = get_gmtime();
        
        return $user_feedback_model->insert();
    }
    
    /**
     * 删除反馈
     *
     * @param $id_str 删除的反馈id
     * @param $is_shift 是否彻底删除
     * @return bool
     **/
    public function feedbackDelete($id_str, $is_shift = 0){
        
        $id_arr = array_unique(array_filter(explode(',', $id_str)));
        
        $res = false;
        if($id_arr){
            $id_str = implode(',', $id_arr);
            $user_feedback_model = new UserFeedbackModel();
            $res = $user_feedback_model->delete($id_str, $is_shift);
        }
        
        return $res;
    }
}
// END class UserFeedbackService
