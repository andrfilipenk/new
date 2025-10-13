<?php
// app/Core/Utils/WebSocketServer.php
namespace Core\Utils;

use Core\Repository\PerformanceMetricsRepository;

class WebSocketServer
{
    private PerformanceMetricsRepository $repository;
    private $server;
    private $clients = [];

    public function __construct(PerformanceMetricsRepository $repository)
    {
        $this->repository = $repository;
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, '0.0.0.0', 8080);
        socket_listen($this->server);
    }

    public function run(): void
    {
        while (true) {
            $sockets = array_merge([$this->server], $this->clients);
            $write = $except = [];
            socket_select($sockets, $write, $except, null);

            foreach ($sockets as $socket) {
                if ($socket === $this->server) {
                    $client = socket_accept($this->server);
                    $this->clients[(int)$client] = $client;
                    $this->handshake($client);
                } else {
                    $bytes = @socket_recv($socket, $buffer, 1024, 0);
                    if ($bytes === false || $bytes === 0) {
                        unset($this->clients[(int)$socket]);
                        socket_close($socket);
                        continue;
                    }

                    $data = $this->decode($buffer);
                    if ($data) {
                        $this->handleMessage($socket, json_decode($data, true));
                    }
                }
            }

            $this->broadcastNewMetrics();
            sleep(5); // Poll every 5 seconds
        }
    }

    private function handshake($socket): void
    {
        $buffer = socket_read($socket, 1024);
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match)) {
            $key = $match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
            $accept = base64_encode(sha1($key, true));
            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                        "Upgrade: websocket\r\n" .
                        "Connection: Upgrade\r\n" .
                        "Sec-WebSocket-Accept: $accept\r\n\r\n";
            socket_write($socket, $response);
        }
    }

    private function decode($buffer): string
    {
        $length = ord($buffer[1]) & 127;
        $offset = 2;
        if ($length == 126) {
            $offset = 4;
        } elseif ($length == 127) {
            $offset = 10;
        }
        $mask = substr($buffer, $offset, 4);
        $data = substr($buffer, $offset + 4);
        $unmasked = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $unmasked .= $data[$i] ^ $mask[$i % 4];
        }
        return $unmasked;
    }

    private function encode($data): string
    {
        $payload = json_encode($data);
        $length = strlen($payload);
        $frame = chr(129); // Text frame
        if ($length <= 125) {
            $frame .= chr($length);
        } elseif ($length <= 65535) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('J', $length);
        }
        $frame .= $payload;
        return $frame;
    }

    private function handleMessage($socket, $message): void
    {
        if (isset($message['last_timestamp'])) {
            $this->clients[(int)$socket] = ['socket' => $socket, 'last_timestamp' => $message['last_timestamp']];
        }
    }

    private function broadcastNewMetrics(): void
    {
        foreach ($this->clients as $clientData) {
            $socket = $clientData['socket'];
            $lastTimestamp = $clientData['last_timestamp'] ?? date('Y-m-d H:i:s');

            $metrics = $this->repository->getNewMetrics($lastTimestamp);

            if (!empty($metrics)) {
                $lastMetric = end($metrics);
                $clientData['last_timestamp'] = $lastMetric['created_at'];

                $data = [
                    'metrics' => $metrics,
                    'last_timestamp' => $lastMetric['created_at']
                ];

                socket_write($socket, $this->encode($data));
            }
        }
    }
}