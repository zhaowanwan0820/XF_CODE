<?php

namespace NCFGroup\Ptp\Apis;

/**
 * 信仔机器人帮助接口
 */
class HelpApi
{

    const KEYWORD_SUFFIX = '相关问题';

    /**
     * 可查询的帮助类别
     */
    private $keywords = array(
        '网信介绍',
        '资金安全',
        '客户端操作',
        '勋章',
        '优惠券',
        '红包',
        '公募基金',
        '邀请奖励',
        '网信证券',
        '网信保险',
        '智多新',
        //'黄金',
    );

    /**
     * 获取帮助关键字列表
     */
    public function getKeywords()
    {
        $actionList = array();
        foreach ($this->keywords as $value) {
            $actionList[] = array(
                'title' => $value,
                'type' => 3,
                'uri' => $value.self::KEYWORD_SUFFIX,
            );
        }

        return array('errorCode' => 0, 'errorMsg' => '', 'data' => array(
            'type' => 1,
            'title' => "请您联系客服热线:95782",
            'content' => array()
            //'title' => "为您找到帮助中心",
            //'content' => array(
            //    'columnNum' => 3,
            //    'actionList' => $actionList,
            //)
        ));
    }

    /**
     * 获取帮助标题列表
     */
    public function getTitles()
    {
        $params = json_decode(file_get_contents('php://input'), true);

        $keyword = addslashes($params['keyword']);
        $title = str_replace(self::KEYWORD_SUFFIX, '', $keyword);

        $articleService = new \core\service\ArticleService();
        $cateInfo = $articleService->getArticleCateByTittle($title);
        if (empty($cateInfo)) {
            return array('errorCode' => 1, 'errorMsg' => '帮助类别不存在');
        }

        $result = $articleService->getArticleListByCateId($cateInfo['id']);

        $actionList = array();
        $host = $this->getApiHost();
        foreach ($result as $item) {
            $actionList[] = array(
                'title' => $item['title'],
                'type' => 2,
                'uri' => $host.'/help/faq/?id='.$item['id'],
            );
        }

        return array('errorCode' => 0, 'errorMsg' => '', 'data' => array(
            'type' => 1,
            'title' => "为您找到{$keyword}",
            'content' => array(
                'title' => $keyword,
                'columnNum' => 1,
                'actionList' => $actionList,
            ),
        ));
    }

    private function getApiHost()
    {
        return 'https://'.str_replace('p2pbackend', 'api', $_SERVER['HTTP_HOST']);
    }

}
