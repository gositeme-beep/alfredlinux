<?php
// This gateway has been replaced by the Root Authenticator Engine (port 7777).
// The engine is not web-accessible — it runs on 127.0.0.1 only.
http_response_code(404);
echo '<!DOCTYPE html><html><body><h1>404 Not Found</h1></body></html>';
exit;
