# PHP-MiTM
**PHP** **M**an-**i**n-**T**he-**M**iddle TCP Proxy based on ReactPHP.

This proxy is not for *SSL to plain* or *SSL to SSL* hijacking. 

Author: sskaje ([http://sskaje.me/](http://sskaje.me/))


## Install

### Clone Code
```
$ git clone git@github.com:sskaje/mitm.git
```
or 
```
$ git clone https://github.com/sskaje/mitm.git
```

### Composer Install Dependencies
```
$ cd mitm
$ php composer.phar install
```


## Try out

**examples/proxy.php** is a simple implementation of PHP-MiTM sending logs to stderr.

**examples/verbose.php** does the same and dumps traffic data to stderr. 

### Usage
```
php examples/proxy.php LISTEN_PORT CONNECT_HOST CONNECT_PORT [RESOLVER]
```

### Examples
1. Forward TCP DNS requests to 127.0.0.1:53 to 114.114.114.114:53

This requires ROOT permission, you must know why.

If not, try 5353 as the first argument of **bin/proxy.php**.


```php
php examples/proxy.php 53 114.114.114.114 53 

```

```
# direct tcp dns query to 114.114.114.114
$ dig +tcp @114.114.114.114
# query 127.0.0.1:53
$ dig +tcp @127.0.0.1
# query port to 5353 if you bind proxy to 127.0.0.1:5353 
$ dig +tcp -p 5353 @127.0.0.1

```

2. Forward HTTP Request 
```
php examples/proxy.php  15920 118.184.180.46 80
```

```
curl 127.0.0.1:15920 -H 'Host: ip.cn'
当前 IP：39.166.202.153 来自：江西省九江市 移动

```

3. Hijack and modify HTTP Traffic

Change http requests from sskaje.me to ip.rst.im 

```
php examples/hijack.php  15920 104.31.70.199 80  
```

```
curl 127.0.0.1:15920 -H 'Host: sskaje.me'
```

You'll see
```
[1] [HIJACK] sskaje.me FOUND in HTTP Header
[1] [HIJACK] replaced to ip.rst.im
```



## More

### Hijacking Traffic on Router

If you have a Linux Router, or Linux box with *net.ipv4.ip_forward=1*:

```
iptables -t nat -A PREROUTING -p tcp --dst {CONNECT_HOST} --dport {CONNECT_PORT} -j REDIRECT --to-port {LISTEN_PORT}
```

#\# EOF
