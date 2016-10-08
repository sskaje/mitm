<?php
namespace sskaje\mitm\ProxyHandler;

use sskaje\mitm\ProxyHandlerInterface;


class Forward extends Base implements ProxyHandlerInterface
{

    public function onClientData($data)
    {
        $this->connection->log("Client Data Received", $data);

        $this->connection->log("Writing to Server");
        return $this->connection->server->write($data);
    }

    public function onServerData($data)
    {
        $this->connection->log("Data from Server", $data);

        $this->connection->log("Writing to Client");
        return $this->connection->client->write($data);
    }

    public function onServerError($error)
    {
        $this->connection->log('Server Error: ' . $error->getMessage());
    }

    public function onClientError($error)
    {
        $this->connection->log('Client Error: ' . $error->getMessage());
    }

    public function onClientDrain()
    {
        $this->connection->log("Client Drain");
        $this->connection->server->resume();
    }

    public function onClientEnd()
    {
        $this->connection->log("Client Connection Ended");
        $this->connection->server->end();
    }

    public function onClientClose()
    {
        $this->connection->log("Client Connection Closed");
        $this->connection->server->close();
    }

    public function onServerDrain()
    {
        $this->connection->log("Remote Drain\n");
        $this->connection->client->resume();
    }

    public function onServerClose()
    {
        $this->connection->log("Server Connection Closed");

        $that = $this;
        $this->connection->mitm->loop->addTimer(
            1.0,
            function () use ($that) {
                $that->connection->client->close();
            }
        );
    }

    public function onServerEnd()
    {
        $this->connection->log("Server Connection Ended");

        $that = $this;
        $this->connection->mitm->loop->addTimer(
            1.0,
            function () use ($that) {
                $that->connection->client->end();
            }
        );
    }
}