<html>
<body>
<?php
$data = [
    "title" => "titul",
    "subject" => "odo mna",
];

$req = curl_init();
curl_setopt_array($req, [
    CURLOPT_URL            => "http://localhost/es/emailsdb/test/?pretty",
    CURLOPT_CUSTOMREQUEST  => "POST",
    CURLOPT_POSTFIELDS     => json_encode($data),
    CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($req);
print($response);
curl_close($req);
?>
</body>
</html>