<?php
$host = 'repo.packagist.org';
$ctx = stream_context_create([
    'ssl' => [
        'capture_peer_cert' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);
$client = @stream_socket_client(
    'ssl://' . $host . ':443',
    $errno,
    $errstr,
    30,
    STREAM_CLIENT_CONNECT,
    $ctx
);
if (!$client) {
    fwrite(STDERR, "Connect failed: $errstr ($errno)\n");
    exit(1);
}
$params = stream_context_get_params($client);
$cert = $params['options']['ssl']['peer_certificate'] ?? null;
if (!$cert) {
    fwrite(STDERR, "No peer certificate captured\n");
    exit(1);
}
$pem = '';
openssl_x509_export($cert, $pem);
$cacert = 'C:/php/cacert.pem';
$existing = file_get_contents($cacert);
if (strpos($existing, trim($pem)) === false) {
    file_put_contents($cacert, $existing . "\n" . $pem);
    echo "Appended intercepted certificate to cacert.pem\n";
} else {
    echo "Certificate already present in cacert.pem\n";
}
