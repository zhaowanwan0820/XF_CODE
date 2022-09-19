<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CifBankDepository extends AbstractEnum
{
    const DPT_DIRECT = '1';//“直接指定”方式（需要填写银行卡帐号和密码）
    const DPT_PRESET = '2';//“预设定”方式（无需填写银行卡帐号和密码）
}
