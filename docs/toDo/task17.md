Файл

packages\dixipro\magicpro\src\Api\API_Auth.php

имеет кучу одинаковых ошибок
например

строка 150

// если нет гугл выкинет исключение
$token = $params['token'];
$res = self::run("checkGoogleCapture", [
"token" => $token,
]);

но дело в том, что self::run("checkGoogleCapture", [
"token" => $token,
]);
выполняется в своей try catch и исключения не будет.
Получается надо проверять?

Я лопухнулся почему, в JS если пошло исключение оно пошло дальше, а тут получается не так.
