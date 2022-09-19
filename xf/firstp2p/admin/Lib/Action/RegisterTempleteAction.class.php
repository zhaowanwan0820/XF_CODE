<?php
/**
 * WAP注册登陆模板
 *
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 * @version $Id$
 * @copyright firstp2p, 21 四月, 2016
 */

class RegisterTempleteAction extends CommonAction
{

    /**
     * 上传字段
     */
    private static $uploadFields = array(
        'sign_up_banner' => '注册页banner',
        'sign_in_banner' => '登陆页banner',
        'sign_up_footer' => '注册页footer',
        'sign_in_footer' => '登陆页footer',
    );

    /**
     * 首页.
     *
     * @access public
     */
    public function index()
    {
        $model = M(MODULE_NAME);
        if (!empty($model)) {
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
        $this->display();
    }

    /**
     * 新增模板
     *
     * @access public
     */
    public function insert()
    {
        if (!(new \core\service\CouponService())->checkCoupon($_POST['invite_code'])) { //优惠码校验
            $this->error("邀请码错误！");
            return;
        }
        $this->uploadBanner('sign_up_banner', false);
        $this->uploadBanner('sign_in_banner', false);
        $this->uploadBanner('sign_up_footer', false);
        $this->uploadBanner('sign_in_footer', false);

        $_POST['start_time']  = to_timespan($_POST['start_time']);
        $_POST['end_time']    = to_timespan($_POST['end_time']);
        $_POST['update_time'] = get_gmtime();

        parent::insert();
        $this->redirect(u(MODULE_NAME."/index"));
    }

    /**
     * 编辑页面.
     *
     * @access public
     */
    public function update()
    {
        if (!(new \core\service\CouponService())->checkCoupon($_POST['invite_code'])) { //优惠码校验
            $this->error("邀请码错误！");
            return;
        }
        $this->uploadBanner('sign_up_banner', false);
        $this->uploadBanner('sign_in_banner', false);
        $this->uploadBanner('sign_up_footer', false);
        $this->uploadBanner('sign_in_footer', false);

        $_POST['start_time']  = to_timespan($_POST['start_time']);
        $_POST['end_time']    = to_timespan($_POST['end_time']);
        $_POST['update_time'] = get_gmtime();
        (new \core\service\RegisterTempleteService())->removeCache(addslashes(trim($_POST['const_name'])));
        parent::update();
    }

    /**
     * 更新.
     *
     * @access public
     */
    public function edit()
    {
        $static_host = app_conf('STATIC_HOST');
        $this->assign('image_host', (substr($static_host, 0, 4) == 'http' ? '' : 'http:').$static_host.'/');
        parent::edit();
    }

    /**
     * 获取上传图片的路径.
     *
     * @param mixed $field
     * @param int   $limitSize
     * @access private
     *
     * @return string
     */
    private function uploadBanner($field, $isRequire = false, $limitSize = 300)
    {
        if (empty($_FILES[$field]['tmp_name'])) {
            if ($isRequire) {
                $this->error(self::$uploadFields[$field].'图片为空');
            } else {
                return true;
            }
        }

        $file = $_FILES[$field];
        if (empty($file) || $file['error'] != 0) {
            $this->error(self::$uploadFields[$field].'图片为空');
        }
        // ImageSizeLimit 限制300KB
        $uploadFileInfo = array(
            'file' => $file,
            'isImage' => 1,
            'asAttachment' => 1,
            'limitSizeInMB' => round($limitSize/1024, 2),
        );
        $result = uploadFile($uploadFileInfo);
        if (empty($result['aid'])) {
            $this->error(self::$uploadFields[$field].'上传失败');
        }
        $_POST[$field] = $result['full_path'];
        return true;
    }

    /**
     * 格式化数据输出.
     *
     * @param mixed $list
     * @access protected
     */
    protected function form_index_list(&$list)
    {
        $signUpUrl = 'http://m.firstp2p.com/account/register?type=h5&';
        $signInUrl = 'http://m.firstp2p.com/account/login?type=h5&';
        foreach ($list as &$item) {
            $item['status'] = $item['status'] == 1 ? '有效' : '无效';
            $param = array('from_platform' => $item['const_name']);
            if (!empty($item['invite_code'])) {
                $param['cn'] = $item['invite_code'];
            }
            if ($item['invite_code_type'] == 3) {
                $param['event_cn_hidden'] = 1;
            }
            if ($item['invite_code_type'] == 2) {
                $param['event_cn_lock'] = 1;
            }
            $item['sign_up_url'] = $signUpUrl.http_build_query($param);
            $item['sign_in_url'] = $signInUrl.http_build_query($param);
        }
    }

    /**
     * 验证邀请码是否有效
     *
     * @access public
     * @return boolean
     */
    public function checkInviteCode()
    {
        $result = array();
        $inviteCode = $_REQUEST['invite_code'];
        if ($inviteCode == '') {
            $result['status'] = 1;
        } else {
            $result = (new \core\service\CouponService())->checkCoupon($inviteCode);
            if (empty($result)) {
                $result['status'] = 0;
            } else {
                $result['status'] = 1;
            }
        }
        ajax_return($result);
        return;
    }
}
