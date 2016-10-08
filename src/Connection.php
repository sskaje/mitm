<?php
namespace sskaje\mitm;

class Connection
{
    protected $connection_id = 0;

    /**
     * @var \React\Socket\Connection
     */
    public $client;
    /**
     * @var \React\Socket\Connection
     */
    public $server;

    public $server_connected = 0;

    public $mitm;

    /**
     * @var ProxyHandlerInterface
     */
    public $handler;

    public function __construct(MitmProxy $mitm)
    {
        $this->get_connection_id();

        $this->mitm = $mitm;
    }

    public function get_connection_id()
    {
        static $conn_id = 0;

        if ($this->connection_id) {
            return $this->connection_id;
        }

        return $this->connection_id = ++ $conn_id;
    }

    public function log($msg, $data=null)
    {
        Logger::Log("[{$this->connection_id}] " . $msg, $data);
    }
}