<?php

# Default Response Code For Routes Not Found
http_response_code(404);

# Sending Header Response Content-Type as JSON
header('Content-Type: application/json');

# Actual Response Here
echo '{"error":"Route not found"}';
