<?php
define('DATA_IN_DB', 1);
define('CLEAR_POST', 1);
define('SALT', '');//ПРИ СМЕНЕ СОЛИ ВСЕ ПАРОЛИ СТАНУТ НЕ ДЕЙСТВИТЕЛЬНЫ!
define('USE_LOG', 1);
define('USE_SSL', 0);

/** 3600\*24 -- 60\*60*24 */
define('SECONDS_PER_DAY', 86400);
/** 60*60 */
define('SECONDS_PER_HOUR', 3600);
define('SECONDS_PER_MINUTE', 60);
define('DB_DATE_FORMAT', 'Y-m-d H:i:s');

/** TOKEN_LIVE_DAYS - время жизни запоминающего токена в сутках @see action_login */
define('TOKEN_LIVE_DAYS', 30);
/** MAX_TOKEN_REMEMBER - максималное колличество используемых токенов @see action_login */
define('MAX_TOKEN_REMEMBER', 20);

define('MAX_ELEMENTS_TIMETABLE', 50);
define('MAX_SECTION_NAME_LEN', 30);

define('MAIN_DBHOST', 'localhost');
define('MAIN_DBUSER', 'root');
define('MAIN_DBPASS', '');
define('MAIN_DBNAME', 'parcer.rad');
?>