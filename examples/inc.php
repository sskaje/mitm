<?php

use sskaje\mitm\Options;

is_file(__DIR__ . '/../vendor/autoload.php') or die("Run: php composer.phar install\n");

require(__DIR__ . '/../vendor/autoload.php');


if (!isset($argv[3])) {
    usage();
}
$listen_port  = (int) $argv[1];
$connect_host = $argv[2];
$connect_port = (int) $argv[3];
$listen_host  = '0.0.0.0';
$dns_resolver = '192.168.1.1';

$options               = new Options();
$options->listen_port  = $listen_port;
$options->listen_host  = $listen_host;
$options->connect_host = $connect_host;
$options->connect_port = $connect_port;
$options->resolver     = $dns_resolver;


function usage()
{
    global $argv;
    echo <<<USAGE
Usage: 
{$argv[0]} LISTEN_PORT CONNECT_HOST CONNECT_PORT [RESOLVER]


USAGE;

    exit;
}