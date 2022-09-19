<?php
/**
 * used for page query
 * User: ChengQ
 * Date: 10/8/14
 * Time: 14:44
 */

namespace NCFGroup\Protos\FundGate;

use \Assert\Assertion as Assert;


class ProtoSort
{

    private $orders;

    public function __construct(array $orders)
    {
        Assert::allNotEmpty($orders, '$orders can not be empty');
        Assert::isArray($orders, '$orders must be array');
        Assert::allIsInstanceOf($orders, 'ProtoOrder', "orders must is insanceof ProtoOrder");
        $this->orders = $orders;
    }

    public function add(ProtoOrder $order)
    {
        $this->orders[] = $order;
        return $this;
    }

    /**
     * @return array<{@link ProtoOrder}> orders
     */
    public function getOrders()
    {
        return $this->orders;
    }


}