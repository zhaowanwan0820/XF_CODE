<?php
/**
 * 收款银行管理
 * @author caolong@ucfgroup.com
 * @modify caolong 2014-3-17
 */
class BankAction extends CommonAction{
    public  $rsData = array('total'=>0,'rows'=>array());
    public  $ajaxData = array('code'=>'0000','message'=>'操作成功');
    public  $allowPostfix = array('jpg','jpeg','pjpeg','png');

    public function index() {
        $post['name']       = $this->filterJs($_POST['name']);
        $post['status']     = intval($_POST['status']);
        $map                = $this->_search();
        $model              = M('Bank');
        //追加默认参数
        if ($this->get("default_map"))
            $map = array_merge($this->get("default_map"), $map); // 搜索框的值覆盖默认值
        if (method_exists($this, '_filter'))
            $this->_filter($map);
        if(!empty($post['name']))
            $map['name'] = array('like','%'.trim($post['name']).'%');
       if(!empty($post['status']))
            $map['status'] = array('eq',$post['status']);

        if (!empty ($model))
            $this->_list($model, $map);

        $this->assign('p', (isset($_GET['p']) ? (int)$_GET['p'] : 1));
        $this->assign('post',array('status'=>$post['status'],'name'=>$post['name']));
        $this->assign('status_list',array('0'=>'有效','1'=>'无效'));
        $this->assign('main_title','收款银行管理列表');
        $this->display ();
    }

    /**
     * 编辑页面
     */
    public function editor() {
        $id     = intval($_REQUEST['id']);
        $list   = M('Payment')->where('is_effect=1')->select();
        $result = M('Bank')->find($id);
        $result['imgName'] = $result['logoImgName'] = $result['bgImgName'] = '';
        // 银行logoID
        if(!empty($result['img'])) {
            $result['imgName'] = get_attr($result['img'], 1, false);
        }
        // 银行logoID 原卡中心
        if(!empty($result['logo_id'])) {
            $result['logoImgName'] = get_attr($result['logo_id'], 1, false);
        }
        // 银行背景图ID
        if(!empty($result['bg_id'])) {
            $result['bgImgName'] = get_attr($result['bg_id'], 1, false);
        }
        // 银行iconId
        if(!empty($result['icon_id'])) {
            $result['iconImgName'] = get_attr($result['icon_id'], 1, false);
        }
        // 水印2倍
        if(!empty($result['mask2x'])) {
            $result['mask2xImgName'] = get_attr($result['mask2x'], 1, false);
        }
        // 水印3倍
        if(!empty($result['mask3x'])) {
            $result['mask3xImgName'] = get_attr($result['mask3x'], 1, false);
        }

        $this->assign('p', (isset($_GET['p']) ? (int)$_GET['p'] : 1));
        $this->assign('vo',$result);
        $this->assign('payment_list',$list);
        $this->assign('main_title','银行管理编辑');
        $this->display ();
    }

    //删除数据
    public function deleteData() {
        $id = intval($_POST['id']);
        if(!empty($id)) {
            $data['status'] = 1;
            M('Bank')->where('id='.$id)->save($data);
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
    echo json_encode($this->ajaxData);
    }

    //删除数据批量
    public function deleteDataList() {
        $ids = $this->filterJs($_POST['ids']);
        if(!empty($ids)) {
            $data['status'] = 1;
            M('Bank')->where('id in('.$ids.')')->save($data);
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
        echo json_encode($this->ajaxData);
    }

    //银行卡图片上传
    public function bankinfoImage() {
        $data   = array();
        $fn     = isset($_GET['fn']) ? addslashes($_GET['fn']) : '';
        $file   = isset($_FILES[$fn]) ? $_FILES[$fn] : $_FILES['fileToUpload'];
        if (empty($file['tmp_name'])) {
            $this->ajaxData = array('code'=>'4002', 'message'=>'请选择要上传的文件。');
            echo json_encode($this->ajaxData);
            exit;
        }
        $prefix = $this->getImagePostFix($file['tmp_name']);
        if(!empty($file) && in_array($prefix, $this->allowPostfix)) {
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
            );
            $result = uploadFile($uploadFileInfo);
            if(!empty($result['aid']) && $result['filename']) {
                $data['image_id'] = $result['aid'];
                $data['filename'] = get_attr($result['aid'],1, false);
                $this->ajaxData['message'] = $data;
            }else{
                $this->ajaxData= array('code'=>'4001','message'=>'图片尺寸不能大于1.5M，请重新上传图片');
            }
        }else{
            $this->ajaxData = array('code'=>'4000','message'=>'图片格式仅限JPG、PNG，请重新上传图片');
        }
        echo json_encode($this->ajaxData);
    }

    //银行卡图片删除
    public function bankinfoImageDel() {
        $id   = intval($_POST['id']);
        if(!empty($id)) {
            if(!del_attr($id)) {
                $this->ajaxData = array('code'=>'4000','message'=>'图片删除失败');
            }
        }else{
            $this->ajaxData = array('code'=>'4001','message'=>'图片id不存在');
        }
        echo json_encode($this->ajaxData);
    }

    //通过二进制流 读取文件后缀信息
    private function getImagePostFix($filename) {
        $file     = fopen($filename, "rb");
        $bin      = fread($file, 2); //只读2字节
        fclose($file);
        $strinfo  = @unpack("c2chars", $bin);
        $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
        $filetype = "";
        switch ($typecode){
        case 7790: $filetype = 'exe';  break;
        case 7784: $filetype = 'midi'; break;
        case 8297: $filetype = 'rar';  break;
        case 255216:$filetype = 'jpg'; break;
        case 7173: $filetype = 'gif';  break;
        case 6677: $filetype = 'bmp';  break;
        case 13780:$filetype = 'png';  break;
        default:   $filetype = 'unknown'.$typecode;
        }
        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' )
            return 'jpg';
        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' )
            return 'png';
        return $filetype;
    }


    //保存用户关联数据
    public function saveBank() {
        $id                 = intval($_POST['id']);
        $data['name']       = $this->filterJs($_POST['name']);
        $data['abbreviate_name'] = $this->filterJs($_POST['abbreviate_name']);
        $data['short_name'] = $this->filterJs($_POST['short_name']);
        $data['img']        = (int)$_POST['img'];
        $data['sort']       = intval($_POST['sort']);
        $data['is_rec']     = intval($_POST['is_rec']);
        $data['deposit']    = intval($_POST['deposit']);
        $data['logo_id']    = (int)$_POST['logo_id'];
        $data['bg_id']      = (int)$_POST['bg_id'];
        $data['icon_id']    = (int)$_POST['icon_id'];
        $data['mask2x']    = (int)$_POST['mask2x'];
        $data['mask3x']    = (int)$_POST['mask3x'];
        //后台用户信息
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['admin_id']   = intval($adm_session['adm_id']);

        if(!empty($data['admin_id']) && !empty($data['name']) ) {
            $model = M('Bank');
            if(!empty($id)) {
                $data['id'] = $id;
                $model->save($data);
            }else{
                $data['create_time'] = time();
                $id = $model->add($data);
            }
            $this->ajaxData['message'] = $id;
        }else{
            $this->ajaxData = array('code'=>4001,'message'=>'参数不能为空');
        }
        echo json_encode($this->ajaxData);
    }


    //恢复数据
    public function recoverData() {
        $id = intval($_POST['id']);
        if(!empty($id)) {
            $data['status'] = 0;
            M('Bank')->where('id='.$id)->save($data);
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
        echo json_encode($this->ajaxData);
    }

    //验证是否重复短标示
    private function checkName($name = '') {
        if(!empty($name)) {
            $result = M('Bank')->where('status=0 and name="'.$name.'"')->find();
            if(!empty($result))
                return false;
        }
        return true;
    }

    //替换 js style 内容
    private function filterJs($str='') {
        if(!empty($str)) {
            $pregfind = array("/<script.*>.*<\/script>/siU","/<style.*>.*<\/style>/siU",);
            $pregreplace = array('','', );
            $str = preg_replace($pregfind, $pregreplace, $str);    //filter script/style entirely
        }
        return $str;
    }
}
