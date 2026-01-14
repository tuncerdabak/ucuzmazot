<?php
/**
 * Admin Paneli - Çıkış
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/auth.php';

logout();
setFlash('success', 'Çıkış yapıldı.');
redirect('/admin/login.php');
