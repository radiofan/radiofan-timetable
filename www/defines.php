<?php
define('DATA_IN_DB', 1);
define('CLEAR_POST', 1);
define('SALT', '');//ПРИ СМЕНЕ СОЛИ ВСЕ ПАРОЛИ СТАНУТ НЕ ДЕЙСТВИТЕЛЬНЫ!
define('USE_LOG', 1);
define('USE_SSL', 0);

/** TOKEN_LIVE_DAYS - время жизни запоминающего токена в сутках @see action_login */
define('TOKEN_LIVE_DAYS', 30);
/** MAX_TOKEN_REMEMBER - максималное колличество используемых токенов @see action_login */
define('MAX_TOKEN_REMEMBER', 10);

define('MAX_ELEMENTS_TIMETABLE', 50);
define('MAX_SECTION_NAME_LEN', 30);

define('MAIN_DBHOST', 'localhost');
define('MAIN_DBUSER', 'root');
define('MAIN_DBPASS', '');
define('MAIN_DBNAME', 'parcer.rad');
?>