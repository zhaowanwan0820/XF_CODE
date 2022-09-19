<?php
namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyUtilService;
use core\service\candy\CandyService;

/**
 * 增加信宝接口
 */
class CandyCreate extends AppBaseAction
{

    private $typeAllowed = array(
        'share' => CandyService::SOURCE_TYPE_SHARE,
    );

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'type' => array('filter' => 'required', 'message'=> '信宝类型不能为空'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $sourceType = isset($this->typeAllowed[$this->form->data['type']]) ? $this->typeAllowed[$this->form->data['type']] : 0;
        if (empty($sourceType)) {
            throw new \Exception('信宝类型错误');
        }

        $userId = intval($loginUser['id']);
        if (!CandyUtilService::hasLoan($userId)) {
            throw new \Exception('历史必须投资一次');
        }

        $token = sprintf('api-%s-%s', $userId, date('Ymd'));
        $candyResult = CandyService::changeAmountByType($userId, $token, $sourceType, '0', '分享文章');

        $this->json_data = array(
            'value' => $candyResult['Amount'],
            'desc' => "分享成功，已获得{$candyResult['Amount']}信宝",
        );
    }

}
