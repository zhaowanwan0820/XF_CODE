<?php

class MessagequeueCommand extends CConsoleCommand {

    /**
     * 项目逾期站内信解析入队列
     */
    public function actionBorrowOverdueMessage(){
        //每周五23点清除文件名称重复限制
        if(date('w') == 5 && date('H') == 23){
            RedisQueueService::getInstance()->delete('crm_file_upload_list');
        }
        echo "start\n";
        $success_num = 0;
        $fail_num    = 0;
        $fail_data   = [];

        $connection = Yii::app()->dwdb;
        $command = $connection->createCommand();
        while ($data = RedisQueueService::getInstance()->deQueue('crm_borrow_overdue_list')){
            $command->reset();
            $command->select = 'id';
            $command->from   = 'dw_borrow';
            $command->where  = "name = '{$data['borrow_name']}'";
            //echo $command->text . "\n";//exit;
            $borrow = $command->queryRow();
            if(empty($borrow)){
                echo "{$data['borrow_name']}：项目不存在\n";
                Yii::log ("{$data['borrow_name']}：项目不存在！param：".print_r($data, true), CLogger::LEVEL_ERROR, __METHOD__);
                $fail_num++;
                $fail_data[] = $data;
                continue;
            }

            $command->reset();
            $command->select = 'user_id';
            $command->from   = 'dw_borrow_collection';
            $command->where  = "borrow_id = {$borrow['id']} and borrow_type != 3100 and status = 0 and type in (1,9) and repay_time = UNIX_TIMESTAMP('{$data['repay_date']}')";
            $command->group  = 'user_id';
            //echo $command->text . "\n";exit;
            $borrow_collection = $command->queryAll();
            if(empty($borrow_collection)){
                echo "{$data['borrow_name']}：暂无还款计划\n";
                Yii::log ("{$data['borrow_name']}：暂无还款计划！param：".print_r($data, true), CLogger::LEVEL_ERROR, __METHOD__);
                $fail_num++;
                $fail_data[] = $data;
                continue;
            }
            $redis_data = [];
            foreach ($borrow_collection as $v){
                $redis_data[] = [
                    'user_id'      => $v['user_id'],
                    'trigger_code' => $data['trigger_code'],
                    'param'        => $data['param'],
                ];
            }
            //放入消息队列
            RedisQueueService::getInstance()->pipeline();
            foreach($redis_data as $r_data){
                RedisQueueService::getInstance()->enQueue('crm_message_list',$r_data);
            }
            $transactions_result = RedisQueueService::getInstance()->exec();
            if(empty($transactions_result)){
                echo "{$data['borrow_name']}：入队列执行失败\n";
                Yii::log ("{$data['borrow_name']}：入队列执行失败！param：".print_r($data, true), CLogger::LEVEL_ERROR, __METHOD__);
                $fail_num++;
                $fail_data[] = $data;
                continue;
            }
            $success_num++;
            echo "{$data['borrow_name']}：执行成功\n";
        }
        //失败邮件报警
        if($fail_num){
            $email_arr = ['yanghua@itouzi.com'];
            $title = '【报警】CRM项目逾期站内信解析失败';
            $content = 'CRM项目逾期站内信解析总计'.($success_num + $fail_num)."个，成功：{$success_num}个，失败：{$fail_num}个。<br/>失败参数数据如下：<br/>";
            $content .= json_encode($fail_data,true);
            $mailSender = new MailClass();
            $mailSender->send($email_arr, '', $title, $content, '', '', false);
        }
        echo "成功：{$success_num}，失败：{$fail_num}\n";
        echo "end\n";
        exit;
    }
}
?>