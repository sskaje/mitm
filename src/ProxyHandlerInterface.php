<?php
/**
 * Created by PhpStorm.
 * User: sskaje
 * Date: 16/10/8
 * Time: 下午8:16
 */

namespace sskaje\mitm;


interface ProxyHandlerInterface
{

    public function __construct(Connection $connection);

    public function onClientData($data);

    public function onClientError($error);

    public function onClientDrain();

    public function onClientEnd();

    public function onClientClose();


    public function onServerData($data);

    public function onServerError($error);

    public function onServerDrain();

    public function onServerEnd();

    public function onServerClose();
}