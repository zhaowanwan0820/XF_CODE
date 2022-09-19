<?php
/**
 *用户反馈
 */

namespace core\service;
use core\dao\FeedbackModel;
use libs\utils\Logger;
use core\dao\UserModel;

class FeedbackService extends BaseService{

    private $userId;
    private $type;
    private $status;
    private $is_read;

    public static $event_info_complain=array(
        1 => '纠纷案件',
        2 => '违法行为',
        3 => '服务质量',
        4 => '其他',
    );

    public static $for_type=array(
        1 => '投资方',
        2 => '融资方',
        3 => '担保方',
        4 => '咨询方',
        5 => '平台方',
        6 => '其他',
    );

    public static $event_info_answer=array(
        1 => '项目情况',
        2 => '产品规则',
        3 => '交易结构',
        4 => '合同协议',
        5 => '营销宣传',
        6 => '其他',
    );


    public function __construct($userId='',$type='',$status=1,$is_read=0){
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, $userId,$type,$status,$is_read)));
        $this->userId = $userId;
        //反馈类型
        $this->type=$type;
        $this->status=$status;
        $this->is_read=$is_read;
    }


    public function checkData($data){
        $response = array('errCode' => 0,'msg' =>'','data' => false);
        try {
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'data:'.json_encode($data))));
            $title = '';

            if(intval($this->type)==2) {
                $title = $data['title'];
            }
            $data['content']=htmlspecialchars(addslashes(trim($data['content'])));
            $this->checkUser();
            $this->askAmount();
            $this->checkLength($data['content'], $title);
            $result=$this->insert($data);
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'result:'.$result)));
        }catch(\Exception $e){
            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            Logger::error(implode(" | ",array(__CLASS__, __FUNCTION__,'error:'.$response['msg'])));
            return $response;
        }
        $response['data'] = $result;
        $response['msg'] ='您的反馈我们已收到!';
        return $response;
    }

    /**
     * 验证用户信息
     */
    protected function checkUser(){
        $userInfo = (new UserModel())->find(intval($this->userId));
        if(empty($userInfo)){
            throw new \Exception('用户不存在');
        }
    }

    /**
     *  统计当日用户提问次数
     */
    protected function askAmount(){
        $ask_cnt=FeedbackModel::instance()->getAskAmountByUserIdAndTime(intval($this->userId));
        if(intval($ask_cnt)>=10){
            throw new \Exception('您今天提交的问题过多，请明日再来!');
        }
    }

    /**
     *检查数据长度
     */
    protected function checkLength($content,$title){
        if(mb_strlen(trim($content))>200 || (!empty($title)&&mb_strlen(trim($title))>20)){
            throw new \Exception('超过字数限制，请重新输入');
        }
    }

    public function insert($data){
        $feedBackModel=new FeedbackModel();
        //1-咨询答疑,2-投诉举报
        if(intval($this->type)==2){
            $feedBackModel->title=$data['title'];
            $feedBackModel->contact_name=trim($data['contact_name']);
            $feedBackModel->contact_mobile=trim($data['contact_mobile']);
            $feedBackModel->contact_email=trim($data['contact_email']);
            $feedBackModel->for_name=trim($data['for_name']);
            $feedBackModel->for_type=intval($data['for_type']);
            $feedBackModel->for_product=trim($data['for_product']);
            $feedBackModel->is_anony=intval($data['is_anony']);
        }
        $feedBackModel->type=intval($this->type);
        $feedBackModel->user_id=intval($this->userId);
        $feedBackModel->status=intval($this->status);
        $feedBackModel->content=trim($data['content']);
        //图片附件
        $feedBackModel->attachment=$data['image_url'];
        $feedBackModel->create_time=time();
        $feedBackModel->event_type=intval($data['event_type']);
        $result=$feedBackModel->insert();
        return $result;
    }

    /**
     *  在线答疑列表页,分页
     */
    public function answerList(){
        $result=FeedbackModel::instance()->getListByUserId($this->userId,$this->type);
         Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'result:'.json_encode($result))));
        return $result;
    }

    /**
     * 获取信息(通过状态和类型判断用户是否有新回复或者是否已读)
     */
    public function getAnswerInfo(){
        $data['userId']=$this->userId;
        $data['type']=$this->type;
        $data['status']=$this->status;
        $data['is_read']=$this->is_read;
         Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'data:'.json_encode($data))));
        $result=FeedbackModel::instance()->getInfo($data);
        if(empty($result)){
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'result:'.json_encode($result))));
            return true;
        }
        Logger::error(implode(" | ",array(__CLASS__, __FUNCTION__,'result:'.json_encode($result))));
        return false;
    }

    /**
     * 统计用户历史提问次数
     */
    public function askTotalAmount(){
        $ask_cnt=FeedbackModel::instance()->getAskAmountByUserId(intval($this->userId),intval($this->type));
        return $ask_cnt;
    }

    /**
     * 更新列表状态为已读
     */
    public function updateIsReadByUserId(){
        $params['is_read']=$this->is_read;
        $params['userId']=$this->userId;
        $params['type']=$this->type;
        $params['status']=$this->status;
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'params:'.json_encode($params))));
        $result=FeedbackModel::instance()->updateIsRead($params);
        return $result;
    }

}
