<?php

use libs\utils\Curl;

/**
 * 信仔机器人 活动相关
 */
class XinChatPromotionAction extends CommonAction
{
    public function groupList()
    {
        $promotionGroupList = $this->request("admin/promotion-group-list");
        $promotionGroupList = array_map(function ($group) {
            return $this->convertGroupTime($group);
        }, $promotionGroupList);


        $this->assign('groupList', $promotionGroupList);

        $this->display();
    }

    public function showGroup()
    {
        $groupId = $_REQUEST["id"];
        $isAdd = empty($groupId);
        if (!$isAdd) {
            $group = $this->convertGroupTime($this->request("admin/promotion-group/" . $groupId));

            $this->assign('group', $group);
            if (!empty($group['voPromotions']) && !empty($group['voPromotions'][0]['imageUrl'])) {
                $this->assign('imageUrl', $group['voPromotions'][0]['imageUrl']);
            } else {
                $this->assign('imageUrl', "");
            }
        }

        $this->display();
    }

    public function saveGroup()
    {
        $request = $this->createRequest4SaveGroup();
        $groupId = $_REQUEST['groupId'];
        $isAdd = empty($groupId);
        if ($isAdd) {
            //新增
            $this->request("/admin/promotion-group/", true, $request);
        } else {
            //更新
            $this->request("/admin/promotion-group/{$groupId}", true, $request);
        }

        $this->success(l("UPDATE_SUCCESS"), 0, '/m.php?m=XinChatPromotion&a=groupList');
    }

    private function createRequest4SaveGroup()
    {
        $request['validityStart'] = strtotime($_REQUEST['validityStart']) * 1000;
        $request['validityEnd'] = strtotime($_REQUEST['validityEnd']) * 1000;

        foreach ($_REQUEST['promotion']['title'] as $key => $title) {
            $request['voPromotions'][$key]['title'] = $title;
            $request['voPromotions'][$key]['url'] = $_REQUEST['promotion']['url'][$key];
        }
        $imgUrl = $this->handlerImage();
        if (!empty($imgUrl) && !empty($request['voPromotions'])) {
            $request['voPromotions'][0]['imageUrl'] = $imgUrl;
        }

        return $request;
    }

    private function handlerImage()
    {
        $file = current($_FILES);
        if (empty($file) || empty($file['tmp_name'])) {
            return $_REQUEST["imageUrl"];
        }

        $imageInfo = getimagesize($file['tmp_name']);
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width != 750 || $height != 300) {
            $this->error("图片大小不正确, 图片大小应该为750*300, 目前是{$width}*{$height}");
        }

        $result = uploadFile(
            array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                //600kb
                'limitSizeInMB' => round(600 / 1024, 2),
            )
        );

        if (!empty($result['aid']) && empty($result['errors'])) {
            $imgUrl = get_attr($result['aid'], 1, false);

            return $imgUrl;
        }

        if (!empty($result['errors'])) {
            $this->error(implode($result['errors']));
        }

        $this->error("图片上传失败");
    }

    private function request($urlPath, $isPost = false, $param = array())
    {
        $host = $GLOBALS['sys_config']['XIN_CHAT']['BACKEND_HOST'];
        $url = $host . $urlPath;

        if ($isPost) {
            $result = json_decode(Curl::post_json($url, json_encode($param)), true);
        } else {
            $result = json_decode(Curl::get($url), true);
        }

        if ($result['errorCode'] != 0) {
            $this->error($result['devMsg']);
        }

        return $result['data'];
    }

    private function convertGroupTime($group)
    {
        $group["validityStart"] = date("Y-m-d H:i", $group["validityStart"] / 1000);
        $group["validityEnd"] = date("Y-m-d H:i", $group["validityEnd"] / 1000);

        return $group;
    }
}
