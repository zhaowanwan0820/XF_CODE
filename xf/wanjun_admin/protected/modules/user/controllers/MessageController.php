<?php
use iauth\models\AuthAssignment;
class MessageController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'UploadPicture' , 'DownloadUserIdTemplate' , 'UploadXls' , 'checkOldMobile' , 'checkNewMobile'
        );
    }

    /**
     * 成功提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionSuccess($msg = '成功' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    /**
     * 文件上传OSS
     * @param $filePath
     * @param $ossPath
     * @return bool
     */
    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            unlink($filePath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * 上传图片
     */
    public function actionUploadPicture()
    {
        if (!empty($_FILES)) {
            header ( "Content-type:application/json; charset=utf-8" );
            $file  = $_FILES['file'];
            $types = array('image/png', 'image/jpg' , 'image/jpeg');
            if ($file['error'] != 0) {
                switch ($file['error']) {
                    case 1:
                        $res["data"] = array();
                        $res['code'] = 1;
                        $res['msg']  = '上传的图片文件超过了服务器限制';
                        echo exit(json_encode($res));
                        break;
                    case 2:
                        $res["data"] = array();
                        $res['code'] = 2;
                        $res['msg']  = '上传的图片文件超过了脚本限制';
                        echo exit(json_encode($res));
                        break;
                    case 3:
                        $res["data"] = array();
                        $res['code'] = 3;
                        $res['msg']  = '图片文件只有部分被上传';
                        echo exit(json_encode($res));
                        break;
                    case 4:
                        $res["data"] = array();
                        $res['code'] = 4;
                        $res['msg']  = '没有图片文件被上传';
                        echo exit(json_encode($res));
                        break;
                    case 6:
                        $res["data"] = array();
                        $res['code'] = 5;
                        $res['msg']  = '找不到临时文件夹';
                        echo exit(json_encode($res));
                        break;
                    case 7:
                        $res["data"] = array();
                        $res['code'] = 6;
                        $res['msg']  = '图片文件写入失败';
                        echo exit(json_encode($res));
                        break;
                    default:
                        $res["data"] = array();
                        $res['code'] = 7;
                        $res['msg']  = '图片文件上传发生未知错误';
                        echo exit(json_encode($res));
                        break;
                }
            }
            $name      = $file['name'];
            $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if(!in_array($file['type'] , $types)){
                $res["data"] = array();
                $res['code'] = 8;
                $res['msg']  = '图片文件类型不匹配';
                echo exit(json_encode($res));
            }
            $new_name = time() . rand(10000,99999);
            $dir      = date('Ymd');
            if (!is_dir('./upload/' . $dir)) {
                $mkdir = mkdir('./upload/' . $dir , 0777 , true);
                if (!$mkdir) {
                    $res["data"] = array();
                    $res['code'] = 9;
                    $res['msg']  = '创建图片文件目录失败';
                    echo exit(json_encode($res));
                }
            }
            $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
            $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
            if ($result) {
                $img_info = getimagesize('./' . $new_url);
                if ($img_info[0] > 320) {
                    $res["data"] = array();
                    $res['code'] = 11;
                    $res['msg']  = '上传图片宽度大于320px';
                    echo exit(json_encode($res));
                }
                $res = $this->upload_oss('./'.$new_url , 'notice_message/' . $new_name . '.' . $file_type);
                if ($res === false) {
                    $res["data"] = array('src' => '/'.$new_url);
                    $res['code'] = 0;
                    $res['msg']  = '上传图片成功';
                    echo exit(json_encode($res));
                } else {
                    $res["data"] = array('src' => Yii::app()->c->oss_preview_address . '/notice_message/' . $new_name . '.' . $file_type);
                    $res['code'] = 0;
                    $res['msg']  = '上传图片成功';
                    echo exit(json_encode($res));
                }
            } else {
                $res["data"] = array();
                $res['code'] = 10;
                $res['msg']  = '上传图片失败';
                echo exit(json_encode($res));
            }
        }
    }

    /**
     * 上传xls文件
     */
    public function actionUploadXls()
    {
        if (!empty($_FILES)) {
            $file  = $_FILES['user_id_file'];
            $types = array('xls');
            if ($file['error'] != 0) {
                switch ($file['error']) {
                    case 1:
                        $this->echoJson(array() , 1 , "上传的xls文件超过了服务器限制");
                        break;
                    case 2:
                        $this->echoJson(array() , 2 , "上传的xls文件超过了脚本限制");
                        break;
                    case 3:
                        $this->echoJson(array() , 3 , "xls文件只有部分被上传");
                        break;
                    case 4:
                        $this->echoJson(array() , 4 , "没有xls文件被上传");
                        break;
                    case 6:
                        $this->echoJson(array() , 5 , "找不到临时文件夹");
                        break;
                    case 7:
                        $this->echoJson(array() , 6 , "xls文件写入失败");
                        break;
                    default:
                        $this->echoJson(array() , 7 , "xls文件上传发生未知错误");
                        break;
                }
            }
            $name      = $file['name'];
            $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if(!in_array($file_type, $types)){
                $this->echoJson(array() , 8 , "xls文件类型不匹配");
            }
            $new_name = time() . rand(10000,99999);
            $dir      = date('Ymd');
            if (!is_dir('./upload/' . $dir)) {
                $mkdir = mkdir('./upload/' . $dir , 0777 , true);
                if (!$mkdir) {
                    $this->echoJson(array() , 9 , "创建xls文件目录失败");
                }
            }
            $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
            $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
            if ($result) {
                $this->echoJson(array('url'=>$new_url , 'name'=>$new_name . '.' . $file_type) , 0 , "保存xls文件成功");
            } else {
                $this->echoJson(array() , 10 , "保存xls文件失败");
            }
        }
    }

    /**
     * 公告列表
     */
    public function actionNoticeList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            $time  = time();
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 标题
            if (!empty($_POST['title'])) {
                $t      = trim($_POST['title']);
                $where .= " AND title = '{$t}' ";
            }
            // 校验发布时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND start_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND start_time <= {$end} ";
            }
            // 发布状态
            if (!empty($_POST['status'])) {
                if ($_POST['status'] == 2) {
                    $where .= " AND start_time <= '{$time}' AND status = 1 ";
                } else if ($_POST['status'] == 1) {
                    $where .= " AND start_time > '{$time}' AND status = 1 ";
                } else if ($_POST['status'] == 3) {
                    $where .= " AND status = 2 ";
                }
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(id) AS count FROM xf_notice {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_notice {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList    = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            $edit_status = 0;
            $set_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Message/NoticeInfo') || empty($authList)) {
                $info_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/EditNotice') || empty($authList)) {
                $edit_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/SetNotice') || empty($authList)) {
                $set_status = 1;
            }
            foreach ($list as $key => $value) {
                if ($value['start_time'] <= $time) {
                    $value['status_name'] = '已发布';
                    $value['edit_status'] = 0;
                    $value['set_status']  = $set_status;
                } else if ($value['start_time'] > $time) {
                    $value['status_name'] = '待发布';
                    $value['edit_status'] = $edit_status;
                    $value['set_status']  = 0;
                }
                if ($value['status'] == 2) {
                    $value['status_name'] = '已撤回';
                }
                $value['start_time']    = date('Y-m-d H:i:s' , $value['start_time']);
                $value['add_time']      = date('Y-m-d H:i:s' , $value['add_time']);
                $value['add_user_name'] = '';
                $value['info_status']   = $info_status;
                $listInfo[] = $value;
                $user_id_arr[] = $value['add_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['add_user_name'] = $user_infos[$value['add_user_id']];
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Message/AddNotice') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('NoticeList' , array('add_status'=> $add_status));
    }

    /**
     * 新增公告
     */
    public function actionAddNotice()
    {
        if (!empty($_POST)) {
            if (empty($_POST['title'])) {
                $this->echoJson(array() , 1 , "请输入标题");
            }
            // if (empty($_POST['abstract'])) {
            //     $this->echoJson(array() , 2 , "请输入摘要");
            // }
            if (empty($_POST['content'])) {
                $this->echoJson(array() , 3 , "请输入内容");
            }
            $time = time();
            if (!empty($_POST['start_time'])) {
                $start_time = strtotime($_POST['start_time']);
            } else {
                $start_time = $time;
            }
            if ($start_time < $time) {
                $this->echoJson(array() , 4 , "发布时间不可早于当前时间");
            }
            $title       = trim($_POST['title']);
            $abstract    = trim($_POST['abstract']);
            $content     = $_POST['content'];
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "INSERT INTO xf_notice (title , abstract , content , add_user_id , add_time , add_ip , start_time , status) VALUES ('{$title}' , '{$abstract}' , '{$content}' , {$add_user_id} , {$time} , '{$add_ip}' , {$start_time} , 1) ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if ($result) {
                $this->echoJson(array() , 0 , "新增公告成功");
            } else {
                $this->echoJson(array() , 5 , "新增公告失败");
            }
        }

        return $this->renderPartial('AddNotice' , array());
    }

    /**
     * 公告详情
     */
    public function actionNoticeInfo()
    {
        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误' , 5);
            }
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM xf_notice WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID' , 5);
            }
            $res['start_time'] = date('Y-m-d H:i:s' , $res['start_time']);

            return $this->renderPartial('NoticeInfo' , array('res' => $res));
        }
    }

    /**
     * 编辑公告
     */
    public function actionEditNotice()
    {
        if (!empty($_POST)) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , "ID格式错误");
            }
            $id = intval($_POST['id']);
            $sql = "SELECT * FROM xf_notice WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 2 , "请输入正确的ID");
            }
            $time = time();
            if ($old['start_time'] <= $time) {
                $this->echoJson(array() , 3 , "此公告已经发布,不可编辑!");
            }
            if (empty($_POST['title'])) {
                $this->echoJson(array() , 4 , "请输入标题");
            }
            // if (empty($_POST['abstract'])) {
            //     $this->echoJson(array() , 5 , "请输入摘要");
            // }
            if (empty($_POST['content'])) {
                $this->echoJson(array() , 6 , "请输入内容");
            }
            if (!empty($_POST['start_time'])) {
                $start_time = strtotime($_POST['start_time']);
            } else {
                $start_time = $time;
            }
            if ($start_time < $time) {
                $this->echoJson(array() , 7 , "发布时间不可早于当前时间");
            }
            $title       = trim($_POST['title']);
            $abstract    = trim($_POST['abstract']);
            $content     = $_POST['content'];
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE xf_notice SET title = '{$title}' , abstract = '{$abstract}' , content = '{$content}' , operation_user_id = {$add_user_id} , operation_time = {$time} , operation_ip = '{$add_ip}' , start_time = {$start_time} WHERE id = {$old['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if ($result) {
                $this->echoJson(array() , 0 , "保存公告成功");
            } else {
                $this->echoJson(array() , 8 , "保存公告失败");
            }
        }

        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误' , 1);
            }
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM xf_notice WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID' , 2);
            }
            if ($res['start_time'] <= time()) {
                return $this->actionError('此公告已经发布,不可编辑!' , 3);
            }
            $res['start_time'] = date('Y-m-d H:i:s' , $res['start_time']);

            return $this->renderPartial('EditNotice' , array('res' => $res));
        }
    }

    /**
     * 撤回公告
     */
    public function actionSetNotice()
    {
        if (!empty($_POST['id'])) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , "ID格式错误");
            }
            $id = intval($_POST['id']);
            $sql = "SELECT * FROM xf_notice WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 2 , "请输入正确的ID");
            }
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE xf_notice SET operation_user_id = {$add_user_id} , operation_time = {$time} , operation_ip = '{$add_ip}' , status = 2 WHERE id = {$old['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if ($result) {
                $this->echoJson(array() , 0 , "操作成功");
            } else {
                $this->echoJson(array() , 3 , "操作失败");
            }
        }
    }

    /**
     * 消息列表
     */
    public function actionMessageList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            $time  = time();
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 标题
            if (!empty($_POST['title'])) {
                $t      = trim($_POST['title']);
                $where .= " AND m.title = '{$t}' ";
            }
            // 校验发布时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND m.start_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND m.start_time <= {$end} ";
            }
            // 发布状态
            if (!empty($_POST['status'])) {
                if ($_POST['status'] == 2) {
                    $where .= " AND m.start_time <= '{$time}' AND m.status = 1 ";
                } else if ($_POST['status'] == 1) {
                    $where .= " AND m.start_time > '{$time}' AND m.status = 1 ";
                } else if ($_POST['status'] == 3) {
                    $where .= " AND m.status = 2 ";
                }
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT COUNT(DISTINCT m.id) AS count FROM xf_message AS m LEFT JOIN xf_message_detail AS d ON m.id = d.message_id {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT m.id , m.title , m.abstract , m.add_user_id , m.add_time , m.add_ip , m.start_time , m.status , m.user_scope , m.total_user , COUNT(CASE WHEN d.status = 1 THEN 1 ELSE NULL END) AS d_status_1 , COUNT(CASE WHEN d.status = 2 THEN 1 ELSE NULL END) AS d_status_2 FROM xf_message AS m LEFT JOIN xf_message_detail AS d ON m.id = d.message_id {$where} GROUP BY m.id ORDER BY m.id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList    = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            $edit_status = 0;
            $set_status  = 0;
            if (!empty($authList) && strstr($authList,'/user/Message/MessageInfo') || empty($authList)) {
                $info_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/EditMessage') || empty($authList)) {
                $edit_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/SetMessage') || empty($authList)) {
                $set_status = 1;
            }
            foreach ($list as $key => $value) {
                if ($value['start_time'] <= $time) {
                    $value['status_name'] = '已发布';
                    $value['edit_status'] = 0;
                    $value['set_status']  = $set_status;
                } else if ($value['start_time'] > $time) {
                    $value['status_name'] = '待发布';
                    $value['edit_status'] = $edit_status;
                    $value['set_status']  = 0;
                }
                if ($value['status'] == 2) {
                    $value['status_name'] = '已撤回';
                }
                if ($value['user_scope'] == 1) {
                    $value['d_status_2'] = $value['total_user'] - $value['d_status_1'];
                    $value['user_scope'] = '全量在途用户';
                } else {
                    $value['user_scope'] = '指定在途用户';
                }
                $value['add_time']      = date('Y-m-d H:i:s' , $value['add_time']);
                $value['start_time']    = date('Y-m-d H:i:s' , $value['start_time']);
                $value['add_user_name'] = '';
                $value['info_status']   = $info_status;
                $listInfo[] = $value;
                $user_id_arr[] = $value['add_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['add_user_name'] = $user_infos[$value['add_user_id']];
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Message/AddMessage') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('MessageList' , array('add_status' => $add_status));
    }

    /**
     * 新增消息
     */
    public function actionAddMessage()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_1']) || !in_array($_POST['user_1'] , array(1 , 2))) {
                $this->echoJson(array() , 1 , "请正确先择推送用户范围");
            }
            if (empty($_POST['user_2']) || !in_array($_POST['user_2'] , array(1 , 2 , 3 , 4))) {
                $this->echoJson(array() , 2 , "请正确先择指定用户方式");
            }
            if ((empty($_POST['platform']) || !in_array($_POST['platform'] , array(1 , 2))) && $_POST['user_1'] == 2 && in_array($_POST['user_2'] , array(1 , 2))) {
                $this->echoJson(array() , 3 , "请正确先择指定平台");
            }
            if (empty($_POST['project_id']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 1) {
                $this->echoJson(array() , 4 , "请输入项目ID");
            }
            if (empty($_POST['deal_id']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 2) {
                $this->echoJson(array() , 5 , "请输入借款编号");
            }
            if (empty($_POST['user_id']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 3) {
                $this->echoJson(array() , 6 , "请输入用户ID");
            }
            if (empty($_POST['user_id_file']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 4) {
                $this->echoJson(array() , 7 , "请上传用户ID");
            }
            if (empty($_POST['title'])) {
                $this->echoJson(array() , 8 , "请输入标题");
            }
            // if (empty($_POST['abstract'])) {
            //     $this->echoJson(array() , 9 , "请输入摘要");
            // }
            if (empty($_POST['content'])) {
                $this->echoJson(array() , 10 , "请输入内容");
            }
            $time = time();
            if (!empty($_POST['start_time'])) {
                $start_time = strtotime($_POST['start_time']);
            } else {
                $start_time = $time;
            }
            if ($start_time < $time) {
                $this->echoJson(array() , 11 , "发布时间不可早于当前时间");
            }
            $title       = trim($_POST['title']);
            $abstract    = trim($_POST['abstract']);
            $content     = $_POST['content'];
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $platform    = 0;
            if ($_POST['user_1'] == 1) { // 全部用户                
                $sql        = "SELECT COUNT(id) AS count FROM firstp2p_user WHERE is_online = 1 ";
                $count_user = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($count_user == 0) {
                    $this->echoJson(array() , 12 , "未查询到任何在途用户");
                }
            } else if ($_POST['user_1'] == 2) { // 指定用户

                if ($_POST['user_2'] == 1) { // 项目ID

                    if ($_POST['platform'] == 1) {
                        $model = Yii::app()->fdb;
                        $platform = 1;
                    } else if ($_POST['platform'] == 2) {
                        $model = Yii::app()->phdb;
                        $platform = 2;
                    }
                    $project_id = trim($_POST['project_id']);
                    if (!is_numeric(substr($project_id , 0 , 1))) {
                        $this->echoJson(array() , 12 , "项目ID开头格式错误");
                    }
                    if (!is_numeric(substr($project_id , -1 , 1))) {
                        $this->echoJson(array() , 13 , "项目ID结尾格式错误");
                    }
                    $project_id_arr = explode(',' , $project_id);
                    foreach ($project_id_arr as $key => $value) {
                        if (!is_numeric($value)) {
                            $this->echoJson(array() , 14 , "项目ID格式错误：{$value}");
                        }
                    }
                    $project_id_str = "'".implode("','" , $project_id_arr)."'";
                    $sql = "SELECT DISTINCT deal_id FROM ag_wx_stat_repay WHERE project_id IN ($project_id_str) AND repay_status = 0 AND is_zdx = 0 ";
                    $deal_id_arr = $model->createCommand($sql)->queryColumn();
                    if ($deal_id_arr) {
                        $deal_id_str = implode(',' , $deal_id_arr);
                        $sql = "SELECT DISTINCT user_id FROM firstp2p_deal_load WHERE deal_id IN ({$deal_id_str}) AND status = 1 ";
                        $user_id_arr = $model->createCommand($sql)->queryColumn();
                    }
                    if (empty($user_id_arr)) {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }

                } else if ($_POST['user_2'] == 2) { // 借款编号

                    if ($_POST['platform'] == 1) {
                        $model = Yii::app()->fdb;
                        $platform = 1;
                    } else if ($_POST['platform'] == 2) {
                        $model = Yii::app()->phdb;
                        $platform = 2;
                    }
                    $deal_id = trim($_POST['deal_id']);
                    if (!is_numeric(substr($deal_id , 0 , 1))) {
                        $this->echoJson(array() , 12 , "借款编号开头格式错误");
                    }
                    if (!is_numeric(substr($deal_id , -1 , 1))) {
                        $this->echoJson(array() , 13 , "借款编号结尾格式错误");
                    }
                    $deal_id_arr = explode(',' , $deal_id);
                    foreach ($deal_id_arr as $key => $value) {
                        if (!is_numeric($value)) {
                            $this->echoJson(array() , 14 , "借款编号格式错误：{$value}");
                        }
                    }
                    $deal_id_str = "'".implode("','" , $deal_id_arr)."'";
                    $sql = "SELECT DISTINCT user_id FROM firstp2p_deal_load WHERE deal_id IN ({$deal_id_str}) AND status = 1 ";
                    $user_id_arr = $model->createCommand($sql)->queryColumn();
                    if (empty($user_id_arr)) {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }

                } else if ($_POST['user_2'] == 3) { // 用户ID

                    $user_id = trim($_POST['user_id']);
                    if (!is_numeric(substr($user_id , 0 , 1))) {
                        $this->echoJson(array() , 12 , "用户ID开头格式错误");
                    }
                    if (!is_numeric(substr($user_id , -1 , 1))) {
                        $this->echoJson(array() , 13 , "用户ID结尾格式错误");
                    }
                    $user_id_res = explode(',' , $user_id);
                    foreach ($user_id_res as $key => $value) {
                        if (!is_numeric($value)) {
                            $this->echoJson(array() , 14 , "用户ID格式错误：{$value}");
                        }
                    }
                    $user_id_str = "'".implode("','" , $user_id_res)."'";
                    $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) AND is_online = 1 ";
                    $user_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if (!empty($user_id_arr)) {
                        foreach ($user_id_res as $key => $value) {
                            if (!in_array($value , $user_id_arr)) {
                                $this->echoJson(array() , 15 , "错误的在途用户ID：{$value}");
                            }
                        }
                    } else {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }

                } else if ($_POST['user_2'] == 4) { // 上传文件

                    $file_address = './'.trim($_POST['user_id_file']);
                    if (!is_file($file_address)) {
                        $this->echoJson(array() , 16 , "上传文件地址错误");
                    }
                    include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
                    include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
                    $xlsPath   = $file_address;
                    $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
                    $xlsReader->setReadDataOnly(true);
                    $xlsReader->setLoadSheetsOnly(true);
                    $Sheets = $xlsReader->load($xlsPath);
                    $Rows   = $Sheets->getSheet(0)->getHighestRow();
                    $data   = $Sheets->getSheet(0)->toArray();
                    if ($Rows < 2) {
                        $this->echoJson(array() , 17 , "上传的文件中无数据");
                    }
                    unset($data[0]);
                    if ($Rows > 10001) {
                        return $this->actionError('上传的文件中数据超过一万行' , 5);
                    }
                    foreach ($data as $key => $value) {
                        if (!is_numeric($value[0])) {
                            $this->echoJson(array() , 14 , "用户ID格式错误：{$value[0]}");
                        }
                        $user_id_res[] = $value[0];
                    }
                    $user_id_str = "'".implode("','" , $user_id_res)."'";
                    $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) AND is_online = 1  ";
                    $user_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if (!empty($user_id_arr)) {
                        foreach ($user_id_res as $key => $value) {
                            if (!in_array($value , $user_id_arr)) {
                                $this->echoJson(array() , 15 , "错误的在途用户ID：{$value}");
                            }
                        }
                    } else {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }
                }
            }
            $count       = count($user_id_arr);
            $page        = ceil($count / 1000);
            $user_id_sql = array();
            $model       = Yii::app()->fdb;
            if ($_POST['user_1'] == 1) {
                $total_user = $count_user;
            } else if ($_POST['user_1'] == 2) {
                $total_user = $count;
            }
            $model->beginTransaction();
            $sql = "INSERT INTO xf_message (title , abstract , content , add_user_id , add_time , add_ip , start_time , user_scope , type , platform , project_id , deal_id , user_id , upload_file , status , total_user) VALUES ('{$title}' , '{$abstract}' , '{$content}' , {$add_user_id} , {$time} , '{$add_ip}' , {$start_time} , {$_POST['user_1']} , {$_POST['user_2']} , {$platform} , '{$project_id}' , '{$deal_id}' , '{$user_id}' , '{$_POST['user_id_file']}' , 1 , {$total_user}) ";
            $add_message = $model->createCommand($sql)->execute();
            $message_id  = $model->getLastInsertID();
            $add_detail  = true;
            if ($_POST['user_1'] == 2) {
                for ($i = 0; $i < $page; $i++) {
                    $temp = array();
                    for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                        if (!empty($user_id_arr[$j])) {
                            $temp[] = "({$message_id} , {$user_id_arr[$j]} , 2)";
                        }
                    }
                    $user_id_sql[] = implode(',' , $temp);
                }
                foreach ($user_id_sql as $key => $value) {
                    $sql = "INSERT INTO xf_message_detail (message_id , user_id , status) VALUES {$value} ";
                    $add = $model->createCommand($sql)->execute();
                    if (!$add) {
                        $add_detail = false;
                    }
                }
            } else {
                $sql = "INSERT INTO xf_message_detail (message_id) VALUES ({$message_id}) ";
                $add_detail = $model->createCommand($sql)->execute();
            }
            if ($add_message && $add_detail) {
                $model->commit();
                $this->echoJson(array() , 0 , "新增消息成功");
            } else {
                $model->rollback();
                $this->echoJson(array() , 8 , "新增消息失败");
            }
        }

        return $this->renderPartial('AddMessage' , array());
    }

    /**
     * 下载用户ID模板
     */
    public function actionDownloadUserIdTemplate()
    {
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
        $objPHPExcel = new PHPExcel();
        // 设置当前的sheet
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('第一页');

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

        $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
        $name = '新增消息 上传用户ID '.date("Y年m月d日 H时i分s秒" , time());

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$name.'.xls"');
        header("Content-Transfer-Encoding:binary");

        $objWriter->save('php://output');
        exit;
    }

    /**
     * 编辑消息
     */
    public function actionEditMessage()
    {
        if (!empty($_POST)) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , "ID格式错误");
            }
            $id = intval($_POST['id']);
            $sql = "SELECT * FROM xf_message WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 2 , "请输入正确的ID");
            }
            $time = time();
            if ($old['start_time'] <= $time) {
                $this->echoJson(array() , 2 , "此消息已经发布,不可编辑!");
            }
            if (empty($_POST['user_1']) || !in_array($_POST['user_1'] , array(1 , 2))) {
                $this->echoJson(array() , 1 , "请正确先择推送用户范围");
            }
            if (empty($_POST['user_2']) || !in_array($_POST['user_2'] , array(1 , 2 , 3 , 4))) {
                $this->echoJson(array() , 2 , "请正确先择指定用户方式");
            }
            if ((empty($_POST['platform']) || !in_array($_POST['platform'] , array(1 , 2))) && $_POST['user_1'] == 2 && in_array($_POST['user_2'] , array(1 , 2))) {
                $this->echoJson(array() , 3 , "请正确先择指定平台");
            }
            if (empty($_POST['project_id']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 1) {
                $this->echoJson(array() , 4 , "请输入项目ID");
            }
            if (empty($_POST['deal_id']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 2) {
                $this->echoJson(array() , 5 , "请输入借款编号");
            }
            if (empty($_POST['user_id']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 3) {
                $this->echoJson(array() , 6 , "请输入用户ID");
            }
            if (empty($_POST['user_id_file']) && $_POST['user_1'] == 2 && $_POST['user_2'] == 4) {
                $this->echoJson(array() , 7 , "请上传用户ID");
            }
            if (empty($_POST['title'])) {
                $this->echoJson(array() , 8 , "请输入标题");
            }
            // if (empty($_POST['abstract'])) {
            //     $this->echoJson(array() , 9 , "请输入摘要");
            // }
            if (empty($_POST['content'])) {
                $this->echoJson(array() , 10 , "请输入内容");
            }
            if (!empty($_POST['start_time'])) {
                $start_time = strtotime($_POST['start_time']);
            } else {
                $start_time = $time;
            }
            if ($start_time < $time) {
                $this->echoJson(array() , 11 , "发布时间不可早于当前时间");
            }
            $title       = trim($_POST['title']);
            $abstract    = trim($_POST['abstract']);
            $content     = $_POST['content'];
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $platform    = 0;
            if ($_POST['user_1'] == 1) { // 全部用户                
                $sql        = "SELECT COUNT(id) AS count FROM firstp2p_user WHERE is_online = 1 ";
                $count_user = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($count_user == 0) {
                    $this->echoJson(array() , 12 , "未查询到任何在途用户");
                }
            } else if ($_POST['user_1'] == 2) { // 指定用户

                if ($_POST['user_2'] == 1) { // 项目ID

                    if ($_POST['platform'] == 1) {
                        $model = Yii::app()->fdb;
                        $platform = 1;
                    } else if ($_POST['platform'] == 2) {
                        $model = Yii::app()->phdb;
                        $platform = 2;
                    }
                    $project_id = trim($_POST['project_id']);
                    if (!is_numeric(substr($project_id , 0 , 1))) {
                        $this->echoJson(array() , 12 , "项目ID开头格式错误");
                    }
                    if (!is_numeric(substr($project_id , -1 , 1))) {
                        $this->echoJson(array() , 13 , "项目ID结尾格式错误");
                    }
                    $project_id_arr = explode(',' , $project_id);
                    foreach ($project_id_arr as $key => $value) {
                        if (!is_numeric($value)) {
                            $this->echoJson(array() , 14 , "项目ID格式错误：{$value}");
                        }
                    }
                    $project_id_str = "'".implode("','" , $project_id_arr)."'";
                    $sql = "SELECT DISTINCT deal_id FROM ag_wx_stat_repay WHERE project_id IN ($project_id_str) AND repay_status = 0 AND is_zdx = 0 ";
                    $deal_id_arr = $model->createCommand($sql)->queryColumn();
                    if ($deal_id_arr) {
                        $deal_id_str = implode(',' , $deal_id_arr);
                        $sql = "SELECT DISTINCT user_id FROM firstp2p_deal_load WHERE deal_id IN ({$deal_id_str}) AND status = 1 ";
                        $user_id_arr = $model->createCommand($sql)->queryColumn();
                    }
                    if (empty($user_id_arr)) {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }

                } else if ($_POST['user_2'] == 2) { // 借款编号

                    if ($_POST['platform'] == 1) {
                        $model = Yii::app()->fdb;
                        $platform = 1;
                    } else if ($_POST['platform'] == 2) {
                        $model = Yii::app()->phdb;
                        $platform = 2;
                    }
                    $deal_id = trim($_POST['deal_id']);
                    if (!is_numeric(substr($deal_id , 0 , 1))) {
                        $this->echoJson(array() , 12 , "借款编号开头格式错误");
                    }
                    if (!is_numeric(substr($deal_id , -1 , 1))) {
                        $this->echoJson(array() , 13 , "借款编号结尾格式错误");
                    }
                    $deal_id_arr = explode(',' , $deal_id);
                    foreach ($deal_id_arr as $key => $value) {
                        if (!is_numeric($value)) {
                            $this->echoJson(array() , 14 , "借款编号格式错误：{$value}");
                        }
                    }
                    $deal_id_str = "'".implode("','" , $deal_id_arr)."'";
                    $sql = "SELECT DISTINCT user_id FROM firstp2p_deal_load WHERE deal_id IN ({$deal_id_str}) AND status = 1 ";
                    $user_id_arr = $model->createCommand($sql)->queryColumn();
                    if (empty($user_id_arr)) {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }

                } else if ($_POST['user_2'] == 3) { // 用户ID

                    $user_id = trim($_POST['user_id']);
                    if (!is_numeric(substr($user_id , 0 , 1))) {
                        $this->echoJson(array() , 12 , "用户ID开头格式错误");
                    }
                    if (!is_numeric(substr($user_id , -1 , 1))) {
                        $this->echoJson(array() , 13 , "用户ID结尾格式错误");
                    }
                    $user_id_res = explode(',' , $user_id);
                    foreach ($user_id_res as $key => $value) {
                        if (!is_numeric($value)) {
                            $this->echoJson(array() , 14 , "用户ID格式错误：{$value}");
                        }
                    }
                    $user_id_str = "'".implode("','" , $user_id_res)."'";
                    $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) AND is_online = 1 ";
                    $user_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if (!empty($user_id_arr)) {
                        foreach ($user_id_res as $key => $value) {
                            if (!in_array($value , $user_id_arr)) {
                                $this->echoJson(array() , 15 , "错误的在途用户ID：{$value}");
                            }
                        }
                    } else {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }

                } else if ($_POST['user_2'] == 4) { // 上传文件

                    $file_address = './'.trim($_POST['user_id_file']);
                    if (!is_file($file_address)) {
                        $this->echoJson(array() , 16 , "上传文件地址错误");
                    }
                    include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
                    include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
                    $xlsPath   = $file_address;
                    $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
                    $xlsReader->setReadDataOnly(true);
                    $xlsReader->setLoadSheetsOnly(true);
                    $Sheets = $xlsReader->load($xlsPath);
                    $Rows   = $Sheets->getSheet(0)->getHighestRow();
                    $data   = $Sheets->getSheet(0)->toArray();
                    if ($Rows < 2) {
                        $this->echoJson(array() , 17 , "上传的文件中无数据");
                    }
                    unset($data[0]);
                    if ($Rows > 10001) {
                        return $this->actionError('上传的文件中数据超过一万行' , 5);
                    }
                    foreach ($data as $key => $value) {
                        if (!is_numeric($value[0])) {
                            $this->echoJson(array() , 14 , "用户ID格式错误：{$value[0]}");
                        }
                        $user_id_res[] = $value[0];
                    }
                    $user_id_str = "'".implode("','" , $user_id_res)."'";
                    $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) AND is_online = 1  ";
                    $user_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if (!empty($user_id_arr)) {
                        foreach ($user_id_res as $key => $value) {
                            if (!in_array($value , $user_id_arr)) {
                                $this->echoJson(array() , 15 , "错误的在途用户ID：{$value}");
                            }
                        }
                    } else {
                        $this->echoJson(array() , 12 , "未查询到任何在途用户");
                    }
                }
            }
            $count       = count($user_id_arr);
            $page        = ceil($count / 1000);
            $user_id_sql = array();
            $model       = Yii::app()->fdb;
            if ($_POST['user_1'] == 1) {
                $total_user = $count_user;
            } else if ($_POST['user_1'] == 2) {
                $total_user = $count;
            }
            $model->beginTransaction();
            $sql = "UPDATE xf_message SET title = '{$title}' , abstract = '{$abstract}' , content = '{$content}' , operation_user_id = {$add_user_id} , operation_time = {$time} , operation_ip = '{$add_ip}' , start_time = {$start_time} , user_scope = {$_POST['user_1']} , type = {$_POST['user_2']} , platform = {$platform} , project_id = '{$project_id}' , deal_id = '{$deal_id}' , user_id = '{$user_id}' , upload_file = '{$_POST['user_id_file']}' , total_user = {$total_user} WHERE id = {$old['id']} ";
            $add_message = $model->createCommand($sql)->execute();
            $sql         = "DELETE FROM xf_message_detail WHERE message_id = {$old['id']} ";
            $delete      = $model->createCommand($sql)->execute();
            $add_detail  = true;
            if ($_POST['user_1'] == 2) {
                for ($i = 0; $i < $page; $i++) {
                    $temp = array();
                    for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                        if (!empty($user_id_arr[$j])) {
                            $temp[] = "({$old['id']} , {$user_id_arr[$j]} , 2)";
                        }
                    }
                    $user_id_sql[] = implode(',' , $temp);
                }
                foreach ($user_id_sql as $key => $value) {
                    $sql = "INSERT INTO xf_message_detail (message_id , user_id , status) VALUES {$value} ";
                    $add = $model->createCommand($sql)->execute();
                    if (!$add) {
                        $add_detail = false;
                    }
                }
            } else {
                $sql = "INSERT INTO xf_message_detail (message_id) VALUES ({$message_id}) ";
                $add_detail = $model->createCommand($sql)->execute();
            }
            if ($add_message && $add_detail) {
                $model->commit();
                $this->echoJson(array() , 0 , "保存消息成功");
            } else {
                $model->rollback();
                $this->echoJson(array() , 8 , "保存消息失败");
            }
        }

        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误' , 1);
            }
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM xf_message WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID' , 2);
            }
            if ($res['start_time'] <= time()) {
                return $this->actionError('此消息已经发布,不可编辑!' , 3);
            }
            $res['start_time'] = date('Y-m-d H:i:s' , $res['start_time']);
            if (!empty($res['upload_file'])) {
                $res['basename'] = basename('./'.$res['upload_file']);
            } else {
                $res['basename'] = '';
            }

            return $this->renderPartial('EditMessage' , array('res' => $res));
        }
    }

    /**
     * 消息详情
     */
    public function actionMessageInfo()
    {
        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误' , 1);
            }
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM xf_message WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID' , 2);
            }
            $res['start_time'] = date('Y-m-d H:i:s' , $res['start_time']);
            if (!empty($res['upload_file'])) {
                $res['basename'] = basename('./'.$res['upload_file']);
            } else {
                $res['basename'] = '';
            }

            return $this->renderPartial('MessageInfo' , array('res' => $res , 'user_id_str'=> $user_id_str));
        }
    }

    /**
     * 撤回消息
     */
    public function actionSetMessage()
    {
        if (!empty($_POST['id'])) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , "ID格式错误");
            }
            $id = intval($_POST['id']);
            $sql = "SELECT * FROM xf_message WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 2 , "请输入正确的ID");
            }
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE xf_message SET operation_user_id = {$add_user_id} , operation_time = {$time} , operation_ip = '{$add_ip}' , status = 2 WHERE id = {$old['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if ($result) {
                $this->echoJson(array() , 0 , "操作成功");
            } else {
                $this->echoJson(array() , 3 , "操作失败");
            }
        }
    }

    /**
     * 意见反馈列表
     */
    public function actionFeedbackList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            $time  = time();
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 校验回复状态
            if (!empty($_POST['status'])) {
                $s      = trim($_POST['status']);
                $where .= " AND status = '{$s}' ";
            }
            // 校验提交时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND add_time <= {$end} ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $m      = trim($_POST['mobile']);
                $where .= " AND user_mobile = '{$m}' ";
            }
            // 校验内容关键词
            if (!empty($_POST['content'])) {
                $m      = trim($_POST['content']);
                $where .= " AND content LIKE '%{$m}%' ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(id) AS count FROM xf_feedback {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_feedback {$where} ORDER BY status ASC , id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList    = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            $edit_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Message/FeedbackInfo') || empty($authList)) {
                $info_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/EditFeedback') || empty($authList)) {
                $edit_status = 1;
            }
            $status = array(1 => '待回复' , 2 => '处理中' , 3 => '已回复');
            foreach ($list as $key => $value) {
                $value['status_name']    = $status[$value['status']];
                $value['add_time']       = date('Y-m-d H:i:s' , $value['add_time']);
                if ($value['operation_time'] > 0) {
                    $value['operation_time'] = date('Y-m-d H:i:s' , $value['operation_time']);
                } else {
                    $value['operation_time'] = '——';
                }
                $value['info_status']    = $info_status;
                if ($value['status'] != 3) {
                    $value['edit_status'] = $edit_status;
                } else {
                    $value['edit_status'] = 0;
                }
                $value['user_real_name'] = $this->strEncrypt($value['user_real_name'] , 1 , 1);
                $value['user_mobile']    = $this->strEncrypt($value['user_mobile'] , 3 , 4);
                $listInfo[] = $value;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        $batch_edit    = 0;
        if (!empty($authList) && strstr($authList,'/user/Message/Feedback2CSV') || empty($authList)) {
            $daochu_status = 1;
        }
        if (!empty($authList) && strstr($authList,'/user/Message/batchEditFeedback') || empty($authList)) {
            $batch_edit = 1;
        }
        return $this->renderPartial('FeedbackList' , array('daochu_status' => $daochu_status , 'batch_edit' => $batch_edit));
    }

    /**
     * 回复意见反馈
     */
    public function actionEditFeedback()
    {
        if (!empty($_POST)) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , "ID格式错误");
            }
            $id = intval($_POST['id']);
            $sql = "SELECT * FROM xf_feedback WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 2 , "请输入正确的ID");
            }
            $time = time();
            if (empty($_POST['status']) || !in_array($_POST['status'] , array(2 , 3))) {
                $this->echoJson(array() , 3 , "请正确选择回复状态");
            }
            if (empty($_POST['re_content']) && $_POST['status'] == 3) {
                $this->echoJson(array() , 4 , "请输入回复内容");
            }
            $status      = intval($_POST['status']);
            $re_content  = $_POST['re_content'];
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE xf_feedback SET status = {$status} , re_content = '{$re_content}' , operation_user_id = {$add_user_id} , operation_time = {$time} , operation_ip = '{$add_ip}' , re_status = 2 WHERE id = {$old['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if ($result) {
                $this->echoJson(array() , 0 , "回复成功");
            } else {
                $this->echoJson(array() , 5 , "回复失败");
            }
        }

        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误' , 1);
            }
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM xf_feedback WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID' , 2);
            }
            if ($res['status'] == 3) {
                return $this->actionError('此意见反馈已经回复' , 2);
            }
            $res['add_time'] = date('Y-m-d H:i:s' , $res['add_time']);
            if ($res['operation_user_id']) {
                $sql = "SELECT id , realname FROM itz_user WHERE id = {$res['operation_user_id']} ";
                $operation = Yii::app()->db->createCommand($sql)->queryRow();
                if ($operation) {
                    $res['operation_user'] = $operation['realname'];
                } else {
                    $res['operation_user'] = '——';
                }
            } else {
                $res['operation_user'] = '——';
            }

            return $this->renderPartial('EditFeedback' , array('res' => $res));
        }
    }

    /**
     * 意见反馈详情
     */
    public function actionFeedbackInfo()
    {
        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误' , 1);
            }
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM xf_feedback WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID' , 2);
            }
            $res['add_time'] = date('Y-m-d H:i:s' , $res['add_time']);
            if ($res['operation_user_id']) {
                $sql = "SELECT id , realname FROM itz_user WHERE id = {$res['operation_user_id']} ";
                $operation = Yii::app()->db->createCommand($sql)->queryRow();
                if ($operation) {
                    $res['operation_user'] = $operation['realname'];
                } else {
                    $res['operation_user'] = '——';
                }
            } else {
                $res['operation_user'] = '——';
            }

            return $this->renderPartial('FeedbackInfo' , array('res' => $res));
        }
    }

    /**
     * 意见反馈 导出
     */
    public function actionFeedback2CSV()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            $model = Yii::app()->fdb;
            $time  = time();
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            if (empty($_GET['mobile']) && empty($_GET['status']) && empty($_GET['start']) && empty($_GET['end']) && empty($_GET['content'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验回复状态
            if (!empty($_GET['status'])) {
                $s      = trim($_GET['status']);
                $where .= " AND status = '{$s}' ";
            }
            // 校验提交时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start']);
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end']);
                $where .= " AND add_time <= {$end} ";
            }
            // 校验手机号
            if (!empty($_GET['mobile'])) {
                $m      = trim($_GET['mobile']);
                $where .= " AND user_mobile = '{$m}' ";
            }
            // 校验内容关键词
            if (!empty($_GET['content'])) {
                $m      = trim($_GET['content']);
                $where .= " AND content LIKE '%{$m}%' ";
            }
            // 查询数据
            $sql  = "SELECT * FROM xf_feedback {$where} ORDER BY status ASC , id DESC ";
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            
            $status = array(1 => '待回复' , 2 => '处理中' , 3 => '已回复');

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , 'ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '内容');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '回复状态');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '用户姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '用户手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '提交时间');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '回复内容');

            foreach ($list as $key => $value) {
                $value['status_name'] = $status[$value['status']];
                $value['add_time']    = date('Y-m-d H:i:s' , $value['add_time']);

                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['content']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['status_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['user_real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['user_mobile'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['add_time'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['re_content']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '意见反馈 '.date("Y年m月d日 H时i分s秒" , time());
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
        }
    }

    /**
     * 批量回复意见反馈
     */
    public function actionbatchEditFeedback()
    {
        if (!empty($_FILES)) {
            $file  = $_FILES['template'];
            $types = array('xls');
            if ($file['error'] != 0) {
                switch ($file['error']) {
                    case 1:
                        return $this->actionError('上传的xls文件超过了服务器限制' , 5);
                        break;
                    case 2:
                        return $this->actionError('上传的xls文件超过了脚本限制' , 5);
                        break;
                    case 3:
                        return $this->actionError('xls文件只有部分被上传' , 5);
                        break;
                    case 4:
                        return $this->actionError('没有xls文件被上传' , 5);
                        break;
                    case 6:
                        return $this->actionError('找不到临时文件夹' , 5);
                        break;
                    case 7:
                        return $this->actionError('xls文件写入失败' , 5);
                        break;
                    default:
                        return $this->actionError('xls文件上传发生未知错误' , 5);
                        break;
                }
            }
            $name      = $file['name'];
            $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if(!in_array($file_type, $types)){
                return $this->actionError('xls文件类型不匹配' , 5);
            }
            $new_name = time() . rand(10000,99999);
            $dir      = date('Ymd');
            if (!is_dir('./upload/' . $dir)) {
                $mkdir = mkdir('./upload/' . $dir , 0777 , true);
                if (!$mkdir) {
                    return $this->actionError('创建xls文件目录失败' , 5);
                }
            }
            $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
            $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
            if (!$result) {
                return $this->actionError('保存xls文件失败' , 5);
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$new_url;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('xls文件中无数据' , 5);
            }
            if ($Rows > 5001) {
                return $this->actionError('xls文件中的数据超过5千行' , 5);
            }
            unset($data[0]);
            $id_array = array();
            foreach ($data as $key => $value) {
                if ($value[2] == '待回复' || $value[2] == '处理中') {
                    $id_array[$key] = $value[0];
                }
            }
            if (empty($id_array)) {
                return $this->actionError('xls文件中无待回复意见反馈数据' , 5);
            }
            $id_string = implode(',' , $id_array);
            $sql = "SELECT * FROM xf_feedback WHERE status IN (1 , 2) AND id IN ({$id_string}) ";
            $data_res = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (empty($data_res)) {
                return $this->actionError('未查询到待回复意见反馈数据' , 5);
            }
            $data_arr = array();
            foreach ($data_res as $key => $value) {
                $data_arr[$value['id']] = $value;
            }
            $total       = 0;
            $true        = 0;
            $false       = 0;
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $add_ip      = Yii::app()->request->userHostAddress;
            foreach ($id_array as $key => $value) {
                $total++;
                $re_content = trim($data[$key][7]);
                if (!empty($data_arr[$value]) && !empty($re_content)) {
                    $sql = "UPDATE xf_feedback SET status = 3 , re_content = '{$re_content}' , operation_user_id = {$add_user_id} , operation_time = {$time} , operation_ip = '{$add_ip}' , re_status = 2 WHERE id = {$value} ";
                    $res = Yii::app()->fdb->createCommand($sql)->execute();
                    if ($res) {
                        $true++;
                    } else {
                        $false++;
                    }
                } else {
                    $false++;
                }
            }
            unlink('./'.$new_url);
            return $this->actionSuccess("待回复总数：{$total}<br>回复成功总数：{$true}<br>回复失败总数：{$false}<br>" , 10);
        }

        return $this->renderPartial('batchEditFeedback' , array());
    }

    /**
     * 用户手机号修改 列表
     */
    public function actionUserMobile()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            // 条件筛选
            $where = " WHERE type = 1 ";
            // 用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 用户姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND real_name = '{$real_name}' ";
            }
            // 用户证件号
            if (!empty($_POST['idno'])) {
                $idno   = GibberishAESUtil::enc(trim($_POST['idno']), Yii::app()->c->idno_key);
                $where .= " AND idno = '{$idno}' ";
            }
            // 旧手机号
            if (!empty($_POST['old_mobile'])) {
                $old_mobile = GibberishAESUtil::enc(trim($_POST['old_mobile']), Yii::app()->c->idno_key);
                $where     .= " AND old_mobile = '{$old_mobile}' ";
            }
            // 新手机号
            if (!empty($_POST['new_mobile'])) {
                $new_mobile = GibberishAESUtil::enc(trim($_POST['new_mobile']), Yii::app()->c->idno_key);
                $where     .= " AND new_mobile = '{$new_mobile}' ";
            }
            // 审核状态
            if (!empty($_POST['status'])) {
                $t      = intval($_POST['status']);
                $where .= " AND status = '{$t}' ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(id) AS count FROM xf_user_mobile_edit_log {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_user_mobile_edit_log {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList     = \Yii::app()->user->getState('_auth');
            $info_status  = 0;
            $audit_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Message/UserMobileInfo') || empty($authList)) {
                $info_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/auditUserMobile') || empty($authList)) {
                $audit_status = 1;
            }
            $status = array(1 => '待审核' , 2 => '审核通过' , 3 => '审核拒绝');
            foreach ($list as $key => $value) {
                $value['add_time']    = date('Y-m-d H:i:s' , $value['add_time']);
                $value['idno']        = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $value['old_mobile']  = GibberishAESUtil::dec($value['old_mobile'], Yii::app()->c->idno_key);
                if ($value['old_mobile'] == false) {
                    $value['old_mobile'] = '';
                }
                $value['new_mobile']  = GibberishAESUtil::dec($value['new_mobile'], Yii::app()->c->idno_key);
                $value['status_name'] = $status[$value['status']];
                $value['info_status'] = $info_status;
                if ($value['status'] == 1) {
                    $value['audit_status'] = $audit_status;
                } else {
                    $value['audit_status'] = 0;
                }
                $value['add_user_name']   = '';
                $value['audit_user_name'] = '——';
                if ($value['audit_time'] > 0) {
                    $value['audit_time'] = date('Y-m-d H:i:s' , $value['audit_time']);
                } else {
                    $value['audit_time'] = '——';
                }

                $listInfo[]    = $value;
                $user_id_arr[] = $value['add_user_id'];
                $user_id_arr[] = $value['audit_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['add_user_name'] = $user_infos[$value['add_user_id']];
                    if ($value['audit_user_id']) {
                        $listInfo[$key]['audit_user_name'] = $user_infos[$value['audit_user_id']];
                    }
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Message/addUserMobile') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('UserMobile' , array('add_status'=> $add_status));
    }

    /**
     * 用户手机号修改 新增
     */
    public function actionaddUserMobile()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id'])) {
                return $this->actionError('请输入用户ID' , 5);
            }
            if (empty($_POST['real_name'])) {
                return $this->actionError('请输入用户姓名' , 5);
            }
            if (empty($_POST['idno'])) {
                return $this->actionError('请输入用户证件号' , 5);
            }
            if (!empty($_POST['old_mobile'])) {
                $old_mobile = GibberishAESUtil::enc(trim($_POST['old_mobile']), Yii::app()->c->idno_key);
            } else {
                $old_mobile = '';
            }
            if (empty($_POST['new_mobile'])) {
                return $this->actionError('请输入新手机号' , 5);
            }
            $check_mobile = preg_match('/^1[3-9]\d{9}$/' , $_POST['new_mobile']);
            if ($check_mobile === 0) {
                return $this->actionError('新手机号格式错误' , 5);
            }
            $user_id    = trim($_POST['user_id']);
            $real_name  = trim($_POST['real_name']);
            $idno       = GibberishAESUtil::enc(trim($_POST['idno']), Yii::app()->c->idno_key);
            $new_mobile = GibberishAESUtil::enc(trim($_POST['new_mobile']), Yii::app()->c->idno_key);
            // 校验用户信息
            $sql = "SELECT * FROM firstp2p_user WHERE id = {$user_id} AND real_name = '{$real_name}' AND idno = '{$idno}' AND mobile = '{$old_mobile}' AND is_effect = 1 AND is_delete = 0 ";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                return $this->actionError('未查询到此用户信息' , 5);
            }
            // 校验修改申请
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE user_id = {$user_id} AND status = 1 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                return $this->actionError('此用户存在待审核的手机号修改申请' , 5);
            }
            // 校验新手机号
            $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$new_mobile}' AND is_effect = 1 AND is_delete = 0 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                return $this->actionError('此新手机号已被其他用户使用' , 5);
            }
            // 校验修改申请
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$new_mobile}' AND status = 1 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                return $this->actionError('此新手机号存在待审核的手机号修改申请' , 5);
            }
            // 上传照片
            $upload = $this->upload_rar('file');
            if ($upload['code'] !== 0) {
                return $this->actionError($upload['info'] , 5);
            }
            // 上传照片至OSS
            $upload_oss = $this->upload_oss('./'.$upload['data'] , 'user_mobile_edit/' . $upload['new_name']);
            Yii::log('addUserMobile: upload_oss:'.print_r($upload_oss) , 'info');
            if ($upload_oss === false) {
                return $this->actionError('上传压缩文件至OSS失败' , 5);
            } else {
                $photograph = '/user_mobile_edit/' . $upload['new_name'];
            }
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $sql = "INSERT INTO xf_user_mobile_edit_log (user_id , real_name , idno , old_mobile , new_mobile , add_user_id , add_time , status , type , photograph) VALUES ('{$user_id}' , '{$real_name}' , '{$idno}' , '{$old_mobile}' , '{$new_mobile}' , '{$add_user_id}' , {$time} , 1 , 1 , '{$photograph}') ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if ($result) {
                return $this->actionSuccess('新增申请成功' , 3);
            } else {
                return $this->actionError('新增申请失败' , 5);
            }
        }

        return $this->renderPartial('addUserMobile' , array());
    }

    /**
     * 用户手机号修改 详情
     */
    public function actionUserMobileInfo()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 1 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }

            $res['idno']          = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['old_mobile']    = GibberishAESUtil::dec($res['old_mobile'], Yii::app()->c->idno_key);
            $res['new_mobile']    = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
            $res['add_time']      = date('Y-m-d H:i:s' , $res['add_time']);
            $res['add_user_name'] = Yii::app()->db->createCommand("SELECT realname FROM itz_user WHERE id = {$res['add_user_id']} ")->queryScalar();
            if ($res['audit_user_id']) {
                $res['audit_user_name'] = Yii::app()->db->createCommand("SELECT realname FROM itz_user WHERE id = {$res['audit_user_id']} ")->queryScalar();
            } else {
                $res['audit_user_name'] = '——';
            }
            if ($res['audit_time']) {
                $res['audit_time'] = date('Y-m-d H:i:s' , $res['audit_time']);
            } else {
                $res['audit_time'] = '——';
            }
            $status = array(1 => '待审核' , 2 => '审核通过' , 3 => '审核拒绝');
            $res['status_name'] = $status[$res['status']];
            $res['photograph'] = Yii::app()->c->oss_preview_address.$res['photograph'];

            return $this->renderPartial('UserMobileInfo' , array('res' => $res));
        }
    }

    /**
     * 用户手机号修改 审核
     */
    public function actionauditUserMobile()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , '请正确输入ID');
            }
            if (empty($_POST['status']) || !in_array($_POST['status'] , [2, 3])) {
                $this->echoJson(array() , 2 , '请正确输入审核状态');
            }
            if ($_POST['status'] == 3 && empty($_POST['reason'])) {
                $this->echoJson(array() , 3 , '请输入拒绝原因');
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 4 , '申请ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 5 , '此手机号修改申请已完成审核');
            }
            $reason        = trim($_POST['reason']);
            $time          = time();
            $audit_user_id = Yii::app()->user->id;
            $audit_user_id = $audit_user_id ? $audit_user_id : 0 ;
            if ($_POST['status'] == 2) {
                // 审核通过
                $model_a = Yii::app()->fdb;
                $model_b = Yii::app()->offlinedb;
                $model_c = Yii::app()->phdb;
                $model_a->beginTransaction();
                $model_b->beginTransaction();
                $model_c->beginTransaction();

                $sql = "UPDATE xf_user_mobile_edit_log SET status = 2 , audit_user_id = {$audit_user_id} , audit_time = {$time} WHERE id = {$id} ";
                $update_log = $model_a->createCommand($sql)->execute();

                $sql = "UPDATE firstp2p_user SET mobile = '{$res['new_mobile']}' , update_time = {$time} WHERE id = {$res['user_id']} ";
                $update_user = $model_a->createCommand($sql)->execute();

                $update_user_ph = true;
                $sql = "SELECT * FROM firstp2p_user WHERE id = {$res['user_id']} ";
                $user_ph = $model_c->createCommand($sql)->queryRow();
                if ($user_ph && $user_ph['mobile'] != $res['new_mobile']) {
                    $sql = "UPDATE firstp2p_user SET mobile = '{$res['new_mobile']}' , update_time = {$time} WHERE id = {$res['user_id']} ";
                    $update_user_ph = $model_c->createCommand($sql)->execute();
                }

                $update_recharge_withdraw = true;
                $sql = "SELECT * FROM xf_user_recharge_withdraw WHERE user_id = {$res['user_id']} ";
                $recharge_withdraw = $model_a->createCommand($sql)->queryRow();
                if ($recharge_withdraw && $recharge_withdraw['mobile'] != $res['new_mobile']) {
                    $sql = "UPDATE xf_user_recharge_withdraw SET mobile = '{$res['new_mobile']}' WHERE user_id = {$res['user_id']} ";
                    $update_recharge_withdraw = $model_a->createCommand($sql)->execute();
                }

                $update_user_platform = true;
                $sql = "SELECT * FROM offline_user_platform WHERE user_id = {$res['user_id']} ";
                $user_platform = $model_b->createCommand($sql)->queryRow();
                if ($user_platform && $user_platform['phone'] != $res['new_mobile']) {
                    $sql = "UPDATE offline_user_platform SET phone = '{$res['new_mobile']}' WHERE user_id = {$res['user_id']} ";
                    $update_user_platform = $model_b->createCommand($sql)->execute();
                }

                if ($update_log && $update_user && $update_user_ph && $update_recharge_withdraw && $update_user_platform) {
                    $model_a->commit();
                    $model_b->commit();
                    $model_c->commit();

                    $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
                    $smaClass                   = new XfSmsClass();
                    $remind                     = array();
                    $remind['sms_code']         = "change_phone_success";
                    $remind['mobile']           = $mobile;
                    $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("auditUserMobile id:{$res['id']}; change_phone_success error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                    $this->echoJson(array() , 0 , '操作成功');
                } else {
                    $model_a->rollback();
                    $model_b->rollback();
                    $model_c->rollback();
                    $this->echoJson(array() , 6 , '操作失败');
                }

            } else if ($_POST['status'] == 3) {
                // 审核拒绝
                $sql = "UPDATE xf_user_mobile_edit_log SET status = 3 , reason = '{$reason}' , audit_user_id = {$audit_user_id} , audit_time = {$time} WHERE id = {$id} ";
                $result = Yii::app()->fdb->createCommand($sql)->execute();
                if ($result) {
                    $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
                    $smaClass                   = new XfSmsClass();
                    $remind                     = array();
                    $remind['sms_code']         = "change_phone_fail";
                    $remind['mobile']           = $mobile;
                    $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("auditUserMobile id:{$res['id']}; change_phone_fail error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                    $this->echoJson(array() , 0 , '操作成功');
                } else {
                    $this->echoJson(array() , 6 , '操作失败');
                }
            }
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 1 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('此手机号修改申请已完成审核' , 5);
            }
            $res['photograph']    = Yii::app()->c->oss_preview_address.$res['photograph'];
            $res['idno']          = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['old_mobile']    = GibberishAESUtil::dec($res['old_mobile'], Yii::app()->c->idno_key);
            $res['new_mobile']    = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
            $res['add_time']      = date('Y-m-d H:i:s' , $res['add_time']);
            $res['add_user_name'] = Yii::app()->db->createCommand("SELECT realname FROM itz_user WHERE id = {$res['add_user_id']} ")->queryScalar();

            return $this->renderPartial('auditUserMobile' , array('res' => $res));
        }
    }

    public function actioncheckOldMobile()
    {
        $result['user_id']        = 0;
        $result['real_name']      = '';
        $result['idno_res']       = 0;
        $result['old_mobile_res'] = 0;
        if (empty($_POST['idno'])) {
            $this->echoJson($result , 1 , "请输入用户证件号");
        }
        $idno = GibberishAESUtil::enc(trim($_POST['idno']), Yii::app()->c->idno_key);
        if ($_POST['old_mobile'] != '') {
            $old_mobile = GibberishAESUtil::enc(trim($_POST['old_mobile']), Yii::app()->c->idno_key);
        } else if ($_POST['old_mobile'] == '') {
            $old_mobile = '';
        }
        $idno_res = array();
        if (!empty($idno)) {
            $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE idno = '{$idno}' AND is_effect = 1 AND is_delete = 0 ORDER BY id ASC ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                $idno_res = $res;
            }
        }
        $old_mobile_res = array();
        if (!empty($old_mobile)) {
            $sql = "SELECT id , real_name FROM firstp2p_user WHERE mobile = '{$old_mobile}' AND is_effect = 1 AND is_delete = 0 ORDER BY id ASC ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                $old_mobile_res = $res;
            }
        } else if (!empty($idno_res) && $idno_res['mobile'] == '' && $old_mobile == '') {
            $old_mobile_res = $idno_res;
        }
        if ($idno_res['id']) {
            $result['idno_res'] = $idno_res['id'];
        }
        if ($old_mobile_res['id']) {
            $result['old_mobile_res'] = $old_mobile_res['id'];
        }
        if ($idno_res && $old_mobile_res && $idno_res['id'] != $old_mobile_res['id']) {
            $this->echoJson($result , 5 , "用户证件号与旧手机号不匹配");
        }
        if (!empty($idno_res['id']) && !empty($old_mobile_res['id']) && $idno_res['id'] == $old_mobile_res['id']) {
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE user_id = {$idno_res['id']} AND status = 1 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                $this->echoJson($result , 6 , "此用户存在待审核的手机号修改申请");
            } else {
                $result['user_id']   = $idno_res['id'];
                $result['real_name'] = $idno_res['real_name'];
            }
        }
        $this->echoJson($result , 0 , "成功");
    }

    public function actioncheckNewMobile()
    {
        if (!empty($_POST['new_mobile'])) {
            $mobile     = trim($_POST['new_mobile']);
            $check_mobile = preg_match('/^1[3-9]\d{9}$/' , $mobile);
            if ($check_mobile === 0) {
                $this->echoJson(array() , 1 , "新手机号格式错误");
            }
            $new_mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
            $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$new_mobile}' AND is_effect = 1 AND is_delete = 0 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                $this->echoJson(array() , 2 , "此新手机号已被其他用户使用");
            }
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$new_mobile}' AND status = 1 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($res) {
                $this->echoJson(array() , 3 , "此新手机号存在待审核的手机号修改申请");
            }
            $this->echoJson(array() , 0 , "成功");
        }
    }

    /**
     * 上传图片
     * @param name  string  图片名称
     * @return string
     */
    private function upload($name)
    {
        $file  = $_FILES[$name];
        $types = array('image/jpg' , 'image/jpeg' , 'image/png' , 'image/pjpeg' , 'image/gif' , 'image/bmp' , 'image/x-png');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的图片超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的图片超过了脚本显示' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '图片只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有图片被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '图片写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '图片上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $file_type = $file['type'];
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => '图片类型不匹配' , 'data' => '');
        }
        $new_name = date('His' . rand(1000,9999));
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建图片目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.jpg';
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存图片成功' , 'data' => $new_url , 'new_name' => $new_name . '.jpg');
        } else {
            return array('code' => 2009 , 'info' => '保存图片失败' , 'data' => '');
        }
    }

    /**
     * 上传压缩文件
     * @param name  string  压缩文件名称
     * @return array
     */
    private function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的压缩文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的压缩文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '压缩文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有压缩文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '压缩文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '压缩文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => '压缩文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000,99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建压缩文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存压缩文件成功' , 'data' => $new_url , 'new_name' => $new_name . '.' . $file_type);
        } else {
            return array('code' => 2009 , 'info' => '保存压缩文件失败' , 'data' => '');
        }
    }

    /**
     * 用户自主修改手机号 列表
     */
    public function actionUserMobileOneself()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            // 条件筛选
            $where = " WHERE type IN (2 , 3) ";
            // 用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 用户姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND real_name = '{$real_name}' ";
            }
            // 用户证件号
            if (!empty($_POST['idno'])) {
                $idno   = GibberishAESUtil::enc(trim($_POST['idno']), Yii::app()->c->idno_key);
                $where .= " AND idno = '{$idno}' ";
            }
            // 旧手机号
            if (!empty($_POST['old_mobile'])) {
                $old_mobile = GibberishAESUtil::enc(trim($_POST['old_mobile']), Yii::app()->c->idno_key);
                $where     .= " AND old_mobile = '{$old_mobile}' ";
            }
            // 新手机号
            if (!empty($_POST['new_mobile'])) {
                $new_mobile = GibberishAESUtil::enc(trim($_POST['new_mobile']), Yii::app()->c->idno_key);
                $where     .= " AND new_mobile = '{$new_mobile}' ";
            }
            // 审核状态
            if (!empty($_POST['status'])) {
                $s      = intval($_POST['status']);
                $where .= " AND status = '{$s}' ";
            }
            // 类型
            if (!empty($_POST['type'])) {
                $t      = intval($_POST['type']);
                $where .= " AND type = '{$t}' ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(id) AS count FROM xf_user_mobile_edit_log {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_user_mobile_edit_log {$where} ORDER BY status = 1 DESC , id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList     = \Yii::app()->user->getState('_auth');
            $info_status  = 0;
            $audit_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Message/UserMobileInfo') || empty($authList)) {
                $info_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/auditUserMobile') || empty($authList)) {
                $audit_status = 1;
            }
            $status = array(1 => '待审核' , 2 => '审核通过' , 3 => '审核拒绝');
            $type   = array(2 => '旧手机号不可用' , 3 => '旧手机号可用');
            foreach ($list as $key => $value) {
                $value['add_time']    = date('Y-m-d H:i:s' , $value['add_time']);
                $value['idno']        = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $value['old_mobile']  = GibberishAESUtil::dec($value['old_mobile'], Yii::app()->c->idno_key);
                if ($value['old_mobile'] == false) {
                    $value['old_mobile'] = '';
                }
                $value['new_mobile']  = GibberishAESUtil::dec($value['new_mobile'], Yii::app()->c->idno_key);
                $value['status_name'] = $status[$value['status']];
                $value['type_name']   = $type[$value['type']];
                $value['info_status'] = $info_status;
                if ($value['status'] == 1 && $value['type'] == 2) {
                    $value['audit_status'] = $audit_status;
                } else {
                    $value['audit_status'] = 0;
                }
                $value['audit_user_name'] = '——';
                if ($value['audit_time'] > 0) {
                    $value['audit_time'] = date('Y-m-d H:i:s' , $value['audit_time']);
                } else {
                    $value['audit_time'] = '——';
                }

                $listInfo[]    = $value;
                $user_id_arr[] = $value['audit_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    if ($value['audit_user_id']) {
                        $listInfo[$key]['audit_user_name'] = $user_infos[$value['audit_user_id']];
                    }
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('UserMobileOneself' , array());
    }

    /**
     * 用户自主修改手机号 详情
     */
    public function actionUserMobileOneselfInfo()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type IN (2 , 3) ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }

            $res['idno']          = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['old_mobile']    = GibberishAESUtil::dec($res['old_mobile'], Yii::app()->c->idno_key);
            $res['new_mobile']    = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
            $res['add_time']      = date('Y-m-d H:i:s' , $res['add_time']);
            if ($res['audit_user_id']) {
                $res['audit_user_name'] = Yii::app()->db->createCommand("SELECT realname FROM itz_user WHERE id = {$res['audit_user_id']} ")->queryScalar();
            } else {
                $res['audit_user_name'] = '——';
            }
            if ($res['audit_time']) {
                $res['audit_time'] = date('Y-m-d H:i:s' , $res['audit_time']);
            } else {
                $res['audit_time'] = '——';
            }
            $status = array(1 => '待审核' , 2 => '审核通过' , 3 => '审核拒绝');
            $res['status_name']    = $status[$res['status']];
            $res['id_pic_front']   = Yii::app()->c->oss_preview_address.$res['id_pic_front'];
            $res['id_pic_back']    = Yii::app()->c->oss_preview_address.$res['id_pic_back'];
            $res['user_pic_front'] = Yii::app()->c->oss_preview_address.$res['user_pic_front'];
            $res['user_pic_back']  = Yii::app()->c->oss_preview_address.$res['user_pic_back'];

            return $this->renderPartial('UserMobileOneselfInfo' , array('res' => $res));
        }
    }

    /**
     * 用户自主修改手机号 审核
     */
    public function actionauditUserMobileOneself()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , '请正确输入ID');
            }
            if (empty($_POST['status']) || !in_array($_POST['status'] , [2, 3])) {
                $this->echoJson(array() , 2 , '请正确输入审核状态');
            }
            if ($_POST['status'] == 3 && empty($_POST['reason'])) {
                $this->echoJson(array() , 3 , '请输入拒绝原因');
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 2 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 4 , '申请ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 5 , '此手机号修改申请已完成审核');
            }
            $reason        = trim($_POST['reason']);
            $time          = time();
            $audit_user_id = Yii::app()->user->id;
            $audit_user_id = $audit_user_id ? $audit_user_id : 0 ;
            if ($_POST['status'] == 2) {
                // 审核通过
                $model_a = Yii::app()->fdb;
                $model_b = Yii::app()->offlinedb;
                $model_c = Yii::app()->phdb;
                $model_a->beginTransaction();
                $model_b->beginTransaction();
                $model_c->beginTransaction();

                $sql = "UPDATE xf_user_mobile_edit_log SET status = 2 , audit_user_id = {$audit_user_id} , audit_time = {$time} WHERE id = {$id} ";
                $update_log = $model_a->createCommand($sql)->execute();

                $sql = "UPDATE firstp2p_user SET mobile = '{$res['new_mobile']}' , update_time = {$time} WHERE id = {$res['user_id']} ";
                $update_user = $model_a->createCommand($sql)->execute();

                $update_user_ph = true;
                $sql = "SELECT * FROM firstp2p_user WHERE id = {$res['user_id']} ";
                $user_ph = $model_c->createCommand($sql)->queryRow();
                if ($user_ph && $user_ph['mobile'] != $res['new_mobile']) {
                    $sql = "UPDATE firstp2p_user SET mobile = '{$res['new_mobile']}' , update_time = {$time} WHERE id = {$res['user_id']} ";
                    $update_user_ph = $model_c->createCommand($sql)->execute();
                }

                $update_recharge_withdraw = true;
                $sql = "SELECT * FROM xf_user_recharge_withdraw WHERE user_id = {$res['user_id']} ";
                $recharge_withdraw = $model_a->createCommand($sql)->queryRow();
                if ($recharge_withdraw && $recharge_withdraw['mobile'] != $res['new_mobile']) {
                    $sql = "UPDATE xf_user_recharge_withdraw SET mobile = '{$res['new_mobile']}' WHERE user_id = {$res['user_id']} ";
                    $update_recharge_withdraw = $model_a->createCommand($sql)->execute();
                }

                $update_user_platform = true;
                $sql = "SELECT * FROM offline_user_platform WHERE user_id = {$res['user_id']} ";
                $user_platform = $model_b->createCommand($sql)->queryRow();
                if ($user_platform && $user_platform['phone'] != $res['new_mobile']) {
                    $sql = "UPDATE offline_user_platform SET phone = '{$res['new_mobile']}' WHERE user_id = {$res['user_id']} ";
                    $update_user_platform = $model_b->createCommand($sql)->execute();
                }

                if ($update_log && $update_user && $update_user_ph && $update_recharge_withdraw && $update_user_platform) {
                    $model_a->commit();
                    $model_b->commit();
                    $model_c->commit();

                    $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
                    $smaClass                   = new XfSmsClass();
                    $remind                     = array();
                    $remind['sms_code']         = "change_phone_success";
                    $remind['mobile']           = $mobile;
                    $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("auditUserMobileOneself id:{$res['id']}; change_phone_success error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                    $this->echoJson(array() , 0 , '操作成功');
                } else {
                    $model_a->rollback();
                    $model_b->rollback();
                    $model_c->rollback();

                    $this->echoJson(array() , 6 , '操作失败');
                }

            } else if ($_POST['status'] == 3) {
                // 审核拒绝
                $sql = "UPDATE xf_user_mobile_edit_log SET status = 3 , reason = '{$reason}' , audit_user_id = {$audit_user_id} , audit_time = {$time} WHERE id = {$id} ";
                $result = Yii::app()->fdb->createCommand($sql)->execute();
                if ($result) {
                    $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
                    $smaClass                   = new XfSmsClass();
                    $remind                     = array();
                    $remind['sms_code']         = "change_phone_fail";
                    $remind['mobile']           = $mobile;
                    $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("auditUserMobileOneself id:{$res['id']}; change_phone_fail error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                    $this->echoJson(array() , 0 , '操作成功');
                } else {
                    $this->echoJson(array() , 6 , '操作失败');
                }
            }
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 2 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('此手机号修改申请已完成审核' , 5);
            }
            $res['id_pic_front']   = Yii::app()->c->oss_preview_address.$res['id_pic_front'];
            $res['id_pic_back']    = Yii::app()->c->oss_preview_address.$res['id_pic_back'];
            $res['user_pic_front'] = Yii::app()->c->oss_preview_address.$res['user_pic_front'];
            $res['user_pic_back']  = Yii::app()->c->oss_preview_address.$res['user_pic_back'];
            $res['idno']           = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['old_mobile']     = GibberishAESUtil::dec($res['old_mobile'], Yii::app()->c->idno_key);
            $res['new_mobile']     = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
            $res['add_time']       = date('Y-m-d H:i:s' , $res['add_time']);

            return $this->renderPartial('auditUserMobileOneself' , array('res' => $res));
        }
    }

    /**
     * 交易所用户信息审核 列表
     */
    public function actionJYSUserInfoList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            // 条件筛选
            $where = " WHERE type = 4 ";
            // 用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 用户姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND real_name = '{$real_name}' ";
            }
            // 用户证件号
            if (!empty($_POST['idno'])) {
                $idno   = GibberishAESUtil::enc(trim($_POST['idno']), Yii::app()->c->idno_key);
                $where .= " AND idno = '{$idno}' ";
            }
            // 新手机号
            if (!empty($_POST['new_mobile'])) {
                $new_mobile = GibberishAESUtil::enc(trim($_POST['new_mobile']), Yii::app()->c->idno_key);
                $where     .= " AND new_mobile = '{$new_mobile}' ";
            }
            // 审核状态
            if (!empty($_POST['status'])) {
                $s      = intval($_POST['status']);
                $where .= " AND status = '{$s}' ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(id) AS count FROM xf_user_mobile_edit_log {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_user_mobile_edit_log {$where} ORDER BY status = 1 DESC , id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList     = \Yii::app()->user->getState('_auth');
            $info_status  = 0;
            $audit_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Message/JYSUserInfo') || empty($authList)) {
                $info_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/Message/auditJYSUserInfo') || empty($authList)) {
                $audit_status = 1;
            }
            $status = array(1 => '待审核' , 2 => '审核通过' , 3 => '审核拒绝');
            foreach ($list as $key => $value) {
                $value['add_time']     = date('Y-m-d H:i:s' , $value['add_time']);
                $value['idno']         = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $value['new_mobile']   = GibberishAESUtil::dec($value['new_mobile'], Yii::app()->c->idno_key);
                $value['status_name']  = $status[$value['status']];
                $value['info_status']  = $info_status;
                $value['audit_status'] = $audit_status;
                $value['audit_user_name'] = '——';
                if ($value['audit_time'] > 0) {
                    $value['audit_time'] = date('Y-m-d H:i:s' , $value['audit_time']);
                } else {
                    $value['audit_time'] = '——';
                }
                if ($value['user_id'] == 0) {
                    $value['user_id'] = '——';
                }

                $listInfo[]    = $value;
                $user_id_arr[] = $value['audit_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    if ($value['audit_user_id']) {
                        $listInfo[$key]['audit_user_name'] = $user_infos[$value['audit_user_id']];
                    }
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('JYSUserInfoList' , array());
    }

    /**
     * 交易所用户信息审核 详情
     */
    public function actionJYSUserInfo()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 4 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if ($res['user_id'] == 0) {
                $res['user_id'] = '——';
            }
            $res['idno']          = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['new_mobile']    = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
            $res['add_time']      = date('Y-m-d H:i:s' , $res['add_time']);
            if ($res['audit_user_id']) {
                $res['audit_user_name'] = Yii::app()->db->createCommand("SELECT realname FROM itz_user WHERE id = {$res['audit_user_id']} ")->queryScalar();
            } else {
                $res['audit_user_name'] = '——';
            }
            if ($res['audit_time']) {
                $res['audit_time'] = date('Y-m-d H:i:s' , $res['audit_time']);
            } else {
                $res['audit_time'] = '——';
            }
            $status = array(1 => '待审核' , 2 => '审核通过' , 3 => '审核拒绝');
            $res['status_name']        = $status[$res['status']];
            $res['id_pic_front_add']   = Yii::app()->c->oss_preview_address.$res['id_pic_front'];
            $res['id_pic_back_add']    = Yii::app()->c->oss_preview_address.$res['id_pic_back'];
            $res['user_pic_front_add'] = Yii::app()->c->oss_preview_address.$res['user_pic_front'];
            $res['user_pic_back_add']  = Yii::app()->c->oss_preview_address.$res['user_pic_back'];
            $res['contract_pic_add']   = Yii::app()->c->oss_preview_address.$res['contract_pic'];
            $res['evidence_pic_add']   = Yii::app()->c->oss_preview_address.$res['evidence_pic'];

            return $this->renderPartial('JYSUserInfo' , array('res' => $res));
        }
    }

    /**
     * 交易所用户信息审核 审核
     */
    public function actionauditJYSUserInfo()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1 , '请正确输入ID');
            }
            if (empty($_POST['status']) || !in_array($_POST['status'] , [2, 3])) {
                $this->echoJson(array() , 2 , '请正确输入审核状态');
            }
            if ($_POST['status'] == 3 && empty($_POST['reason'])) {
                $this->echoJson(array() , 3 , '请输入拒绝原因');
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 4 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 4 , '申请ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 5 , '此手机号修改申请已完成审核');
            }
            $reason        = trim($_POST['reason']);
            $time          = time();
            $audit_user_id = Yii::app()->user->id;
            $audit_user_id = $audit_user_id ? $audit_user_id : 0 ;
            if ($_POST['status'] == 2) {
                // 审核通过
                $model_a = Yii::app()->fdb;
                $model_b = Yii::app()->offlinedb;
                $model_c = Yii::app()->phdb;
                $model_a->beginTransaction();
                $model_b->beginTransaction();
                $model_c->beginTransaction();

                $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);

                $sql = "INSERT INTO firstp2p_user (user_name ,create_time , update_time , is_effect , is_delete , idno , real_name , mobile , user_type , user_purpose) VALUES ('{$mobile}' , '{$time}' , '{$time}' , 1 , 0 , '{$res['idno']}' , '{$res['real_name']}' , '{$res['new_mobile']}' , 0 , 1) ";
                $add_user = $model_a->createCommand($sql)->execute();
                $user_id  = $model_a->getLastInsertID();

                $sql = "UPDATE xf_user_mobile_edit_log SET user_id = {$user_id} , status = 2 , audit_user_id = {$audit_user_id} , audit_time = {$time} WHERE id = {$id} ";
                $update_log = $model_a->createCommand($sql)->execute();

                $sql = "INSERT INTO firstp2p_user (id , user_name ,create_time , update_time , is_effect , is_delete , idno , real_name , mobile , user_type , user_purpose) VALUES ({$user_id} , '{$mobile}' , '{$time}' , '{$time}' , 1 , 0 , '{$res['idno']}' , '{$res['real_name']}' , '{$res['new_mobile']}' , 0 , 1) ";
                $add_user_ph = $model_c->createCommand($sql)->execute();

                $sql = "UPDATE offline_user_platform SET phone = '{$res['new_mobile']}' WHERE user_id = {$res['user_id']} ";
                $sql = "INSERT INTO offline_user_platform (user_id , platform_id , real_name , phone , idno) VALUES ({$user_id} , 5 , '{$res['real_name']}' , '{$res['new_mobile']}' , '{$res['idno']}') ";
                $add_user_platform = $model_b->createCommand($sql)->execute();

                if ($update_log && $add_user && $add_user_ph && $add_user_platform) {
                    $model_a->commit();
                    $model_b->commit();
                    $model_c->commit();

                    $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
                    $smaClass                   = new XfSmsClass();
                    $remind                     = array();
                    $remind['sms_code']         = "jys_phone_success";
                    $remind['mobile']           = $mobile;
                    $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("auditUserMobileOneself id:{$res['id']}; jys_phone_success error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                    $this->echoJson(array() , 0 , '操作成功');
                } else {
                    $model_a->rollback();
                    $model_b->rollback();
                    $model_c->rollback();

                    $this->echoJson(array() , 6 , '操作失败');
                }

            } else if ($_POST['status'] == 3) {
                // 审核拒绝
                $sql = "UPDATE xf_user_mobile_edit_log SET status = 3 , reason = '{$reason}' , audit_user_id = {$audit_user_id} , audit_time = {$time} WHERE id = {$id} ";
                $result = Yii::app()->fdb->createCommand($sql)->execute();
                if ($result) {
                    $mobile = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
                    $smaClass                   = new XfSmsClass();
                    $remind                     = array();
                    $remind['sms_code']         = "jys_phone_fail";
                    $remind['mobile']           = $mobile;
                    $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("auditUserMobileOneself id:{$res['id']}; jys_phone_fail error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                    $this->echoJson(array() , 0 , '操作成功');
                } else {
                    $this->echoJson(array() , 6 , '操作失败');
                }
            }
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE id = {$id} AND type = 4 ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('申请ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('此手机号修改申请已完成审核' , 5);
            }
            $res['id_pic_front_add']   = Yii::app()->c->oss_preview_address.$res['id_pic_front'];
            $res['id_pic_back_add']    = Yii::app()->c->oss_preview_address.$res['id_pic_back'];
            $res['user_pic_front_add'] = Yii::app()->c->oss_preview_address.$res['user_pic_front'];
            $res['user_pic_back_add']  = Yii::app()->c->oss_preview_address.$res['user_pic_back'];
            $res['contract_pic_add']   = Yii::app()->c->oss_preview_address.$res['contract_pic'];
            $res['evidence_pic_add']   = Yii::app()->c->oss_preview_address.$res['evidence_pic'];
            $res['idno']               = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['new_mobile']         = GibberishAESUtil::dec($res['new_mobile'], Yii::app()->c->idno_key);
            $res['add_time']           = date('Y-m-d H:i:s' , $res['add_time']);

            return $this->renderPartial('auditJYSUserInfo' , array('res' => $res));
        }
    }
}