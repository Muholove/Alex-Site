<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Habilitar exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar ambiente de execução
$env = [
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'request_time' => date('Y-m-d H:i:s'),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
];

// Verificar variáveis de ambiente do Appwrite
$appwrite_env = [
    'APPWRITE_ENDPOINT' => getenv('APPWRITE_ENDPOINT') ?: 'Not set',
    'APPWRITE_PROJECT_ID' => getenv('APPWRITE_PROJECT_ID') ?: 'Not set',
    'APPWRITE_DATABASE_ID' => getenv('APPWRITE_DATABASE_ID') ?: 'Not set',
    'APPWRITE_VIDEO_COLLECTION_ID' => getenv('APPWRITE_VIDEO_COLLECTION_ID') ?: 'Not set',
    'APPWRITE_THUMBNAIL_BUCKET_ID' => getenv('APPWRITE_THUMBNAIL_BUCKET_ID') ?: 'Not set',
    'APPWRITE_VIDEO_BUCKET_ID' => getenv('APPWRITE_VIDEO_BUCKET_ID') ?: 'Not set',
    'APPWRITE_API_KEY' => getenv('APPWRITE_API_KEY') ? 'Set (hidden)' : 'Not set'
];

// Checar permissões de diretório
$dir_check = [
    'current_dir' => getcwd(),
    'is_readable' => is_readable('.'),
    'is_writable' => is_writable('.'),
    'files_count' => count(glob('*'))
];

// Verificar extensões PHP
$extensions = get_loaded_extensions();
$required_extensions = ['curl', 'json', 'zip', 'mbstring'];
$missing_extensions = array_diff($required_extensions, $extensions);

// Verificar conexão com a internet
$internet_check = [
    'status' => false,
    'message' => ''
];
try {
    $curl = curl_init('https://cloud.appwrite.io/v1/health');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($response !== false) {
        $internet_check['status'] = true;
        $internet_check['message'] = 'Connection successful';
        $internet_check['http_code'] = $http_code;
    } else {
        $internet_check['message'] = 'Connection failed: ' . $curl_error;
    }
} catch (Exception $e) {
    $internet_check['message'] = 'Exception: ' . $e->getMessage();
}

// Testar conexão com Appwrite
$appwrite_check = [
    'status' => false,
    'message' => ''
];
if (getenv('APPWRITE_ENDPOINT') && getenv('APPWRITE_PROJECT_ID') && getenv('APPWRITE_API_KEY')) {
    try {
        $curl = curl_init(getenv('APPWRITE_ENDPOINT') . '/health');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . getenv('APPWRITE_PROJECT_ID'),
            'X-Appwrite-Key: ' . getenv('APPWRITE_API_KEY')
        ]);
        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($response !== false) {
            $appwrite_check['status'] = ($http_code >= 200 && $http_code < 300);
            $appwrite_check['message'] = $appwrite_check['status'] ? 'Connection successful' : 'Connection failed with HTTP code ' . $http_code;
            $appwrite_check['http_code'] = $http_code;
            $appwrite_check['response'] = json_decode($response, true);
        } else {
            $appwrite_check['message'] = 'Connection failed: ' . $curl_error;
        }
    } catch (Exception $e) {
        $appwrite_check['message'] = 'Exception: ' . $e->getMessage();
    }
} else {
    $appwrite_check['message'] = 'Missing required Appwrite environment variables';
}

// Resultados e recomendações
$result = [
    'status' => 'success',
    'timestamp' => time(),
    'environment' => $env,
    'appwrite_environment' => $appwrite_env,
    'directory_check' => $dir_check,
    'php_extensions' => [
        'all' => $extensions,
        'missing_required' => $missing_extensions,
        'all_required_installed' => count($missing_extensions) === 0
    ],
    'internet_check' => $internet_check,
    'appwrite_check' => $appwrite_check,
    'recommendations' => []
];

// Adicionar recomendações com base nos resultados
if (!$internet_check['status']) {
    $result['recommendations'][] = 'Não foi possível conectar à internet. Verifique a conexão da sua instância Render.';
}

if (!$appwrite_check['status']) {
    $result['recommendations'][] = 'Não foi possível conectar ao Appwrite. Verifique suas credenciais e se o serviço está disponível.';
}

if (count($missing_extensions) > 0) {
    $result['recommendations'][] = 'Faltam extensões PHP necessárias: ' . implode(', ', $missing_extensions) . '. Atualize seu Dockerfile para incluí-las.';
}

foreach ($appwrite_env as $key => $value) {
    if ($value === 'Not set') {
        $result['recommendations'][] = "A variável de ambiente $key não está definida. Configure-a no painel do Render.";
    }
}

// Verificar se conseguimos acessar a coleção de vídeos
if ($appwrite_check['status'] && getenv('APPWRITE_DATABASE_ID') && getenv('APPWRITE_VIDEO_COLLECTION_ID')) {
    try {
        $curl = curl_init(getenv('APPWRITE_ENDPOINT') . '/databases/' . getenv('APPWRITE_DATABASE_ID') . '/collections/' . getenv('APPWRITE_VIDEO_COLLECTION_ID'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . getenv('APPWRITE_PROJECT_ID'),
            'X-Appwrite-Key: ' . getenv('APPWRITE_API_KEY')
        ]);
        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $collection_check = [
            'status' => ($http_code >= 200 && $http_code < 300),
            'http_code' => $http_code
        ];
        
        if (!$collection_check['status']) {
            $result['recommendations'][] = "Não foi possível acessar a coleção de vídeos. Verifique se o ID da coleção está correto e se sua API Key tem permissões adequadas.";
        }
        
        $result['collection_check'] = $collection_check;
    } catch (Exception $e) {
        $result['recommendations'][] = "Erro ao verificar a coleção de vídeos: " . $e->getMessage();
    }
}

// Se o Appwrite está configurado, verificar as plataformas
$result['recommendations'][] = 'Certifique-se de adicionar ' . ($_SERVER['HTTP_HOST'] ?? 'seu domínio') . ' como plataforma permitida no console do Appwrite em Settings > Platforms.';

// Retornar resultado
echo json_encode($result, JSON_PRETTY_PRINT);
?> 