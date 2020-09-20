# Test PATH_INFO support in Caddy
This is a repo to provide some **rough** tests about `PATH_INFO` support in Caddy v2.x using containers via `docker-compose`.

The code here is just a proof to help the work in https://github.com/caddyserver/caddy/issues/3718 by comparing how 3 different vendors (_caddy_, _apache_ and _nginx_) manage the same set of URLs pointing to a PHP script charged to use `PATH_INFO` to perform some kind of routing.

## Setup
### PHP
PHP has been exposed via FPM using `v7.4.10` w/o any supplementar extension but those provided by the official docker image.

### Caddy
Caddy `v2.1.1` has been configured using a variation of the [expanded form](https://caddyserver.com/docs/caddyfile/directives/php_fastcgi#expanded-form) of the `php_fastcgi` directive to avoid the "redirection issue" which is described in https://github.com/caddyserver/caddy/issues/3718.

### Apache
Apache `v2.4.38` is provided by the official docker image i.e. PHP runs as module w/o any PHP configuration change.

### Nginx
Nginx `v1.19.2` is provided by the official docker image and it has been configured to serve PHP via the PHP-FPM service.

### Tests
The included PHP files are:
```
www/
├── file.php
├── index.php
├── lib
│   └── javascript.php
└── tests
    └── testsuite.php
```
1. `index.php` just outputs the `$_SERVER` array and both `file.php` and `lib/javascript.php` include `index.php`, to simulate different routing files, w/ the goal of having one among the others declared as an _index file_
1. `testsuite.php` just automates the test against a set of "interesting URLs" to evaluate the "expected vs actual" result:
   - `/lib/javascript.php/1599824490/lib/requirejs/require.min.js`
   - `/index.php/foo`
   - `/index.php/foo?a=1&b=2`
   - `/foo`
   - `/foo.php/foo`
   - `/file.php/filename_UTF8_en+coded_それが動作するはず.png`
   - `/index.php/some%20%20whitespaces`

## Usage
To setup the 3 web server just fire the Compose:
``` bash
$ docker-compose up -d
```
and then point your browser to [http://<host>:8080/tests/testsuite.php](http://localhost:8080/tests/testsuite.php) to see the result of testing the 3 vendors against a set of fixtures coded into that file.

To stop everything just issue:
``` bash
$ docker-compose down -v
```

### Notes
1. Any change to a web server configuration requires to stop and start the Compose or just that web server container
1. PHP code can be changed at any time without the need to restart anything
1. The [Caddy issue](https://github.com/caddyserver/caddy/issues/3718), when resolved, will be used to bump the interest to add Caddy "native" support into Moodle i.e. w/o the need to fake it as "Apache" via `env SERVER_SOFTWARE Apache`. @scara will do it via [MDL-57646](https://tracker.moodle.org/browse/MDL-57646) (Caddy server support) :wink:

Have fun!
