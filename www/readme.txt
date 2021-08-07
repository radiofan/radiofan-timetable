index.php - основа сайта. Запускает движок.
Операции:
 Первоначальная настройка [2-6]
 Создание констант (defines.php, langs/ru-lang.php) [7-8]
 Объявление функций (подключает все файлы includes/functions/*-functions.php, также обязательно иметь other-functions.php) [14-20]
 Инициализация лога ошибок {rad_log|null $LOG} (includes/classes/log-class.php) [26-28]
 Инициализация БД {rad_db $DB} (includes/classes/db-class.php) [30-31]
 Инициализация настроек сайта {rad_data $DATA} (includes/classes/data-class.php) [33-34]
 //TODO
 
 Проверка сессии
 Объявление пользователя (переменная $USER) (смтр. includes/user-class.php)
 Объявление ЧПУ (переменная $URL) (смтр. includes/url-class.php)
 Создание страниц (смтр. pages.php)
 Выполнение событий (смтр. actions.php)
 Обработка параметров (смтр. pages.php) для шаблонизатора, генерация выбранной страницы, её показ.
