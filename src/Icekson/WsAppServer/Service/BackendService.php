<?php
/**
 * @author a.itsekson
 * @createdAt: 17.11.2015 15:50
 */

namespace Icekson\WsAppServer\Service;

use Icekson\WsAppServer\Application;
use Icekson\WsAppServer\Config\ServiceConfig;
use Icekson\WsAppServer\Rpc\RPC;

abstract class BackendService extends AbstractService
{

    public function __construct(Application $app, ServiceConfig $config)
    {
        parent::__construct($config->getName(), $config, $app);
    }
    public function getRunCmd()
    {
        return sprintf("%s scripts/runner.php app:service --type=backend --name='%s'", $this->getConfiguration()->get("php_path"), $this->getName());
    }

    public function run(){}

}