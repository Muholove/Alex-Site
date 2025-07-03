<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Habilitar exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Appwrite configuration
$appwrite = [
    'projectId' => '6852ab51002ca9bf6bd4',
    'databaseId' => '681f818100229727cfc0',
    'videoCollectionId' => '681f81a4001d1281896e',
    'thumbnailBucketId' => '681f82280005e6182fdd',
    'videoBucketId' => '681f820d00319f2aa58b',
    'apiKey' => 'standard_f291de00b8dc2241d3248c9786faa23a9e22f81d3ad95842dc9d955f42464bc179f70721cd83c483dcd5b2d596f529f4a77d67afb37f80040941bb7235a4a5f6e16ad4df4ba8299352e8ec59344efbd8a59da626fd684db28ec14221428d21c57c566b52ad84c6dde8055f3c189e93d347200a4778a065dae271c14c4105db0e',
    'endpoint' => 'https://cloud.appwrite.io/v1'
];

// Use environment variables if available
if(getenv('APPWRITE_ENDPOINT')) {
    $appwrite['endpoint'] = getenv('APPWRITE_ENDPOINT');
}
if(getenv('APPWRITE_PROJECT_ID')) {
    $appwrite['projectId'] = getenv('APPWRITE_PROJECT_ID');
}
if(getenv('APPWRITE_API_KEY')) {
    $appwrite['apiKey'] = getenv('APPWRITE_API_KEY');
}

// Diagnóstico do ambiente
$diagnostics = [
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'request_time' => date('Y-m-d H:i:s'),
    'environment' => [
        'APPWRITE_ENDPOINT' => getenv('APPWRITE_ENDPOINT') ?: 'Not set',
        'APPWRITE_PROJECT_ID' => getenv('APPWRITE_PROJECT_ID') ?: 'Not set',
        'APPWRITE_API_KEY' => getenv('APPWRITE_API_KEY') ? 'Set (hidden)' : 'Not set'
    ],
    'configuration' => [
        'endpoint' => $appwrite['endpoint'],
        'projectId' => $appwrite['projectId'],
        'databaseId' => $appwrite['databaseId'],
        'videoCollectionId' => $appwrite['videoCollectionId']
    ]
];

$testResults = [];

// Teste 1: Verificar status do serviço Appwrite
try {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $appwrite['endpoint'] . '/health',
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    $testResults['service_status'] = [
        'success' => $status >= 200 && $status < 300,
        'status_code' => $status,
        'response' => $error ? 'Error: ' . $error : json_decode($response, true)
    ];
} catch (Exception $e) {
    $testResults['service_status'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Teste 2: Autenticação com a API Key
try {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $appwrite['endpoint'] . '/account',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . $appwrite['projectId'],
            'X-Appwrite-Key: ' . $appwrite['apiKey']
        ]
    ]);
    
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    $testResults['api_key_auth'] = [
        'success' => $status >= 200 && $status < 300,
        'status_code' => $status,
        'response' => $error ? 'Error: ' . $error : json_decode($response, true)
    ];
} catch (Exception $e) {
    $testResults['api_key_auth'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Teste 3: Lista coleções
try {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . $appwrite['projectId'],
            'X-Appwrite-Key: ' . $appwrite['apiKey']
        ]
    ]);
    
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    $testResults['list_collections'] = [
        'success' => $status >= 200 && $status < 300,
        'status_code' => $status,
        'response' => $error ? 'Error: ' . $error : json_decode($response, true)
    ];
} catch (Exception $e) {
    $testResults['list_collections'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Teste 4: Verificar documentos na coleção de vídeos
try {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . $appwrite['projectId'],
            'X-Appwrite-Key: ' . $appwrite['apiKey']
        ]
    ]);
    
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    $info = curl_getinfo($curl);
    
    curl_close($curl);
    
    $decoded = json_decode($response, true);
    
    $testResults['video_documents'] = [
        'success' => $status >= 200 && $status < 300,
        'status_code' => $status,
        'request_info' => $info,
        'document_count' => isset($decoded['documents']) ? count($decoded['documents']) : 0,
        'response' => $error ? 'Error: ' . $error : (
            isset($decoded['message']) ? ['error_message' => $decoded['message']] : 
            ['total' => $decoded['total'] ?? 0]
        )
    ];
} catch (Exception $e) {
    $testResults['video_documents'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Resumo geral dos testes
$allSuccessful = true;
foreach ($testResults as $test) {
    if (!$test['success']) {
        $allSuccessful = false;
        break;
    }
}

// Montar o resultado final
$result = [
    'all_tests_passed' => $allSuccessful,
    'diagnostics' => $diagnostics,
    'test_results' => $testResults,
    'recommendations' => []
];

// Adicionar recomendações com base nos resultados dos testes
if (!$testResults['service_status']['success']) {
    $result['recommendations'][] = 'Não foi possível conectar ao serviço Appwrite. Verifique sua conexão com a internet ou se o serviço está disponível.';
}

if (!$testResults['api_key_auth']['success']) {
    $result['recommendations'][] = 'Falha na autenticação com a API Key. Verifique se a chave API está correta e se ela possui as permissões necessárias.';
}

if (!$testResults['list_collections']['success']) {
    $result['recommendations'][] = 'Não foi possível listar as coleções. Verifique se o ID do banco de dados está correto e se sua API Key tem permissão para acessá-lo.';
}

if (!$testResults['video_documents']['success']) {
    $result['recommendations'][] = 'Não foi possível acessar os documentos da coleção de vídeos. Verifique se o ID da coleção está correto e se ela existe.';
} else if ($testResults['video_documents']['document_count'] == 0) {
    $result['recommendations'][] = 'A coleção de vídeos está vazia. Adicione documentos para que os vídeos possam ser exibidos.';
}

// Verifica se as credenciais estão vindo das variáveis de ambiente
if (getenv('APPWRITE_API_KEY') === false) {
    $result['recommendations'][] = 'A API Key não está definida como variável de ambiente. Configure-a no painel do Render em "Environment Variables".';
}

// Adicionar recomendações gerais
$result['recommendations'][] = 'Verifique se as plataformas estão configuradas no console do Appwrite para permitir solicitações de ' . ($_SERVER['HTTP_HOST'] ?? 'seu domínio');
$result['recommendations'][] = 'Verifique se o CORS está configurado corretamente no Appwrite para permitir solicitações do seu domínio.';

// Retornar resultado
echo json_encode($result, JSON_PRETTY_PRINT);
?> 