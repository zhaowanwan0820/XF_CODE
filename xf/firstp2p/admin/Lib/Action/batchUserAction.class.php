<?php

use core\service\UserTagService;

class BatchUserAction extends CommonAction{

    private $tags_name_approval   = '标签_待合规审批';
    private $tags_name_settlement = '标签_财务结算无效';
    private $tag_item = array();
    private $status_enum = array('无效','有效');

    public function index()
    {
        $this->display();
    }

    public function process() {
        $this->tag_item = array($this->tags_name_approval, $this->tags_name_settlement);

        $file_data = $_FILES['file'];
        if (empty($file_data['tmp_name'])){
            $this->error('上传的文件不能为空');
        }

        $file_name_suffix = strrpos($file_data['name'], '.');
        if(($suffix = substr($file_data['name'],$file_name_suffix+1) !== 'csv')){
            $this->error('上传的文件不是csv格式');
        }

        $row = 1;
        $handle = fopen($file_data['tmp_name'],'r');

        $fileline = file($file_data['tmp_name']);
        $file_line_num = count($fileline);
        if($file_line_num > 2001){
            $this->error('上传的数据不能超过2000行');
        }

        $k = 0;
        while(($res = fgetcsv( $handle)) !== FALSE){
            for($i=0; $i<4; $i++){
                $csv_list[$k][$i] = ($i !== 0) ? (string)trim(mb_convert_encoding($res[$i], 'utf-8', 'gbk')) : $res[$i];
            }
            $k++;
        }
        unset($csv_list[0]);

        /*数据校验*/
        $data_list = $tags_list = array(); 
        foreach($csv_list as $csv_item){
            $user_id     = $csv_item[0];
            $user_tags   = $csv_item[2];
            $tmp_status  = $csv_item[1];
            $settlement_tags   = $csv_item[3];
            $user_status = (in_array($tmp_status, $this->status_enum)) ? (($tmp_status == $this->status_enum[0]) ? 0 : 1) : 3;

            (empty($user_id) || empty($tmp_status)) && $this->error('导入文件存在空行');

            (!is_numeric($user_id)) && $this->error('User ID不能为非阿拉伯数字');

            ($user_status == 3) && $this->error('用户状态不是有效或无效');

            $data_list[(int)$user_id] = $user_status;

            if($user_tags == $this->tags_name_approval){
                $user_list['approval'][] = $user_id;
            }

            if($settlement_tags == $this->tags_name_settlement){
                $user_list['settlement'][] = $user_id;
            }
        }

        /*数据库操作*/
        $GLOBALS['db']->startTrans();
        try{
            $user_status_res = $GLOBALS['db']->query($this->changeUserStatus($data_list));
            if(!$user_status_res){
                throw new \Exception('用户状态批量更新失败');
            }

            /*批量打标签*/
            $tag_list = $this->getUserTagsId();
            if(count($user_list['approval'])){
                if(!$this->batchInsertTags($user_list['approval'], $tag_list[0])){
                    throw new \Exception('用户批量更改标签失败_1');
                }
            }

            if(count($user_list['settlement'])){
                if(!$this->batchInsertTags($user_list['settlement'], $tag_list[1])){
                    throw new \Exception('用户批量更改标签失败_2');
                }
            }

            /*增加本批次数据*/
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $adm_name    = $adm_session['adm_name'];
            $comments    = (string) trim($_POST['comments']);
            $change_res = $GLOBALS['db']->insert('firstp2p_batch_user_change', array( 'finish_time' => get_gmtime(), 'operate_author' => $adm_name, 'file_name' => $file_data['name'], 'comments' => $comments));
            $insert_id  = $GLOBALS['db']->insert_id();
            if(!$insert_id){
                throw new \Exception('增加本批次数据失败');
            }

            $firstp2p_res_sql = 'REPLACE INTO `firstp2p_batch_user_res`(`user_id`, `status`,`tags`,`batch_id`,`complete_time`) VALUES';
            foreach($csv_list as $vals){
                $vals[2] = !empty($vals[3]) ? $vals[2] . '|' . $vals[3] : $vals[2];
                $firstp2p_res_sql .= "(".$vals[0].",'".$vals[1]."','".$vals[2]."',".$insert_id.",".get_gmtime()."),";
            }
            $firstp2p_res_sql = substr($firstp2p_res_sql, 0, strrpos($firstp2p_res_sql, ','));
            $batch_res        = $GLOBALS['db']->query($firstp2p_res_sql);

            if(!$batch_res || !$GLOBALS['db']->affected_rows()){
                $this->error('增加本批次详细数据失败');
            }
            $res = $GLOBALS['db']->commit();
            if($res){
                $this->success('提交成功', 0, '/m.php?m=batchUser&a=index');
            }
            throw new \Exception('提交事务失败');
        }catch(\Exception $e){
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage());
        }
    }
    
    /**
     * @获取指定标签id
     * @param void
     * @return void
     */
    private function getUserTagsId()
    {
        foreach($this->tag_item as $vals){
            $tags_split = explode("_", $vals);
            $tags_item[] = $tags_split[1];
        }

        $tag_res = (new UserTagService())->lists();
        foreach($tag_res as $vals){
            if(in_array($vals['name'], $tags_item)){
                $tags_info[$vals['name']] = $vals['id'];
            }
        }

        $tags_name = array_keys($tags_info);
        foreach($tags_item as $vals){
            if(!in_array($vals, $tags_name)){
                $this->error($vals . '标签不存在');
            }
        }
        
        return array_values($tags_info);
    }

    /*输出csv文件*/
    public function demoCsv()
    {
        $file_name = 'demo.csv';
        $fields = array('0' => 'User ID', '1' => '状态', '2' => $this->tags_name_approval, '3' => $this->tags_name_settlement);
        $this->exportCsv($fields, $file_name) ;        
    }

    /*生成csv文件*/
    function exportCsv($title_arr = array(), $file_name = 'demo.csv', $header_data = array())
    {
        if(count($title_arr)){
            $nums = count($title_arr);
            for($i=0; $i<$nums-1; ++$i) {
                $csv_data .= '"' . $title_arr[$i] . '",';
            }
        }

        ($nums>0) && $csv_data .= '"' . $title_arr[$nums - 1] ."\"\r\n";

        if(count($header_data)){
            $nums = count($header_data);
            foreach ($header_data as $k => $row) {
                for ($i = 0; $i < 3; ++$i) {
                    $row[$i] = str_replace("\"", "\"\"", $row[$i]);
                    $csv_data .= '"' . $row[$i] . '",';
                }
                $csv_data .= '"' . $row[3] . "\"\r\n";
                unset($data[$k]);
            }
        }

        if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")){
            $file_name = urlencode($file_name);
            $file_name = str_replace('+', '%20', $file_name);
        }
        $csv_data = mb_convert_encoding($csv_data, 'cp936', 'UTF-8');
        $file_name = $file_name;
        header('Content-type:text/csv;');
        header('Content-Disposition:attachment;filename=' . $file_name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $csv_data;
    }

    /*更改用户状态*/
    private function changeUserStatus($data_list)
    {
        $ids = implode(',', array_keys($data_list)); 
        $user_status_sql = "UPDATE `firstp2p_user` SET `is_effect` = CASE id ";
        foreach ($data_list as $id => $ordinal) {
            $user_status_sql .= sprintf("WHEN %d THEN %d ", $id, $ordinal);
        }
        $user_status_sql .= "END WHERE id IN ($ids)";

        return $user_status_sql;
    }

    public function downCsv()
    {
        $id = (int)trim($_REQUEST['id']);
        $id = ($id>0) ? $id : $this->error('参数错误');
        $data = M('BatchUserRes')->where('batch_id = ' . $id)->findAll();

        $batch_data = M('BatchUserChange')->where('id = ' . $id)->find();
        $file_name   = $batch_data['file_name'];

        foreach($data as $vals){
            $csv_item[] = array($vals['user_id'], $vals['status'], $vals['tags']);
        }
        $this->exportCsv(array(),$file_name, $csv_item) ;        
    }

    /**
     * @批量打标签
     * @param array $user_list
     * @param int   $tag_id
     * @return bool
     */
    private function batchInsertTags($user_list, $tag_id)
    {
        foreach($user_list as $vals){
            $rows[] = "($vals, $tag_id)";
        }
        $sql = "REPLACE INTO `firstp2p_user_tag_relation`(`uid`, `tag_id`) VALUES ". implode(',', $rows);
        $user_tag_relation_res = $GLOBALS['db']->query($sql);
        if(!$user_tag_relation_res){
            return false;
        }

        return true;
    }
}

