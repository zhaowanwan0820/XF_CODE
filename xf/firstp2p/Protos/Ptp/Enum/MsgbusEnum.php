<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MsgbusEnum extends AbstractEnum {

    const MSGBUS_DEAL_ZX                 = 'deal_zx';
    const MSGBUS_DEAL_P2P                = 'deal_p2p';
    const MSGBUS_NAME_AUTH               = 'name_auth';
    const MSGBUS_BIND_CARD               = 'bind_card';
    const MSGBUS_SHARE_NEWS              = 'share_news';
    const MSGBUS_CANDY_EXCHANGE          = 'candy_exchange';
    const MSGBUS_CANDY_SHARE             = 'candy_share';
    const MSGBUS_CAND_LOTTERY            = 'candy';
    const MSGBUS_SPAROW_GAME             = 'sparow_game';
    const MSGBUS_CHECKIN                 = 'checkin';
    const MSGBUS_MAREKTING_QUESTIONNAIRE = 'marekting_questionnaire';
    const MSGBUS_INVITE_REGISTER         = 'invite_register';
    const MSGBUS_INVITE_DEAL             = 'invite_deal';
    const MSGBUS_USER_REGISTER           = 'user_register';
    const MSGBUS_USER_RECHARGE           = 'user_recharge';
    const MSGBUS_USER_WITHDRAWAL         = 'user_withdrawal';
}

