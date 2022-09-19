<?php
/**
 * 新版用户登录
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\attachment;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Block;
use libs\vfs\VfsHelper;

class View extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        $file = isset($_GET['file']) ? trim($_GET['file']) : '';
        $userCode = isset($_GET['f']) ? trim($_GET['f']) : '';
        if(empty($file)) {
            return false;
        }

        $path = pathinfo($file);
        // 读取后台设置的cookie，验证是否管理员在浏览
        $_tmpAdminCookie = getGlobalTmpCookie('am_vwpic');
        $adminInfo = json_decode($_tmpAdminCookie, true);
        if(isset($adminInfo['adm_name']) && !empty($adminInfo['adm_name'])) {

            $streamContent = VfsHelper::image($file, true);
            if ($path['extension'] == 'jpg' || $path['extension'] == 'jpeg') {
                header('content-type:image/jpeg');
            } else  {
                header('content-type:application/octet-stream');
                header("Content-Disposition:attachment;filename=". $path['basename']);
            }
        } else {
            $loginInfo = \es_session::get('user_info');
            if(empty($loginInfo))
            {
                return $this->show_error('您无权查看此附件');
            } else {
                // 解析参数里的数据，验证是否图片所属用户在浏览
                $userCodeDecode = aesAuthCode($userCode);
                $userCodeData = explode('|', $userCodeDecode);
                if (empty($userCodeData) || count($userCodeData) != 2 || $userCodeData[1] != $loginInfo['id'])
                {
                    return $this->show_error('您无权查看此附件');
                }
                // 根据附件表id，查询某条附件数据
                $attachmentDao = new \core\dao\AttachmentModel();
                $attachmentData = $attachmentDao->getAttachmentById($userCodeData[0]);

                if (empty($attachmentData) || (!empty($userCodeData[1]) && $attachmentData['user_id'] != $userCodeData[1]))
                {
                    return $this->show_error('您无权查看此附件');
                }
                $streamContent = VfsHelper::image($file, true);
                if ($path['extension'] == 'jpg' || $path['extension'] == 'jpeg') {
                    header('content-type:image/jpeg');
                } else  {
                    header('content-type:application/octet-stream');
                    header("Content-Disposition:attachment;filename=". $path['basename']);
                }
            }
        }
        echo $streamContent;
        return true;
    }
}
