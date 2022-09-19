<?php
/**
 * It is used to provide input for {@link ProtoSort}
 * User: ChengQi
 * Date: 10/8/14
 * Time: 14:46
 */

namespace NCFGroup\Protos\FundGate;

use \Assert\Assertion as Assert;

class ProtoOrder
{

    private $property;

    private $direction;

    /**
     * for pageable order
     * @param {@link EnumDirection} $direction
     * @param String $property
     */
    public function __construct(EnumDirection $direction, $property)
    {
        Assert::notNull($direction, '$diection can not be null');
        Assert::notBlank($property, '$property can not be null');
        $this->direction = $direction;
        $this->property = $property;
    }

    /**
     * @return String property
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return {@link EnumDirection} direction
     */
    public function getDirection()
    {
        return $this->direction;
    }

}