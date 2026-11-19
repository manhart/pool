<?php
declare(strict_types=1);

// Serve the supplied responses on an ephemeral loopback port; stdout reports readiness and requests.
$server = stream_socket_server('tcp://127.0.0.1:0');
if (!$server) exit(1);
fwrite(STDOUT, stream_socket_get_name($server, false)."\n");
fflush(STDOUT);
foreach (json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR) as [$status, $body]) {
    $connection = stream_socket_accept($server, 5);
    if (!$connection) exit(1);
    stream_set_timeout($connection, 5);
    $request = fgets($connection);
    while (($header = fgets($connection)) !== false && $header !== "\r\n") {}
    fwrite(STDOUT, $request);
    fwrite($connection, "HTTP/1.1 $status Test\r\nContent-Type: application/json\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n$body");
    fclose($connection);
}
fclose($server);
