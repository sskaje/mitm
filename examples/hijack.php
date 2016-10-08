<?php
use sskaje\mitm\Connection;
use sskaje\mitm\Logger;
use sskaje\mitm\MitmProxy;
use sskaje\mitm\ProxyHandler\Base;
use sskaje\mitm\ProxyHandlerInterface;

require(__DIR__ . '/inc.php');

Logger::$dump = 'plain';
Logger::$log_level = LOG_DEBUG;

$mitm = new MitmProxy($options);

echo "This example hijacks HTTP requests from sskaje.me to ip.rst.im, both domains are using cloudflare CDN\n";
echo "Listening at 0.0.0.0:{$listen_port}\n";


class myHijack extends Base implements ProxyHandlerInterface
{
    public function onClientData($data)
    {
        $this->connection->log("Client Data Received", $data);

        if (stripos($data, "\r\nHost: sskaje.me\r\n") !== false) {
            $this->connection->log("[HIJACK] sskaje.me FOUND in HTTP Header");
            $data = str_ireplace(
                "\r\nHost: sskaje.me\r\n",
                "\r\nHost: ip.rst.im\r\n",
                $data
            );

            $this->connection->log("[HIJACK] replaced to ip.rst.im", $data);
        }

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


# class need to be declared before this line
$mitm->set_proxy_handler_factory(function(Connection $connection) {
    return new \myHijack($connection);
});

$mitm->run();



# EOF