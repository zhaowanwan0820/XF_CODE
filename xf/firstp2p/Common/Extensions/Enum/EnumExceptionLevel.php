<?php
/* EnumExceptionLevel.php ---
 *
 * Filename: EnumExceptionLevel.php
 * Description: <put the file description here>
 * Author: zhounew
 * Maintainer: <put maintainers here>
 * Created: 2014-10-03 18:44
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup.com
 * http://www.firstp2p.com
 */

namespace NCFGroup\Common\Extensions\Enum;

use NCFGroup\Common\Extensions\Base\EnumBase;

final class EnumExceptionLevel extends EnumBase
{
    const NONE = 0;
    const WARNING = 1;
    const ERROR = 2;
    const EMERGENCY = 3;
}