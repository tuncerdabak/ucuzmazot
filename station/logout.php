<?php
/**
 * İstasyon Paneli - Çıkış
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/auth.php';

logout();
setFlash('success', 'Başarıyla çıkış yaptınız.');
redirect('/station/login.php');
