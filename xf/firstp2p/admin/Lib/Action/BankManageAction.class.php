<?php
/**
 * 银行卡管理
 * @author caolong@ucfgroup.com
 * @modify wangquniang 2016-1-4
 */
class BankManageAction extends CommonAction{
    public  $rsData = array('total'=>0,'rows'=>array());
    public  $ajaxData = array('code'=>'0000','message'=>'操作成功');
    public  $allowPostfix = array('jpg','jpeg','pjpeg','png');

    public function index() {
        $post['name']       = $this->filterJs($_POST['name']);
        $post['status']     = isset($_POST['status']) && $_POST['status'] !='请选择状态' ? $_POST['status'] : '';
        $post['payment_id'] = isset($_POST['payment_id']) && $_POST['payment_id'] !='请选择快捷支付方式' ? intval($_POST['payment_id']) : '';
        $map                = $this->_search();
        $model              = M('BankCharge');
        $config_list        = array();
        //追加默认参数
        if ($this->get("default_map"))
            $map = array_merge($this->get("default_map"), $map); // 搜索框的值覆盖默认值
        if (method_exists($this, '_filter'))
            $this->_filter($map);
        if(!empty($post['name']))
            $map['name'] = array('like','%'.trim($post['name']).'%');
        if(isset($post['status']) && $post['status'] != 'all')
            $map['status'] = array('eq',$post['status']);
        if(!empty($post['payment_id']) ) {
            $map['payment_id'] = array('eq',$post['payment_id']);
        }
        if($post['payment_id'] === 0 ) {
            $map['payment_id'] = array('eq',$post['payment_id']);
        }
        if (!empty ($model))
            $this->_list($model, $map);
        $list   = M('Payment')->where('is_effect=1')->select();
        if(!empty($list)) {
            foreach ($list as $key=>$val)
                $config_list[$val['id']] = $val['name'];
        }
        $this->assign('post',array('status'=>$post['status'],'name'=>$post['name'],'payment_id'=>$post['payment_id']));
        $this->assign('config_list',$config_list);
        $this->assign('payment_list',$list);
        $this->assign('status_list',array('0'=>'有效','1'=>'无效','all'=>'全部'));
        $this->assign('main_title','银行卡列表');
        $this->display ();
    }

    /**
     * 编辑页面
     */
    public function editor() {
        $id     = intval($_REQUEST['id']);
        $list   = M('Payment')->where('is_effect=1')->select();
        $result = M('BankCharge')->find($id);
        if(!empty($result['img'])) {
            $result['imgName'] = get_attr($result['img'],1, false);
        }
        $this->assign('vo',$result);
        $this->assign('payment_list',$list);
        $this->assign('main_title','银行卡修改');
        $this->display ();
    }

    //删除数据
    public function deleteData() {
        $id = intval($_POST['id']);
        if(!empty($id)) {
            $data['status'] = 1;
            M('BankCharge')->where('id='.$id)->save($data);
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
        echo json_encode($this->ajaxData);
    }

    //删除数据批量
    public function deleteDataList() {
        $ids = $this->filterJs($_POST['ids']);
        if(!empty($ids)) {
            $data['status'] = 1;
            M('BankCharge')->where('id in('.$ids.')')->save($data);
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
        echo json_encode($this->ajaxData);
    }

    //银行卡图片上传
    public function bankinfoImage() {
        $data   = array();
        $file   = $_FILES['fileToUpload'];
        $prefix = $this->getImagePostFix($file['tmp_name']);
        if(!empty($file) && in_array($prefix, $this->allowPostfix)) {
        //TODO markup 银行logo为非隐私图片
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

    //获取银行附属信息
    public function getAuxiliary() {
        $charge_id = intval($_REQUEST['charge_id']);
        if(!empty($charge_id)) {
            $list = M("BankChargeAuxiliary")->where("charge_id=".$charge_id." and status = 0")->select();
            $num = 0;
            if(!empty($list)) {
                foreach ($list as $key=>$val) {
                    $num++;
                    $this->rsData['rows'][] = $val;
                }
            }
            $this->rsData['total'] = $num;
        }
        echo json_encode($this->rsData);
    }

    //保存用户关联数据
    public function saveCharge() {
        $id                 = $_POST['id'];
        $data['name']       = $this->filterJs($_POST['name']);
        $data['img']        = $_POST['img'];
        $data['value']      = $this->filterJs($_POST['value']);
        $data['short_name'] = $this->filterJs($_POST['short_name']);
        $data['payment_id'] = intval($_POST['payment_id']);
        $data['status']     = intval($_POST['status']);
        $data['update_time']= time();
        //后台用户信息
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['admin_id']   = intval($adm_session['adm_id']);

        if(!empty($data['name']) && !empty($data['img']) && !empty($data['value']) && !empty($data['short_name'])) {
            $model = M('BankCharge');
            if(!empty($id)) {
                $data['id'] = $id;
                $bool = $model->save($data);
            }else{
                $data['create_time'] = time();
                $bool = $model->add($data);
                $id   = $bool;
            }
            if(!$bool)
                $this->ajaxData = array('code'=>4002,'message'=>'保存失败');
            else
                $this->ajaxData['message'] = $id;
        }else
            $this->ajaxData = array('code'=>4001,'message'=>'参数不能为空');
        echo json_encode($this->ajaxData);
    }


    //保存银行附属表信息
    public function saveData() {
        if(!empty($_POST['data'])) {
            $data = (array)json_decode($_POST['data']);
          //  print_r($data);exit;
            $model = M("BankChargeAuxiliary");
            $post['charge_id']     = $data['charge_id'];
            $post['category']      = $this->filterJs($data['category']);
            $post['card_type']     = $this->filterJs($data['card_type']);
            $post['one_money']     = $this->filterJs($data['one_money']);
            $post['date_norm']     = $this->filterJs($data['date_norm']);
            if(!empty($post['charge_id'])) {
                if(!empty($post['category']) && !empty($post['card_type'])) {
                    if(intval($data['id']) === 99999999) { //添加
                        $post['create_time'] = time();
                        if(!$model->add($post)) 
                            $this->ajaxData = array('code'=>'4004','message'=>'添加失败');
                    }else{//编辑
                        if(!$model->where('id = '.$data['id'])->save($post))
                            $this->ajaxData = array('code'=>'4003','message'=>'更新失败');
                    }
                }else
                    $this->ajaxData = array('code'=>'4002','message'=>'参数不能为空');
            }else
                $this->ajaxData = array('code'=>'4000','message'=>'银行信息表id丢失');
        }else
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');

        echo json_encode($this->ajaxData);
    }

    //删除银行附属表信息
    public function delChargeAuxiliary() {
        $id = intval($_POST['id']);
        if(!empty($id)) {
            $model = M("BankChargeAuxiliary");
            $data['status'] = 1;
            $model->where('id = '.$id)->save($data);
        }else{
            $this->ajaxData = array('code'=>'4001','message'=>'参数丢失');
        }
        echo json_encode($this->ajaxData);
    }

    //恢复数据
    public function recoverData() {
        $id = intval($_POST['id']);
        if(!empty($id)) {
            $data['status'] = 0;
            M('BankCharge')->where('id='.$id)->save($data);
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
        echo json_encode($this->ajaxData);
    }

    //验证是否重复短标示
    public function checkShortName() {
        $shortName = $this->filterJs($_POST['name']);
        if(!empty($shortName)) {
            $result = M('BankCharge')->where('status=0 and short_name="'.$shortName.'"')->find();
          //  echo M('BankCharge')->getLastSql();
            if(!empty($result))
                $this->ajaxData = array('code'=>4001,'message'=>'重复的短标示');
        }else
            $this->ajaxData = array('code'=>4000,'message'=>'参数缺失');
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
