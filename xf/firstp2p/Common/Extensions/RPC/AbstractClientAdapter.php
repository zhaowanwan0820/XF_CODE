<?php
/* AbstractClientAdapter.php ---
 *
 * Filename: AbstractClientAdapter.php
 * Description: 抽象接口类，用于定义所有RPC的接口
 * Author: zhounew
 * Created: 14-9-25 下午5:37
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup
 */

namespace NCFGroup\Common\Extensions\RPC;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * Class AbstractClientAdapter
 *
 * 定义所有RPC的接口
 *
 * @package NCFGroup\Common\Extensions\RPC
 */
abstract class AbstractClientAdapter
{

    public function callByParams($service, $method, ProtoBufferBase $args)
    {
    }

    public function callByObject($service_obj)
    {
    }
}
