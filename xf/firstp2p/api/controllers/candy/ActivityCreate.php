<?php
namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyActivityService;
use core\service\candy\CandyUtilService;
use NCFGroup\Common\Library\Msgbus;
use NCFGroup\Protos\Ptp\Enum\MsgbusEnum;
use core\service\candy\CandyService;

/**
 * todo 临时应用，以后接口名字会更改为增加信宝
 * 增加信力接口
 */
class ActivityCreate extends AppBaseAction
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

        // 分享发现频道埋点
        Msgbus::instance()->produce(MsgbusEnum::MSGBUS_SHARE_NEWS, ['userId' => $loginUser['id'], 'createTime' => time()]);

        $sourceType = isset($this->typeAllowed[$this->form->data['type']]) ? $this->typeAllowed[$this->form->data['type']] : 0;
        if (empty($sourceType)) {
            throw new \Exception('信宝类型错误');
        }

        $userId = intval($loginUser['id']);
        if (!CandyUtilService::hasLoan($userId)) {
            throw new \Exception('历史必须投资一次');
        }

        $token = sprintf('api-%s-%s', $userId, date('Ymd'));
        $candyResult = CandyService::changeAmountByType($userId, $token, CandyService::SOURCE_TYPE_SHARE, '0', '分享文章');

        $this->json_data = array(
            'value' => '',
            'desc' => "分享成功，已获得{$candyResult['Amount']}信宝",
        );
    }

}
