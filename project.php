<?php

require 'vendor/autoload.php';
require 'simplexml.php';

$client = Elasticsearch\ClientBuilder::create()->setHosts(['elasticsearch'])->build();

ini_set('max_execution_time', 1200);
ini_set('memory_limit', '1024M');

$response = [];
$searchTerm = "";
$pageSize = 25;
$pageIndex = 0;
$resultSetSize = null;
$pageCount = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = $_POST['searchTerm'];

    if ($searchTerm === "") {
        echo "Search term is empty";
    } else {
        $response = $client->indices()->exists([
            'index' => 'fvb_index',
        ]);

        if (!$response) {
            $indexParams = [

                'index' => 'fvb_index',
                'body' => [
                    'settings' => [
                        'index.mapping.total_fields.limit' => 1000000,
                    ],
                    'mappings' => [
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'sku' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'status' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'c4_status' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'm3_status' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'is_returnable' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'allow_purchase' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'allow_guest_purchase' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'allow_back_orders' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'manufacturer' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'c4_sysid' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'replaces' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'qty_increments' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'ean_number' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'updated_at' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'dangerous_goods' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'competitor_references' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'supplier' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            'visibility' => [
                                'type' => 'text',
                                'copy_to' => '_all',
                            ],
                            '_all' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ];
            $client->indices()->create($indexParams);

            $arr = xml_to_array();

            $params = ['body' => []];

            $i = 1;

            foreach ($arr as $body) {
                $params['body'][] = [
                    'index' => [
                        '_index' => 'fvb_index',
                        '_id' => $i,
                    ],
                ];

                $params['body'][] = $body;

                // Every 200 documents stop and send the bulk request
                if ($i % 200 == 0) {
                    $responses = $client->bulk($params);

                    // erase the old bulk request
                    $params = ['body' => []];

                    // unset the bulk response when you are done to save memory
                    unset($responses);
                }

                $i++;
            }

            // Send the last batch if it exists
            if (!empty($params['body'])) {
                $responses = $client->bulk($params);
            }
        }

        $searchParams = [
            'index' => 'fvb_index',
            'size' => $pageSize,
            'body' => [
                'query' => [
                    'match' => [
                        '_all' => $searchTerm,
                    ],
                ],
            ],
        ];

        $response = $client->search($searchParams);
        $resultSetSize = $response['hits']['total']['value'];
        $pageCount = $resultSetSize % $pageSize == 0 ? $resultSetSize / $pageSize : (int) ($resultSetSize / $pageSize) + 1;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && count($_GET) == 2 && array_key_exists('pageIndex', $_GET) && array_key_exists('searchTerm', $_GET) && is_numeric($_GET['pageIndex']) && $_GET['searchTerm'] !== "") {
    $pageIndex = $_GET['pageIndex'];
    $searchTerm = $_GET['searchTerm'];

    $search_params = [
        'index' => 'fvb_index',
        'from' => $pageIndex * $pageSize,
        'size' => $pageSize,
        'body' => [
            'query' => [
                'match' => [
                    '_all' => $searchTerm,
                ],
            ],
        ],
    ];

    $response = $client->search($searchParams);
    $resultSetSize = $response['hits']['total']['value'];
    $pageCount = $resultSetSize % $pageSize == 0 ? $resultSetSize / $pageSize : (int) ($resultSetSize / $pageSize) + 1;
}
