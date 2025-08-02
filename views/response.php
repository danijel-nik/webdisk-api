<?php
http_response_code($response['status']);
unset($response['status']);
header('Content-Type: application/json');
echo json_encode($response);