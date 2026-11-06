<?php
/**
 * CLI-скрипт импорта видеоконтента для TeaseAdultCMS
 * 
 * Использование:
 *   php import.php [url] [limit] [category]
 * 
 * Примеры:
 *   php import.php
 *   php import.php http://92.115.179.18/index.php?n=100
 *   php import.php http://92.115.179.18/index.php?n=100 50
 *   php import.php http://92.115.179.18/index.php?n=100 50 amateur
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run via CLI (command line).\n");
}

set_time_limit(0);
mb_internal_encoding("UTF-8");

define('_CLI_RUN_', true);

// Подключаем ядро
require_once __DIR__ . '/config.php';
require_once _LIB_ . 'func.php';

// Инициализация базовых структур, если проект запускается впервые
if (!file_exists(_DATA_ . 'READY')) {
    echo "[!] Инициализация структуры сайта...\n";
    $structures = [
        _CACHE_ . 'pages/' => 0,
        _CACHE_ . 'counter/' => 1,
        _CACHE_ . 'cats/' => 1,
        _CACHE_ . 'tags/' => 1,
        _CACHE_ . 'sitemap/' => 1,
        _ROOT_ . 'static/' => 0,
        _DATA_ . 'adv/' => 1
    ];
    foreach ($structures as $struct => $st) {
        if (!file_exists($struct)) {
            @mkdir($struct, 0777, true);
            if ($st === 1) {
                @file_put_contents($struct . '.htaccess', "<FilesMatch \"\.(dat|txt)\.?.*\">\r\nOrder allow,deny\r\nDeny from all\r\n</FilesMatch>", LOCK_EX);
            }
        }
    }
    if (!file_exists(_DATA_ . 'cloakBox.php')) {
        @file_put_contents(_DATA_ . 'cloakBox.php', '');
    }
    STARTER();
    @file_put_contents(_DATA_ . 'READY', time(), LOCK_EX);
}

if (file_exists(_DATA_ . 'dynOptions.php')) {
    require_once _DATA_ . 'dynOptions.php';
}
require_once _LIB_ . 'loadFiles.php';
$TITLE_SPIN = file(_DATA_ . 'TITLE-SPIN.txt');

// Аргументы командной строки
$sourceUrl = isset($argv[1]) && !empty(trim($argv[1])) ? trim($argv[1]) : 'http://92.115.179.18/index.php?n=50';
$limit = (isset($argv[2]) && (int)$argv[2] > 0) ? (int)$argv[2] : 0; // 0 = без лимита: обрабатываем все записи из ответа, пачка задаётся n= в URL
$fixedCategory = isset($argv[3]) ? trim($argv[3]) : '';

echo "\n=========================================\n";
echo "   TeaseAdultCMS — CLI Video Importer   \n";
echo "=========================================\n";
echo "Источник:   {$sourceUrl}\n";
echo "Лимит:      " . ($limit > 0 ? $limit : "нет (все записи)") . "\n";
echo "Категория:  " . (!empty($fixedCategory) ? $fixedCategory : "(Автоматически из источника)") . "\n";
echo "-----------------------------------------\n";
echo "[*] Запрос данных из источника...\n";

$rawResponse = getCurl($sourceUrl);

if (empty($rawResponse)) {
    die("[ОШИБКА] Не удалось получить данные от источника (пустой ответ или ошибка сети).\n");
}

$jsonData = json_decode($rawResponse, true);
$importedCount = 0;
$skippedCount = 0;

if ($jsonData && isset($jsonData['items']) && is_array($jsonData['items'])) {
    $items = $jsonData['items'];
    $totalInBatch = count($items);
    echo "[+] Получено записей из источника: {$totalInBatch}\n";
    echo "[*] Начинаем обработку и скачивание превью...\n\n";

    $cnt = 0;
    foreach ($items as $idx => $item) {
        if ($limit > 0 && $cnt >= $limit) {
            break;
        }

        $embed = trim($item['frame'] ?? '');
        $titleRaw = trim($item['title'] ?? '');
        $tagsRaw = trim($item['tags'] ?? '');
        $categoriesRaw = trim($item['categories'] ?? '');
        $timeRaw = $item['time'] ?? 0;
        $origImg = trim($item['orig_img'] ?? '');
        $itemId = $item['id'] ?? ($idx + 1);

        // Заголовок и URI
        if (mb_strlen($titleRaw) > 5) {
            $title = mb_ucwords($titleRaw);
            $uri = str2id(mb_strtolower($titleRaw));
        } else {
            $targetCat = !empty($fixedCategory) ? $fixedCategory : 'video';
            $title = spinTitle($targetCat);
            $uri = str2id(mb_strtolower($title));
        }

        $uri = preg_replace('/(\-){2,}/', '$1', $uri);
        $uri = trim($uri, '-');
        if (strlen($uri) < 5) {
            $uri = 'v-' . (!empty($item['id']) ? $item['id'] : substr(sha1($title . mt_rand()), 0, 10));
        }

        // Проверка дублей
        if (file_exists(_CACHE_ . 'pages/' . $uri . '.php')) {
            $skippedCount++;
            echo "[-] [ДУБЛЬ] {$title} (пропущено)\n";
            continue;
        }

        // Теги
        $tagsArray = [];
        if (!empty($tagsRaw)) {
            $normalizedTags = str_replace([';', ','], ' ', $tagsRaw);
            $normalizedTags = preg_replace("~[^" . _LETTERS_ . _ALLOWED_ . "]~ius", " ", $normalizedTags);
            $tagsArray = cleanArray(explode(' ', $normalizedTags));
            $tagsArray = array_values(array_filter(array_map(function($t) {
                return trim(str_replace('-', ' ', $t));
            }, $tagsArray)));
        }
        if (count($tagsArray) < 3) {
            $tagsArray = array_merge($tagsArray, $DEF_TAGS);
        }
        $tagsArray = array_unique($tagsArray);

        // Длительность
        if (is_numeric($timeRaw)) {
            $duration = ($timeRaw > 0) ? gmdate("i:s", (int)$timeRaw) : gmdate("i:s", mt_rand(300, 1200));
        } elseif (!empty($timeRaw)) {
            $duration = (strpos($timeRaw, ':') !== false) ? $timeRaw : gmdate("i:s", (int)$timeRaw);
        } else {
            $duration = gmdate("i:s", mt_rand(300, 1200));
        }

        // Категории
        $catsToAssign = [];
        if (!empty($fixedCategory)) {
            $catsToAssign[] = $fixedCategory;
        }
        if (!empty($categoriesRaw)) {
            $catsFromApi = explode(';', $categoriesRaw);
            foreach ($catsFromApi as $cCandidate) {
                $cTrim = trim($cCandidate);
                if (!empty($cTrim)) {
                    $catsToAssign[] = $cTrim;
                }
            }
        }
        if (empty($catsToAssign)) {
            $catsToAssign = [$DEFCAT_LIST[array_rand($DEFCAT_LIST)]];
        }
        $catsToAssign = array_values(array_unique($catsToAssign));

        // Запрещённые категории — пропускаем видео (без скачивания превью и без логов)
        if (isBannedCats($catsToAssign)) {
            $skippedCount++;
            echo "[-] [BAN-CAT] ID:{$itemId} | пропущено (запрещённая категория)\n";
            continue;
        }

        $data = [
            'embc' => $embed,
            'title' => $title,
            'tags' => $tagsArray,
            'time' => $duration,
            'thumb' => $origImg,
            'frames' => [$origImg]
        ];

        // Создаем страницу (вернет false, если превью не удалось скачать)
        $created = createPage($data, $uri, $catsToAssign);

        if ($created !== false) {
            $importedCount++;
            $cnt++;
            $shortTitle = mb_substr($title, 0, 45) . (mb_strlen($title) > 45 ? '...' : '');
            echo "[+] [OK #{$cnt}] ID:{$itemId} | {$shortTitle}\n";
        } else {
            $skippedCount++;
            echo "[x] [БИТОЕ ПРЕВЬЮ] ID:{$itemId} | Залогировано в data/failed_videos.jsonl\n";
            $rawItemJson = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            @file_put_contents(_DATA_ . 'failed_videos.jsonl', $rawItemJson . "\n", FILE_APPEND | LOCK_EX);
        }
    }

} else {
    // Обработка CSV
    echo "[!] Распознан старый формат данных (CSV)...\n";
    $csvLines = explode("\n", $rawResponse);
    $cnt = 0;
    foreach ($csvLines as $line) {
        if ($limit > 0 && $cnt >= $limit) {
            break;
        }
        $partLine = cleanArray(explode('|', $line));
        if (count($partLine) >= 5) {
            $embed = str_replace('""', '"', trim($partLine[0], '"'));
            $titleRaw = cleanStr(str_replace('""', '"', trim($partLine[1], '"')));

            if (mb_strlen($titleRaw) > 5) {
                $title = mb_ucwords($titleRaw);
                $uri = str2id(mb_strtolower($titleRaw));
            } else {
                $targetCat = !empty($fixedCategory) ? $fixedCategory : 'video';
                $title = spinTitle($targetCat);
                $uri = str2id(mb_strtolower($title));
            }
            $uri = preg_replace('/(\-){2,}/', '$1', $uri);
            $uri = trim($uri, '-');
            if (strlen($uri) < 5) {
                $uri = sha1($title);
            }

            if (file_exists(_CACHE_ . 'pages/' . $uri . '.php')) {
                $skippedCount++;
                continue;
            }

            $rawTags = str_replace('""', '"', trim($partLine[2], '"'));
            $rawTags = preg_replace("~[^" . _LETTERS_ . _ALLOWED_ . "]~ius", "", $rawTags);
            $tags = cleanArray(explode(',', str_replace(';', ' ', $rawTags)));

            $timePart = trim($partLine[3], '"');
            $duration = !empty($timePart) ? ((strpos($timePart, ':') !== false) ? $timePart : gmdate("i:s", (int)$timePart)) : gmdate("i:s", mt_rand(300, 1200));
            $thumb = trim($partLine[4], '"');
            $frames = isset($partLine[5]) ? explode(";", trim(cleanStr($partLine[5]), '"')) : [$thumb];
            $catToAssign = !empty($fixedCategory) ? $fixedCategory : $DEFCAT_LIST[array_rand($DEFCAT_LIST)];

            // Запрещённая категория — пропускаем (без логов)
            if (isBannedCats([$catToAssign])) {
                $skippedCount++;
                echo "[-] [BAN-CAT] {$title}\n";
                continue;
            }

            $data = [
                'embc' => $embed,
                'title' => $title,
                'tags' => $tags,
                'time' => $duration,
                'thumb' => $thumb,
                'frames' => $frames
            ];

            $created = createPage($data, $uri, $catToAssign);
            if ($created !== false) {
                $importedCount++;
                $cnt++;
                echo "[+] [OK #{$cnt}] {$title}\n";
            } else {
                $skippedCount++;
                echo "[x] [БИТОЕ ПРЕВЬЮ] {$title}\n";
            }
        }
    }
}

echo "\n-----------------------------------------\n";
echo "ИТОГ РАБОТЫ:\n";
echo "  - Добавлено страниц:   {$importedCount}\n";
echo "  - Пропущено/Ошибок:    {$skippedCount}\n";
echo "=========================================\n\n";
