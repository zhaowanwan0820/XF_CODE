<?php
/**
 * UserService RequestGetPurchaseOrders.
 * User: ChengQ
 * Date: 10/8/14
 * Time: 10:51
 */

namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;

class RequestGetHistoryOrders extends AbstractRequestBase
{

    /**
     *
     * @var {@link ProtoPage}
     *
     * @required
     */
    private $protoPage;


    private $userId;


    public function __construct($userId, Pageable $protoPage)
    {
        $this->setUserId($userId);
        $this->setProtoPage($protoPage);
    }

    /**
     * @return {@link ProtoPage}
     */
    public function getProtoPage()
    {
        return $this->protoPage;
    }

    /**
     * @param {@link ProtoPage} $protoPage
     */
    private function setProtoPage(Pageable $protoPage)
    {
        Assert::notNull($protoPage,'$protoPage can not be empty');
        $this->protoPage = $protoPage;
    }

    /**
     * @return String $userId
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param String $userId
     */
    private function setUserId($userId)
    {
        Assert::notBlank($userId,'$userId can not be empty');
        $this->userId = $userId;
    }


}
