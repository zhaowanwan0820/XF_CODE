<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
//
use core\service\BonusService;

class BonusTempleteAction extends CommonAction
{
    /**
     * 首页.
     *
     * @access public
     */
    public function index()
    {
        $model = M(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model);
        }
        $list = $this->get('list');
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 新增模板页
     *
     * @access public
     * @return void
     */
    public function add()
    {
        $this->assign('user_group_list', $GLOBALS['sys_config']['TPL_SITE_LIST']);
        $this->display();
    }

    /**
     * 新增模板
     *
     * @access public
     * @return void
     */
    public function insert()
    {
        if (!empty($_FILES['share_icon'])) {
            $file = $_FILES['share_icon'];
            if (empty($file) || $file['error'] != 0) {
                $this->error('分享图标为空！');
            }
            // ImageSizeLimit 红包分享图标限制150KB
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(150/1024, 2),
            );
            $result = uploadFile($uploadFileInfo);

            if (empty($result['aid'])) {
                $this->error('分享图标上传失败！');
            } else {
                $_POST['share_icon'] = $result['full_path'];
            }
        }
        if (!empty($_FILES['bg_image'])) {
            $file = $_FILES['bg_image'];
            if (empty($file) || $file['error'] != 0) {
                $this->error('皮肤为空！');
            }
            // ImageSizeLimit 红包皮肤限制300KB
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(300/1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
            if (empty($result['aid'])) {
                $this->error('皮肤上传失败！');
            } else {
                $_POST['bg_image'] = $result['full_path'];
            }
        }

        $_POST['start_time']  = to_timespan($_POST['start_time']);
        $_POST['end_time']    = to_timespan($_POST['end_time']);
        $_POST['update_time'] = get_gmtime();
        \SiteApp::init()->cache->delete(BonusService::CACHE_PREFIX__BONUS_TEMPLETE . $_POST['site_id']);

        parent::insert();
        $this->redirect(u(MODULE_NAME."/index"));
    }

    /**
     * 编辑页面
     *
     * @access public
     * @return void
     */
    public function update()
    {
        if (!empty($_FILES['share_icon']['tmp_name'])) {
            $file = $_FILES['share_icon'];
            if (empty($file) || $file['error'] != 0) {
                $this->error('分享图标为空！');
            }
            // ImageSizeLimit 红包分享图标限制150KB
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(150/1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
            if (empty($result['aid'])) {
                $this->error('分享图标上传失败！'.$result['errors'][0]);
            } else {
                $_POST['share_icon'] = $result['full_path'];
            }
        }
        if (!empty($_FILES['bg_image']['tmp_name'])) {
            $file = $_FILES['bg_image'];
            if (empty($file) || $file['error'] != 0) {
                $this->error('皮肤为空！');
            }
            // ImageSizeLimit 红包皮肤限制300KB
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(300/1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
            if (empty($result['aid'])) {
                $this->error('皮肤上传失败！'.$result['errors'][0]);
            } else {
                $_POST['bg_image'] = $result['full_path'];
            }
        }

        $_POST['start_time']  = to_timespan($_POST['start_time']);
        $_POST['end_time']    = to_timespan($_POST['end_time']);
        $_POST['update_time'] = get_gmtime();
        \SiteApp::init()->cache->delete(BonusService::CACHE_PREFIX__BONUS_TEMPLETE . $_POST['site_id']);
        parent::update();
    }

    /**
     * 更新
     *
     * @access public
     * @return void
     */
    public function edit()
    {
        $static_host = app_conf('STATIC_HOST');
        $this->assign('image_host',(substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/');
        $this->assign('user_group_list', $GLOBALS['sys_config']['TPL_SITE_LIST']);
        parent::edit();
    }

    /**
     * 格式化数据输出
     *
     * @param mixed $list
     * @access protected
     * @return void
     */
    protected function form_index_list(&$list)
    {
        $bonus_service = new BonusService();
        $sites = $GLOBALS['sys_config']['TPL_SITE_LIST'];
        foreach ($list as &$item) {
            $item['site_id'] = $sites[$item['site_id']];
            $item['status'] = $item['status'] == 1 ? '有效' : '无效';
        }
    }
}
