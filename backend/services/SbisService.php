<?php

class SbisService
{
    private $config;
    private $accessToken;

    public function __construct($config)
    {
        $this->config = $config;
        $this->accessToken = $config['sbis']['access_token'];
    }

    private function request($url, $method = 'GET', $data = null, $isJsonRpc = false)
    {
        $ch = curl_init();

        $headers = [
            'Content-type: application/json;charset=utf-8',
            'X-SBISAccessToken: ' . $this->accessToken,
        ];

        $options = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $isJsonRpc ? $data : json_encode($data);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $answer = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode" . PHP_EOL . ($answer['error']['message'] ?? ''));
        }

        return $answer;
    }

    public function getWarehousesFromRetailApi()
    {
        // Альтернативный способ через retail API
        $url = $this->config['sbis']['api_url'] . '/retail/point/list';

        try {
            $data = $this->request($url);

            $warehouses = [];
            foreach ($data['points'] ?? [] as $point) {
                $warehouses[] = [
                    'id' => $point['id'],
                    'name' => $point['name'],
                ];
            }

            return $warehouses;
        } catch (Exception $e) {
            // Если и это не работает, возвращаем пустой массив
            return [];
        }
    }

    public function getWarehousesFromService()
    {
        // Получаем склады через JSON-RPC метод СБИС
        $request = [
            'jsonrpc' => '2.0',
            'protocol' => 7,
            'method' => 'Warehouse.GetList',
            'params' => [
                'Фильтр' => [
                    'd' => [
                        'current',
                        null,
                        [
                            -1,
                            -2
                        ],
                        true,
                        '-2',
                        [
                            'Warehouse'
                        ]
                    ],
                    's' => [
                        [
                            't' => 'Строка',
                            'n' => 'BalanceRegistry'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Folder'
                        ],
                        [
                            't' => [
                                'n' => 'Массив',
                                't' => 'Число целое'
                            ],
                            'n' => 'Ids'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'IsUsing'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'OurCompany'
                        ],
                        [
                            't' => [
                                'n' => 'Массив',
                                't' => 'Строка'
                            ],
                            'n' => 'ScopesAreas'
                        ]
                    ],
                    '_type' => 'record',
                    'f' => 0
                ],
                'Сортировка' => [
                    'd' => [
                        [
                            false,
                            'IsFolder',
                            true
                        ],
                        [
                            true,
                            'Custom',
                            false
                        ]
                    ],
                    's' => [
                        [
                            't' => 'Логическое',
                            'n' => 'l'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'n'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'o'
                        ]
                    ],
                    '_type' => 'recordset',
                    'f' => 0
                ],
                'Навигация' => [
                    'd' => [
                        true,
                        2,
                        0
                    ],
                    's' => [
                        [
                            't' => 'Логическое',
                            'n' => 'ЕстьЕще'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'РазмерСтраницы'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'Страница'
                        ]
                    ],
                    '_type' => 'record',
                    'f' => 0
                ],
                'ДопПоля' => [
                    'Name',
                ]
            ],
            'id' => 1,
        ];

        $result = $this->request(
            $this->config['sbis']['service_url'],
            'POST',
            json_encode($request, JSON_UNESCAPED_UNICODE),
            true
        );

        // Форматируем результат
        $warehouses = [];
        foreach ($result['result']['d'] ?? [] as $warehouse) {
            $item = [];
            foreach ($result['result']['s'] ?? [] as $index => $column) {
                $item[strtolower($column['n'])] = $warehouse[$index];
            }
            $warehouses[] = $item;
        }

        return $warehouses;
    }

    public function getWarehouses()
    {
        $request = [
            'jsonrpc' => '2.0',
            'protocol' => 5,
            'method' => 'sabyWarehouse.List',
            'params' => [
                'filter' => [
                    'limit' => 10,
                ],
            ],
            'id' => 0,
        ];

        $result = $this->request(
            $this->config['sbis']['service_url'],
            'POST',
            json_encode($request, JSON_UNESCAPED_UNICODE),
            true
        );

        // Форматируем результат
        $warehouses = [];
        foreach ($result['result']['warehouses'] ?? [] as $item) {
            $warehouses[] = [
                'id' => $item['id'] ?? '',
                'name' => $item['name'] ?? 'Склад',
            ];
        }

        return $warehouses;
    }

    public function getProducts($kitId)
    {
        $request = [
            'jsonrpc' => '2.0',
            'protocol' => 7,
            'method' => 'Nomenclature.NomenclatureRead',
            'params' => [
                'ИдО' => $kitId,
                'ИмяМетода' => 'Номенклатура.FormatCard',
                'Params' => [
                    'd' => [
                        [
                            'd' => [],
                            's' => [],
                            '_type' => 'record',
                            'f' => 1,
                        ],
                    ],
                    's' => [
                        [
                            't' => 'Запись',
                            'n' => 'CostOption'
                        ],
                    ],
                    '_type' => 'record',
                    'f' => 0,
                ],
            ],
            'id' => 1
        ];

        $result = $this->request(
            $this->config['sbis']['service_url'],
            'POST',
            json_encode($request, JSON_UNESCAPED_UNICODE),
            true
        );

        $products = [];
        foreach ($result['result']['s'] ?? [] as $index => $column) {
            if ($column['n'] === 'CardExtData') {
                foreach ($result['result']['d'][$index]['d'] ?? [] as $cardExtData) {
                    if (($cardExtData['s'][0]['n'] ?? '') === 'ModifiersData') {
                        $columns = $cardExtData['d'][0]['s'] ?? [];
                        foreach ($cardExtData['d'][0]['d'] ?? [] as $product) {
                            $item = [];
                            foreach ($columns as $ind => $col) {
                                $item[$col['n']] = $product[$ind];
                                if ($col['n'] === 'NomenclatureInfo') {
                                    if (($product[$ind]['d'][3]['d'][3] ?? null) === 1) {
                                        $item[$col['n']] = 'Service';
                                    } elseif (($product[$ind]['d'][3]['d'][3] ?? null) === 0) {
                                        $item[$col['n']] = 'Product';
                                    } else {
                                        $item[$col['n']] = 'Other';
                                    }
                                }
                            }
                            if ($item['Group']) {
                                $item['nomNumber'] = '';
                                if ($item['NomenclatureInfo'] === 'Product') {
                                    $url = $this->config['sbis']['api_url'] . '/retail/nomenclature/' . $item['Nom'];
                                    $data = $this->request($url);
                                    $item['nomNumber'] = $data['nomNumber'] ?? '';
                                }
                                $products[] = $item;
                            }
                        }
                    }
                }
            }
        }

        return $products;
    }

    public function getKits()
    {
        $count = 100;
        $folderId = 10408;
        // Получаем склады через JSON-RPC метод СБИС
        $request = [
            'jsonrpc' => '2.0',
            'protocol' => 7,
            'method' => 'Nomenclature.List',
            'params' => [
                'Фильтр' => [
                    'd' => [
                        null,
                        null,
                        true,
                        null,
                        null,
                        null,
                        true,
                        [
                            'd' => [
                                'desktop'
                            ],
                            's' => [
                                [
                                    't' => 'Строка',
                                    'n' => 'Device'
                                ]
                            ],
                            '_type' => 'record',
                            'f' => 1
                        ],
                        null,
                        $folderId,
                        $folderId,
                        $folderId,
                        0,
                        true,
                        false,
                        1,
                        null,
                        true,
                        null,
                        null,
                        [
                            'Catalog'
                        ],
                        [
                            'd' => [
                                true
                            ],
                            's' => [
                                [
                                    't' => 'Логическое',
                                    'n' => 'HideConditionalProduct'
                                ]
                            ],
                            '_type' => 'record',
                            'f' => 2
                        ],
                        null,
                        true,
                        null,
                        null,
                        null
                    ],
                    's' => [
                        [
                            't' => 'Строка',
                            'n' => 'Archival'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Balance'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'BalanceEmptyFolder'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'BalanceForOrganization'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'BarcodeExist'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Category'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'CheckFolderExists'
                        ],
                        [
                            't' => 'Запись',
                            'n' => 'ConfigurationOption'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Envd'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'FolderCompilation'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'FolderUI'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'GetColumnsFromSettings'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'GetPath'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'GetRights'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'HideEmptyFolder'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'Link'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'MarkColor'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'NewConfiguration'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'NodeType'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'PublicationSaleState'
                        ],
                        [
                            't' => [
                                'n' => 'Массив',
                                't' => 'Строка'
                            ],
                            'n' => 'ScopesAreas'
                        ],
                        [
                            't' => 'Запись',
                            'n' => 'ServiceOption'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'StateSystem'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'TranslitSearchString'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Type'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Vat'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'Warehouse'
                        ]
                    ],
                    '_type' => 'record',
                    'f' => 0
                ],
                'Сортировка' => [
                    'd' => [
                        [
                            true,
                            'Custom',
                            false
                        ]
                    ],
                    's' => [
                        [
                            't' => 'Логическое',
                            'n' => 'l'
                        ],
                        [
                            't' => 'Строка',
                            'n' => 'n'
                        ],
                        [
                            't' => 'Логическое',
                            'n' => 'o'
                        ]
                    ],
                    '_type' => 'recordset',
                    'f' => 0
                ],
                'Навигация' => [
                    'd' => [
                        true,
                        $count,
                        0
                    ],
                    's' => [
                        [
                            't' => 'Логическое',
                            'n' => 'ЕстьЕще'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'РазмерСтраницы'
                        ],
                        [
                            't' => 'Число целое',
                            'n' => 'Страница'
                        ]
                    ],
                    '_type' => 'record',
                    'f' => 0
                ],
                'ДопПоля' => [
                    'Name',
                    'Identifier',
                    'Code',
                    'TypeObject',
                ],
            ],
            'id' => 1
        ];

        $result = $this->request(
            $this->config['sbis']['service_url'],
            'POST',
            json_encode($request, JSON_UNESCAPED_UNICODE),
            true
        );

        $columns = $result['result']['s'] ?? [];

        $kits = [];
        foreach ($result['result']['d'] ?? [] as $kit) {
            $item = [];
            foreach ($columns as $index => $column) {
                $item[$column['n']] = $kit[$index];
                if ($column['n'] === 'TypeObject') {
                    if ($kit[$index]['d'][3] === 9) {
                        $item[$column['n']] = 'Kit';
                    } else {
                        $item[$column['n']] = 'Product';
                    }
                }
            }
            if ($item['TypeObject'] === 'Kit') {
                $kits[] = $item;
            }
        }

        return $kits;
    }

    public function createTransferDocument($fromWarehouse, $toWarehouse, $kits)
    {
        $guid = $this->generateUuid();
        $ipData = $this->config['ip'];
        $fileName = "ON_Movement_{$ipData['inn']}_{$ipData['inn']}_" . date('Ymd') . "_{$guid}.xml";
        $xmlData = $this->createXmlDoc($fileName, $ipData, $fromWarehouse, $toWarehouse, $kits);
        file_put_contents('doc.xml', $xmlData);
        $document = [
            'jsonrpc' => '2.0',
            'method' => 'СБИС.ЗаписатьДокумент',
            'params' => [
                'Документ' => [
                    'Дата' => date('d.m.Y'),
                    'Тип' => 'ВнутрПрм',
                    'НашаОрганизация' => [
                        'СвФЛ' => [
                            'ИНН' => $ipData['inn'],
                            'Имя' => $ipData['name'],
                            'Отчество' => $ipData['patronymic'],
                            'Фамилия' => $ipData['surname'],
                        ],
                    ],
                    'Автор' => [
                        'Идентификатор' => '',
                        'Имя' => $ipData['name'],
                        'Отчество' => $ipData['patronymic'],
                        'Фамилия' => $ipData['surname'],
                    ],
                    'Ответственный' => [
                        'Идентификатор' => '',
                        'Имя' => $ipData['name'],
                        'Отчество' => $ipData['patronymic'],
                        'Фамилия' => $ipData['surname'],
                    ],
                    'Контрагент' => [
                        'СвФЛ' => [
                           'ИНН' => $ipData['inn'],
                            'Имя' => $ipData['name'],
                            'Отчество' => $ipData['patronymic'],
                            'Фамилия' => $ipData['surname'],
                        ],
                    ],
                    'Вложение' => [
                        [
                            'Идентификатор' => $guid,
                            'Файл' => [
                                'ДвоичныеДанные' => base64_encode($xmlData),
                                'Имя' => $fileName,
                            ],
                        ],
                    ],
                ],
            ],
            'id' => 0,
        ];

        return $this->request(
            $this->config['sbis']['service_url'],
            'POST',
            json_encode($document, JSON_UNESCAPED_UNICODE),
            true
        );
    }

    private function formatKitsForDocument($kits)
    {
        return array_map(function ($kit) {
            return [
                'Номенклатура' => $kit['nomNumber'],
                'Наименование' => $kit['name'],
                'Количество' => $kit['quantity'] ?? 1,
                'Цена' => $kit['cost'] ?? 0,
            ];
        }, $kits);
    }

    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function createXmlDoc($fileName, $ipData, $fromWarehouse, $toWarehouse, $kits)
    {
        $guid = strtoupper($this->generateUuid());
        $date = date('d.m.Y');
        $time = date('H:i:s');
        $docNumber = 12;

        // Подсчитываем общую сумму
        $totalCol = 0;
        $totalSum = 0;
        foreach ($kits as $kit) {
            foreach ($kit['items'] ?? [] as $item) {
                if ($item['NomenclatureInfo'] === 'Product') {
                    $totalSum += $kit['quantity'] * $item['SumPlannedCost'];
                    $totalCol += $kit['quantity'] * $item['BaseQty'];
                }
            }
        }

        $xml = new DOMDocument('1.0', 'WINDOWS-1251');
        $xml->formatOutput = true;

        // Корневой элемент <Файл>
        $root = $xml->createElement('Файл');
        $root->setAttribute('ВерсияФормата', '3.01');
        $root->setAttribute('Имя', $fileName);
        $root->setAttribute('Формат', 'ВнутрПер');
        $xml->appendChild($root);

        // Элемент <Документ>
        $document = $xml->createElement('Документ');
        $document->setAttribute('Время', $time);
        $document->setAttribute('Дата', $date);
        //$document->setAttribute('Номер', $docNumber);
        $root->appendChild($document);

        // Элемент <ТаблДок>
        $table = $xml->createElement('ТаблДок');
        $document->appendChild($table);

        $total = $xml->createElement('ИтогТабл');
        $total->setAttribute('Кол_во', $totalCol);
        $total->setAttribute('Сумма', $totalSum);
        $table->appendChild($total);

        // Добавляем товары
        $itemNumber = 1;
        foreach ($kits as $kit) {
            foreach ($kit['items'] as $item) {
                if ($item['NomenclatureInfo'] !== 'Product') {
                    continue;
                }
                $product = $xml->createElement('СтрТабл');
                $product->setAttribute('ЕдИзм', $item['BaseMeasureUnitParsed']['base']['abbr']);
                $product->setAttribute('Идентификатор', $item['nomNumber']);
                $product->setAttribute('Кол_во', $item['BaseQty'] * $kit['quantity']);
                $product->setAttribute('Название', htmlspecialchars($item['Label'], ENT_XML1, 'UTF-8'));
                $product->setAttribute('ПорНомер', $itemNumber);
                $product->setAttribute('Сумма', number_format($item['SumPlannedCost'] * $kit['quantity'], 2, '.', ''));
                $product->setAttribute('Цена', number_format($item['SumPlannedCost'], 2, '.', ''));
                $table->appendChild($product);
                $itemNumber++;
            }
        }

        // Элемент <Отправитель>
        $sender = $xml->createElement('Отправитель');
        $sender->setAttribute('Название', 'ИП ' . $ipData['surname'] . ' ' . $ipData['name'] . ' ' . $ipData['patronymic']);
        $document->appendChild($sender);

        $senderOrg = $xml->createElement('СвФЛ');
        $senderOrg->setAttribute('ИНН', $ipData['inn']);
        $senderOrg->setAttribute('Имя', $ipData['name']);
        $senderOrg->setAttribute('Наименование', 'ИП ' . $ipData['surname'] . ' ' . $ipData['name'] . ' ' . $ipData['patronymic']);
        $senderOrg->setAttribute('Отчество', $ipData['patronymic']);
        $senderOrg->setAttribute('Фамилия', $ipData['surname']);
        $sender->appendChild($senderOrg);

        $senderWarehouse = $xml->createElement('Склад');
        $senderWarehouse->setAttribute('Идентификатор', $fromWarehouse['id']);
        $senderWarehouse->setAttribute('Название', $fromWarehouse['name']);
        $sender->appendChild($senderWarehouse);

        // Элемент <Получатель>
        $receiver = $xml->createElement('Получатель');
        $document->appendChild($receiver);

        $receiverWarehouse = $xml->createElement('Склад');
        $receiverWarehouse->setAttribute('Идентификатор', $toWarehouse['id']);
        $receiverWarehouse->setAttribute('Название', $toWarehouse['name']);
        $receiver->appendChild($receiverWarehouse);

        $receiverOrg = $xml->createElement('СвФЛ');
        $receiverOrg->setAttribute('ИНН', $ipData['inn']);
        $receiverOrg->setAttribute('Имя', $ipData['name']);
        $receiverOrg->setAttribute('Отчество', $ipData['patronymic']);
        $receiverOrg->setAttribute('Фамилия', $ipData['surname']);
        $receiver->appendChild($receiverOrg);

        return $xml->saveXML();
    }
}
