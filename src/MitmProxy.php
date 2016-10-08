<?php
namespace sskaje\mitm;


use React\Dns\Resolver\Factory as DNSFactory;
use React\EventLoop\Factory;
use React\Socket\Connection as SocketConnection;
use React\Socket\Server;
use React\SocketClient\Connector;
use React\Stream\Stream;
use sskaje\mitm\ProxyHandler\Forward;

class MitmProxy
{
    public $loop;
    public $socket;
    public $connector;

    public $options;
    public $factory;

    public function __construct(Options $options)
    {
        $this->options = $options;

        $this->loop         = Factory::create();
        $this->socket       = new Server($this->loop);
        $dnsResolverFactory = new DNSFactory();
        $dns                = $dnsResolverFactory->createCached($options->resolver, $this->loop);
        $this->connector    = new Connector($this->loop, $dns);

    }

    protected function configure_proxy()
    {
        # use default factory
        if (!$this->factory) {
            $this->factory = function (Connection $connection) {
                return new Forward($connection);
            };
        } else if (!is_callable($this->factory)) {
            throw new \Exception('Proxy Handler Factory NOT CALLABLE');
        }

        $that = $this;

        $this->socket->on('error', function($e) {
            echo "SERVER ERROR: " . $e->getMessage() . "\n";
            exit;
        });

        $this->socket->on(
            'connection',
            function (SocketConnection $client) use ($that) {
                $connection = new Connection($that);
                $connection->handler = call_user_func_array($this->factory, [$connection]);
                if (!($connection->handler instanceof ProxyHandlerInterface)) {
                    throw new \Exception('Proxy Handler Factory MUST returns instances of ProxyHandlerInterface');
                }

                $connection->client = $client;

                $connection->log("Accepted Client Connection from {$connection->client->getRemoteAddress()}");

                # 初始化的时候注册一个 data 事件处理, 因为客户端可能第一时间有数据上发, 但是服务端网络连接尚未建立
                $client_init_wait = function ($data, $stream) use ($connection, &$client_init_wait) {
                    if ($connection->server_connected) {
                        $connection->client->removeListener('data', $client_init_wait);
                    } else {
                        $connection->log("Received data before server side connection established");
                        $connection->client->once(
                            'ready',
                            function (Connection $connection) use ($data) {
                                $connection->log("Sending Buffered Data", LOG_DEBUG, $data);

                                return $connection->handler->onClientData($data);
                            }
                        );
                    }
                };

                $client->on('data', $client_init_wait);

                $that->connector->create($that->options->connect_host, $that->options->connect_port)->then(
                    function (Stream $remote) use ($that, $connection) {
                        $connection->server = $remote;

                        $connection->log(
                            "Connected to Server "
                            . $that->options->connect_host
                            . ':'
                            . $that->options->connect_port
                        );
                        $connection->server_connected = 1;

                        $connection->client->emit('ready', [$connection]);

                        $connection->client->on('data',  [$connection->handler, 'onClientData']);
                        $connection->client->on('drain', [$connection->handler, 'onClientDrain']);
                        $connection->client->on('end',   [$connection->handler, 'onClientEnd']);
                        $connection->client->on('close', [$connection->handler, 'onClientClose']);
                        $connection->client->on('error', [$connection->handler, 'onClientError']);

                        $connection->server->on('data',  [$connection->handler, 'onServerData']);
                        $connection->server->on('drain', [$connection->handler, 'onServerDrain']);
                        $connection->server->on('end',   [$connection->handler, 'onServerEnd']);
                        $connection->server->on('close', [$connection->handler, 'onServerClose']);
                        $connection->server->on('error', [$connection->handler, 'onServerError']);
                    }
                )->otherwise(function ($reason) use($connection, $that) {

                    $connection->log("Server Connection Failed");
                    $connection->log("Reason: {$reason->getMessage()}");
                    $connection->log("Closing Client Connection");

                    $that->loop->addTimer(
                        1.0,
                        function () use ($connection) {
                            $connection->client->close();
                        }
                    );
                });
            }
        );
    }

    public function run()
    {
        $this->configure_proxy();

        $this->socket->listen($this->options->listen_port, $this->options->listen_host);

        $this->loop->run();
    }

    public function set_proxy_handler_factory(callable $factory)
    {
        $this->factory = $factory;
    }

}

# EOF
