<?php
/**
 * 客户端用户反馈管理
 *
 * @author wenyanlei@ucfgroup.com
 */

use core\service\UserFeedbackService;

class UserFeedbackAction extends CommonAction
{
    public static $msg = array('code' => '0000', 'msg' => '');
    
	public function index(){
		
	    $page_num = intval($_REQUEST['p']);
	    $page_num = $page_num >= 0 ? $page_num : 1;

	    $user_feedback_service = new UserFeedbackService();
	    $feedback_list = $user_feedback_service->feedbackList($page_num, C('PAGE_LISTROWS'));

	    $page_obj = new Page($feedback_list['count'], C('PAGE_LISTROWS'));

	    $this->assign("list", $feedback_list['list']);
	    $this->assign("page", $page_obj->show());
	    $this->assign("nowPage", $page_obj->nowPage);

		$this->display();
	}

    public function delete(){
		
		//删除指定记录
		$id = $_REQUEST ['id'];
		$ajax = intval($_REQUEST['ajax']);
		$is_shift = intval($_REQUEST ['islot']);

		if (isset ( $id )) {

		    $user_feedback_service = new UserFeedbackService();
		    $res = $user_feedback_service->feedbackDelete($id, $is_shift);
		    
		    $log_msg = "客户端反馈id：$id";
			
			if ($res) {
				save_log($log_msg.'删除成功',1);
				$this->success ('删除成功',$ajax);
			} else {
				save_log($log_msg.'删除失败',0);
				$this->error ('删除失败',$ajax);
			}
		} else {
			$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}

	public function getFeedImage(){
        $id = intval($_POST['id']);
        if (!empty($id)) {
            $model = MI('UserFeedback');
            $result = $model->find($id);
            $image_id = $result['image_id'];
            if (!empty($image_id)) {
                $imageUrl = get_attr($image_id, 1, true);
                $imageHtml = $imageUrl ? "<img id='image' src = '{$imageUrl}' width='100%'> " : '暂无照片';
                $str = "<div class='image' >
                        {$imageHtml}
                        </div>";
                self::$msg['msg'] = $str;
            } else
                self::$msg = array('code' => 4001, 'msg' => '数据不存在');
        } else
            self::$msg = array('code' => 4000, 'msg' => '参数错误');
        echo json_encode(self::$msg);
    }
}
?>