<?php
namespace NCFGroup\Task\Instrument;

/**
 * FakeJob
 * 模仿gearman的job
 *
 * @author jingxu
 */
class FakeJob
{
    private $workload;

    public function __construct($workload)
    {
        $this->workload = $workload;
    }

    public function workload()
    {
        return $this->workload;
    }
}
