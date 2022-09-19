<?php
namespace NCFGroup\Common\Extensions;

require_once(dirname(__DIR__) . '/RPC/AbstractYarRPCServer.php');

use NCFGroup\Common\Extensions\RPC\AbstractYarRPCServer;

class BackendServerBase extends AbstractYarRPCServer
{
    public function __construct(\Phalcon\DI $di)
    {
        $this->di = $di;
    }

    protected function initialize()
    {
        $di = $this->di;
        $config = $this->di->getConfig();
        parent::init($config, $di, $di->get('logger'), $di->get('varz'));
    }
}
