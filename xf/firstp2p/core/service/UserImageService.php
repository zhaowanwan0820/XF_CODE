<?php
/**
 * 用户上传图片服务
 *
 * @date 2016-10-28
 * @author guofeng@ucfgroup.com
 */

namespace core\service;

use core\dao\UserImageModel;

/**
 * @package core\service
 */
class UserImageService extends BaseService {
    /**
     * 通过用户ID、图片类型，获取用户图片信息
     * @param int $userId
     * @param int $type
     * @return array
     */
    public function getUserImageInfo($userId, $type = UserImageModel::TYPE_1) {
        return UserImageModel::instance()->getUserImageInfo($userId, $type);
    }

    /**
     * 上传用户图片信息
     * @param array $params
     *     file:文件域信息数组
     *     user_id:用户ID
     *     type:图片类型(1:头像)
     *     is_priv:是否是隐私文件
     *     desc:图片描述
     * @return array
     */
    public function uploadUserImageInfo($params) {
        // 是否是隐私文件
        $isPriv = !empty($params['is_priv']) ? intval($params['is_priv']) : UserImageModel::IS_PRIV_0;
        // 图片类型
        $type = isset($params['type']) ? intval($params['type']) : UserImageModel::TYPE_1;
        // 上传用户图片
        $result = uploadFile(array(
            'file' => $params['file'], // 文件域信息数组
            'isImage' => 1, // 是否是图片
            'asAttachment' => 0, // 是否作为附件保存
            'asPrivate' => $isPriv, // 是否是隐私文件
        ));
        if (isset($result['status']) && $result['status'] == 1) {
            $data = array(
                'user_id' => intval($params['user_id']),
                'type' => $type,
                'filename' => $result['original_filename'],
                'filesize' => $result['size_in_bytes'],
                'attachment' => $result['full_path'],
                'is_priv' => $isPriv,
                'description' => !empty($params['desc']) ? htmlspecialchars($params['desc']) : '',
            );
            // 通过用户ID、图片类型，获取用户图片信息
            $userImageInfo = $this->getUserImageInfo($data['user_id'], $data['type']);
            if (empty($userImageInfo)) {
                // 插入用户图片信息
                $userImageRet = UserImageModel::instance()->insertUserImageInfo($data);
            }else{
                // 更新用户图片信息
                $userImageRet = UserImageModel::instance()->updateUserImageInfo($data);
            }
            if ($userImageRet) {
                return array('respCode'=>'00', 'respMsg'=>'SUCCESS', 'img' => $result['full_path']);
            }else{
                return array('respCode'=>'02', 'respMsg'=>'uploadUserImageInfo Failed');
            }
        }
        return array('respCode'=>'01', 'respMsg'=>(isset($result['errors'][0]) ? $result['errors'][0] : '上传失败'));
    }
}
