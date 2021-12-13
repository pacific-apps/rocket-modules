<?php

# Default Response Code For Index Route
http_response_code(200);

# Sending Header Response Content-Type as JSON
header('Content-Type: application/json');

# Actual default global response
echo '{"message":"hello world!"}';
