<?php

return [
    'sbis' => [
        'access_token' => getenv('SBIS_TOKEN'),
        'api_url' => 'https://api.sbis.ru',
        'service_url' => 'https://online.sbis.ru/service/?srv=1',
    ],
    'ip' => [
        'name' => getenv('IP_NAME'),
        'surname' => getenv('IP_SURNAME'),
        'patronymic' => getenv('IP_PATRONYMIC'),
        'inn' => getenv('IP_INN'),
    ],
];
