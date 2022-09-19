<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class QueryType extends AbstractEnum
{
    /**
     *  只查今天的
     */
    const ONLY_TODAY = 1;
    /**
     *  只查历史的
     */
    const ONLY_HISTORY = 2;
    /**
     *  今天的与历史的都查
     */
    const ALL = 3;
}
