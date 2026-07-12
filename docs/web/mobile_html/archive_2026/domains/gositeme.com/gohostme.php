<?php
// Redirect /gohostme → /gohostme/ (trailing slash needed for directory routing)
header('Location: /gohostme/', true, 301);
exit;
