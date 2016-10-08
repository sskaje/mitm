<?php

namespace sskaje\mitm;


class ProxyApp
{

    protected $mitm;

    public function __construct(MitmProxy $mitm)
    {
        $this->mitm = $mitm;
    }

    public function onClientData($data)
    {
        $this->log("Client Data Received\n", LOG_DEBUG, $data);

        $this->log("Writing to Server\n");
        return $this->server->write($data);
    }

    public function onServerData($data)
    {
        $this->log("Data from Server", LOG_DEBUG, $data);

        $this->log("Writing to Client\n");
        return $this->client->write($data);
    }

    public function onServerError($error)
    {
        $this->log('Server Error: ' . $error->getMessage());
    }

    public function onClientError($error)
    {
        $this->log('Client Error: ' . $error->getMessage());
    }

    public function onClientDrain()
    {
        $this->log("Client Drain\n");
        $this->server->resume();
    }

    public function onClientEnd()
    {
        $this->log("Client Connection Ended\n");
        $this->server->end();
    }

    public function onClientClose()
    {
        $this->log("Client Connection Closed\n");
        $this->server->close();
    }


    public function onServerDrain()
    {
        $this->log("Remote Drain\n");
        $this->client->resume();
    }

    public function onServerClose()
    {
        $this->log("Server Connection Closed");

        $that = $this;
        $this->mitm->loop->addTimer(
            1.0,
            function () use ($that) {
                $that->client->close();
            }
        );
    }

    public function onServerEnd()
    {
        $this->log("Server Connection Ended");

        $that = $this;
        $this->mitm->loop->addTimer(
            1.0,
            function () use ($that) {
                $that->client->end();
            }
        );
    }

}