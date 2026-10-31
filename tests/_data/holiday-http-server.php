<?php
declare(strict_types=1);

// Serve one response on an ephemeral loopback port; stdout reports readiness and the request.
$server = stream_socket_server('tcp://127.0.0.1:0');
if (!$server) exit(1);
fwrite(STDOUT, stream_socket_get_name($server, false)."\n");
fflush(STDOUT);
$connection = stream_socket_accept($server, 5);
fclose($server);
if (!$connection) exit(1);
stream_set_timeout($connection, 5);
$request = fgets($connection);
while (($header = fgets($connection)) !== false && $header !== "\r\n") {}
fwrite(STDOUT, $request);
$status = $argv[1];
$body = $argv[2];
fwrite($connection, "HTTP/1.1 $status Test\r\nContent-Type: application/json\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n$body");
fclose($connection);
