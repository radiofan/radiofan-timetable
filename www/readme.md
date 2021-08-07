`index.php` - основа сайта. Запускает движок.

#### Операции:

Описание | Переменная | Подключаемые файлы | Строки
--- | --- | --- | ---
Первоначальная настройка | | | *2-6*
Создание констант | | `defines.php`, `langs/ru-lang.php` | *7-8*
Объявление функций | | `includes/functions/other-functions.php`, `includes/functions/*-functions.php` | *14-20*
Инициализация лога ошибок | `rad_log/null $LOG` | `includes/classes/log-class.php` | *26-28*
Инициализация БД | `rad_db $DB` | `includes/classes/db-class.php` | *30-31*
Инициализация настроек сайта | `rad_data $DATA` | `includes/classes/data-class.php` | *33-34*
 |  |  | 
