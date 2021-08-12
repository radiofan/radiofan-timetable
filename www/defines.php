<?php
define('DATA_IN_DB', 1);
define('CLEAR_POST', 1);
define('SALT_START', '');//ПРИ СМЕНЕ СОЛИ ВСЕ ПАРОЛИ СТАНУТ НЕ ДЕЙСТВИТЕЛЬНЫ!
define('SALT_END', '');//ПРИ СМЕНЕ СОЛИ ВСЕ ПАРОЛИ СТАНУТ НЕ ДЕЙСТВИТЕЛЬНЫ!
define('USE_LOG', 1);
define('DEBUG_SMTP', 0);
define('USE_SSL', 0);

define('ADMIN_RECOVERY_PASS', 0);//админ может восстанавливать пароль
define('ADMIN_CHANGE_MAIL', 0);//админ может менять почту
define('ADMIN_CHANGE_PASS', 0);//админ может менять пароль

/** 3600\*24 -- 60\*60*24 */
define('SECONDS_PER_DAY', 86400);
/** 60*60 */
define('SECONDS_PER_HOUR', 3600);
define('SECONDS_PER_MINUTE', 60);
define('BYTES_PER_KB', 1024);
define('BYTES_PER_MB', 1048576);
define('BYTES_PER_GB', 1073741824);
define('DB_DATE_FORMAT', 'Y-m-d H:i:s');

/** int REMEMBER_TOKEN_LIVE_DAYS - время жизни запоминающего токена в сутках @see action_login */
define('REMEMBER_TOKEN_LIVE_DAYS', 30);
/** int SESSION_TOKEN_LIVE_SECONDS - время жизни сессионного токена в секундах */
define('SESSION_TOKEN_LIVE_SECONDS', 20*SECONDS_PER_MINUTE);
/** int MAX_TOKEN_REMEMBER - максималное колличество используемых токенов @see action_login */
define('MAX_TOKEN_REMEMBER', 20);
/** int MAIL_VERIFY_TOKEN_LIVE_DAYS - время жизни токена подтверждения почты в сутках */
define('MAIL_VERIFY_TOKEN_LIVE_DAYS', 7);
/** int MAIL_PASS_RECOVERY_LIVE_HORS - время жизни токена смены пароля в часах */
define('MAIL_PASS_RECOVERY_LIVE_HORS', 6);

define('MAX_ELEMENTS_TIMETABLE', 50);
define('MAX_SECTION_NAME_LEN', 30);

define('MAIN_DBHOST', 'localhost');
define('MAIN_DBUSER', 'root');
define('MAIN_DBPASS', '');
define('MAIN_DBNAME', 'parcer.rad');

define('SMTP_SERVER', 'localhost');
define('SMTP_USER', 'me');
define('SMTP_PASS', '');
define('SMTP_PORT', 25);
define('SMTP_ADDRES_FROM', 'info@radiofan-tools.ru');
define('SMTP_NAME_FROM', 'radiofan-tools.ru');
?>