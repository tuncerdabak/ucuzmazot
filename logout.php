<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/auth.php';

logout();
redirect('/');
