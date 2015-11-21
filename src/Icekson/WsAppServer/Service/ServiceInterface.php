<?php
/**
 * @author a.itsekson
 * @createdAt: 17.11.2015 15:08
 */

namespace Icekson\WsAppServer\Service;


interface ServiceInterface
{
    /**
     * return int
     */
    public function run();

    public function getRunCmd();

    public function start();

    public function startAsProcess();

    public function stop();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isRun();

    /**
     * @return int
     */
    public function getPid();
}