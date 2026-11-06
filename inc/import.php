<?php
set_time_limit(0);
(file_exists(_DATA_.'dynOptions.php')) ? include_once _DATA_.'dynOptions.php' : die('File <b>dynOptions.php</b> not found. Please run site first.');
include_once _LIB_.'loadFiles.php'; // загружаем файлы
$TITLE_SPIN = file(_DATA_.'TITLE-SPIN.txt'); 
$LOGi = '';
$importedCount = 0;
$skippedCount = 0;

if ($_POST) {
    $sourceUrl = trim($_POST['source_url'] ?? '');
    // Без поля pags (удалено из формы) — импортируем все записи, вернувшиеся по URL; размер пачки задаётся параметром n= в нём
    $limitPages = (isset($_POST['pags']) && (int)$_POST['pags'] > 0) ? (int)$_POST['pags'] : PHP_INT_MAX;
    $embst = (isset($_POST['embmed']) && $_POST['embmed'] === 'on') ? true : false;
    $selectedCategory = trim($_POST['default_category'] ?? '');

    if (empty($sourceUrl) || !preg_match('~^https?://~i', $sourceUrl)) {
        $LOGi .= "<div class='alert error'>Укажите корректный URL источника (начиная с http:// или https://)</div>";
    }

    if (empty($LOGi)) {
        // Подгружаем данные из источника
        $rawResponse = getCurl($sourceUrl);

        if (empty($rawResponse)) {
            $LOGi .= "<div class='alert error'>Не удалось получить ответ от источника (пустой ответ или ошибка сети).</div>";
        } else {
            // Пытаемся распарсить как JSON
            $jsonData = json_decode($rawResponse, true);

            if ($jsonData && isset($jsonData['items']) && is_array($jsonData['items'])) {
                // НОВЫЙ ФОРМАТ (JSON API)
                $items = $jsonData['items'];
                if (isset($_POST['shuffle_items']) && $_POST['shuffle_items'] === 'on') {
                    shuffle($items);
                }

                $cnt = 0;
                foreach ($items as $item) {
                    if ($cnt >= $limitPages) {
                        break;
                    }

                    $embed = trim($item['frame'] ?? '');
                    $titleRaw = trim($item['title'] ?? '');
                    $tagsRaw = trim($item['tags'] ?? '');
                    $categoriesRaw = trim($item['categories'] ?? '');
                    $timeRaw = $item['time'] ?? 0;
                    $origImg = trim($item['orig_img'] ?? '');

                    // Заголовок и URI
                    if (mb_strlen($titleRaw) > 5) {
                        $title = mb_ucwords($titleRaw);
                        $uri = str2id(mb_strtolower($titleRaw));
                    } else {
                        $targetCat = !empty($selectedCategory) ? $selectedCategory : 'video';
                        $title = spinTitle($targetCat);
                        $uri = str2id(mb_strtolower($title));
                    }

                    $uri = preg_replace('/(\-){2,}/', '$1', $uri);
                    $uri = trim($uri, '-');
                    if (strlen($uri) < 5) {
                        $uri = 'v-' . (!empty($item['id']) ? $item['id'] : substr(sha1($title . mt_rand()), 0, 10));
                    }

                    // Если страница уже существует, пропускаем во избежание дублей
                    if (file_exists(_CACHE_.'pages/'.$uri.'.php')) {
                        $skippedCount++;
                        continue;
                    }

                    // Обработка тегов (могут быть разделены ';' или ',')
                    $tagsArray = [];
                    if (!empty($tagsRaw)) {
                        $normalizedTags = str_replace([';', ','], ' ', $tagsRaw);
                        $normalizedTags = preg_replace("~[^"._LETTERS_._ALLOWED_."]~ius", " ", $normalizedTags);
                        $tagsArray = cleanArray(explode(' ', $normalizedTags));
                        // Очистка тегов от лишних тире
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

                    // Категории для сайта (поддержка множественных категорий)
                    $catsToAssign = [];
                    if (!empty($selectedCategory)) {
                        $catsToAssign[] = $selectedCategory;
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
                        continue;
                    }

                    // Собираем data
                    $data = [
                        'embc' => $embed,
                        'title' => $title,
                        'tags' => $tagsArray,
                        'time' => $duration,
                        'thumb' => $origImg,
                        'frames' => [$origImg] // кадры для псевдостраниц (если _PSEUDO_ включен)
                    ];

                    $created = createPage($data, $uri, $catsToAssign);
                    if ($created !== false) {
                        $importedCount++;
                        $cnt++;
                    } else {
                        $skippedCount++;
                        // Логируем сырую строку элемента, у которого не удалось получить превью
                        $rawItemJson = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        file_put_contents(_DATA_ . 'failed_videos.jsonl', $rawItemJson . "\n", FILE_APPEND | LOCK_EX);
                    }
                }

                $LOGi .= "<div class='alert success'>Успешно импортировано: <b>{$importedCount}</b> видео. Пропущено (дубли или не удалось получить превью): <b>{$skippedCount}</b>.</div>";
                if ($skippedCount > 0) {
                    $LOGi .= "<div class='alert' style='background: #4a4e69; color: #fff;'>Видео без превью сохранены в лог: <code>data/failed_videos.jsonl</code></div>";
                }

            } else {
                // ОБРАТНАЯ СОВМЕСТИМОСТЬ: СТАРЫЙ ФОРМАТ (CSV с разделителем '|')
                $csvLines = explode("\n", $rawResponse);
                if (isset($_POST['shuffle_items']) && $_POST['shuffle_items'] === 'on') {
                    shuffle($csvLines);
                }

                $cnt = 0;
                foreach ($csvLines as $line) {
                    if ($cnt >= $limitPages) {
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
                            $targetCat = !empty($selectedCategory) ? $selectedCategory : 'video';
                            $title = spinTitle($targetCat);
                            $uri = str2id(mb_strtolower($title));
                        }
                        $uri = preg_replace('/(\-){2,}/', '$1', $uri);
                        $uri = trim($uri, '-');
                        if (strlen($uri) < 5) {
                            $uri = sha1($title);
                        }

                        if (file_exists(_CACHE_.'pages/'.$uri.'.php')) {
                            $skippedCount++;
                            continue;
                        }

                        $rawTags = str_replace('""', '"', trim($partLine[2], '"'));
                        $rawTags = preg_replace("~[^"._LETTERS_._ALLOWED_."]~ius", "", $rawTags);
                        $tags = cleanArray(explode(',', str_replace(';', ' ', $rawTags)));

                        $timePart = trim($partLine[3], '"');
                        $duration = !empty($timePart) ? ((strpos($timePart, ':') !== false) ? $timePart : gmdate("i:s", (int)$timePart)) : gmdate("i:s", mt_rand(300, 1200));

                        $thumb = trim($partLine[4], '"');
                        $frames = isset($partLine[5]) ? explode(";", trim(cleanStr($partLine[5]), '"')) : [$thumb];

                        $catToAssign = !empty($selectedCategory) ? $selectedCategory : $DEFCAT_LIST[array_rand($DEFCAT_LIST)];

                        // Запрещённая категория — пропускаем (без логов)
                        if (isBannedCats([$catToAssign])) {
                            $skippedCount++;
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

                        createPage($data, $uri, $catToAssign);
                        $importedCount++;
                        $cnt++;
                    }
                }

                if ($importedCount > 0) {
                    $LOGi .= "<div class='alert success'>Успешно импортировано (CSV): <b>{$importedCount}</b> видео.</div>";
                } else {
                    $LOGi .= "<div class='alert error'>Не удалось распознать формат данных (ни JSON items, ни CSV |). Проверьте источник.</div>";
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>Импорт контента — TeaseAdultCMS</title>
	<script type='text/javascript' src='/view/media/js/jquery.js?ver=1.12.4'></script>
	<script type='text/javascript' src='/view/media/js/jquery-migrate.min.js?ver=1.4.1'></script>
	<style>
		:root {
			--bg: #16161c;
			--card: #23232e;
			--card-border: #34344a;
			--text: #e8eaf2;
			--muted: #9aa0b5;
			--accent: #ef233c;
			--accent-dark: #c40f28;
			--success: #2a9d8f;
			--error: #e63946;
			--input-bg: #16161c;
			--code: #ffd166;
			--radius: 12px;
		}
		* { box-sizing: border-box; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
			background: radial-gradient(1200px 600px at 50% -10%, #262639 0%, var(--bg) 60%);
			background-color: var(--bg);
			color: var(--text);
			margin: 0;
			padding: 32px 16px;
			line-height: 1.55;
			-webkit-font-smoothing: antialiased;
		}
		.container {
			max-width: 720px;
			margin: 0 auto;
			background: var(--card);
			border: 1px solid var(--card-border);
			border-radius: 16px;
			padding: 32px;
			box-shadow: 0 12px 40px rgba(0,0,0,.45);
		}
		h1 {
			margin: 0 0 24px;
			color: #fff;
			border-bottom: none;
			padding-bottom: 0;
			font-size: 22px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		h1::before {
			content: "";
			width: 5px;
			height: 24px;
			border-radius: 3px;
			background: var(--accent);
			flex-shrink: 0;
		}
		.form-group { margin-bottom: 20px; }
		label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			font-size: 14px;
			color: var(--muted);
		}
		input[type="text"] {
			width: 100%;
			padding: 12px 14px;
			background: var(--input-bg);
			border: 1px solid var(--card-border);
			border-radius: var(--radius);
			color: #fff;
			font-size: 15px;
			transition: border-color .15s, box-shadow .15s;
		}
		input[type="text"]:focus {
			border-color: var(--accent);
			box-shadow: 0 0 0 3px rgba(239,35,60,.25);
			outline: none;
		}
		.hint {
			margin-top: 8px;
			font-size: 13px;
			color: var(--muted);
		}
		.quick-link {
			display: inline-block;
			margin-top: 6px;
			padding: 4px 12px;
			border: 1px solid var(--card-border);
			border-radius: 999px;
			color: #ff8296;
			font-size: 13px;
			cursor: pointer;
			transition: border-color .15s, background .15s;
		}
		.quick-link:hover {
			border-color: var(--accent);
			background: rgba(239,35,60,.12);
			text-decoration: none;
		}
		.checkbox-group {
			display: flex;
			align-items: flex-start;
			gap: 10px;
			margin-top: 10px;
			padding: 12px 14px;
			background: var(--input-bg);
			border: 1px solid var(--card-border);
			border-radius: var(--radius);
			font-size: 14px;
		}
		.checkbox-group input {
			width: 18px;
			height: 18px;
			margin: 1px 0 0;
			accent-color: var(--accent);
			cursor: pointer;
			flex-shrink: 0;
		}
		.checkbox-group label { margin: 0; cursor: pointer; color: var(--text); font-weight: 400; }
		.cli-box {
			background: var(--input-bg);
			border: 1px dashed var(--card-border);
			border-radius: var(--radius);
			padding: 16px;
			margin: 22px 0;
			font-size: 14px;
		}
		.cli-title { font-weight: 700; margin-bottom: 10px; color: #fff; }
		.cli-cmd {
			display: block;
			font-family: Consolas, Menlo, monospace;
			font-size: 13px;
			background: #0e0e13;
			border: 1px solid var(--card-border);
			border-radius: 8px;
			padding: 10px 12px;
			overflow-x: auto;
			white-space: nowrap;
			color: var(--code);
		}
		.copy-btn {
			margin-top: 10px;
			background: transparent;
			color: var(--muted);
			border: 1px solid var(--card-border);
			padding: 7px 14px;
			font-size: 13px;
			border-radius: 8px;
			cursor: pointer;
			transition: color .15s, border-color .15s;
		}
		.copy-btn:hover { color: #fff; border-color: var(--accent); }
		.cli-hint { margin-top: 10px; font-size: 13px; color: var(--muted); line-height: 1.7; }
		.cli-hint code, .info-box code {
			font-family: Consolas, Menlo, monospace;
			font-size: 12.5px;
			color: var(--code);
		}
		.btn-submit {
			width: 100%;
			background: linear-gradient(180deg, #f43a52, var(--accent-dark));
			color: #fff;
			border: none;
			padding: 15px 28px;
			font-size: 16px;
			font-weight: bold;
			letter-spacing: .03em;
			border-radius: var(--radius);
			cursor: pointer;
			transition: transform .1s, box-shadow .15s, filter .15s;
			margin-top: 6px;
			box-shadow: 0 6px 18px rgba(239,35,60,.35);
		}
		.btn-submit:hover { filter: brightness(1.08); box-shadow: 0 8px 22px rgba(239,35,60,.45); }
		.btn-submit:active { transform: translateY(1px); }
		.alert {
			padding: 14px 18px;
			border-radius: var(--radius);
			border-left: 4px solid var(--muted);
			margin-bottom: 20px;
			font-size: 15px;
		}
		.alert.success { background: rgba(42,157,143,.15); border-color: var(--success); color: #d7fff8; }
		.alert.success::before { content: "\2713  "; font-weight: bold; }
		.alert.error { background: rgba(230,57,70,.15); border-color: var(--error); color: #ffe0e3; }
		.alert.error::before { content: "\2715  "; font-weight: bold; }
		.info-box {
			background: var(--input-bg);
			border: 1px solid var(--card-border);
			border-left: 4px solid var(--accent);
			padding: 16px;
			border-radius: 10px;
			margin-top: 25px;
			font-size: 14px;
			line-height: 1.65;
			color: var(--muted);
		}
		.info-box b { color: var(--text); }
		@media (max-width: 640px) {
			body { padding: 16px 10px; }
			.container { padding: 22px 18px; }
			h1 { font-size: 19px; }
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>Наполнение сайта (Импорт видео)</h1>

		<?php echo $LOGi; ?>

		<form action="/?import" method="POST">
			<div class="form-group">
				<label for="source_url">URL Источника данных:</label>
				<input type="text" id="source_url" name="source_url" value="<?php echo htmlspecialchars($_POST['source_url'] ?? 'http://92.115.179.18/index.php?n=50'); ?>" placeholder="http://92.115.179.18/index.php?n=50" required>
				<div class="hint">
					Быстрый пример: 
					<span class="quick-link" onclick="$('#source_url').val('http://92.115.179.18/index.php?n=50');">http://92.115.179.18/index.php?n=50</span>
				</div>
			</div>

			<div class="form-group">
				<div class="checkbox-group">
					<input type="checkbox" id="embmed" name="embmed" <?php if(isset($_POST['embmed']) && $_POST['embmed'] === 'on') echo 'checked'; ?>>
					<label for="embmed">
						Использовать внешние ссылки на картинки (не скачивать превью на сервер, быстрее)
					</label>
				</div>
				<div class="checkbox-group">
					<input type="checkbox" id="shuffle_items" name="shuffle_items" checked>
					<label for="shuffle_items">
						Перемешивать элементы перед добавлением
					</label>
				</div>
			</div>

			<div class="cli-box">
			<div class="cli-title">Импорт через консоль (CLI)</div>
			<code class="cli-cmd" id="cli-cmd">php import.php http://92.115.179.18/index.php?n=50</code>
			<button type="button" class="copy-btn" onclick="copyCli()">Копировать команду</button>
			<div class="cli-hint">
				Формат: <code>php import.php [url] [limit] [category]</code> — limit и category необязательны.<br>
				Примеры: <code>php import.php</code> (дефолт: 50 записей) ·
				<code>php import.php http://92.115.179.18/index.php?n=100 50</code> ·
				<code>php import.php http://92.115.179.18/index.php?n=100 50 amateur</code>
			</div>
			</div>
			<button type="submit" class="btn-submit">ЗАПУСТИТЬ ИМПОРТ</button>
		</form>

		<div class="info-box">
			<b>Поддерживаемые форматы источника:</b><br>
			1. <b>JSON API</b> (новый): объект <code>{"count": X, "items": [{"id": ..., "frame": "...", "orig_img": "...", "title": "...", "tags": "...", "categories": "...", "time": ...}]}</code><br>
			2. <b>CSV / Текстовый</b> (старый): строки формата <code>embed|title|tags|duration|thumbnail|flipbook</code><br>
			Импортируются <b>все записи</b> из ответа источника — размер пачки задаётся параметром <code>n=</code> в его URL.<br><br>
			После импорта видео сразу появятся на <a href="/" target="_blank" style="color: #ef233c;">Главной странице</a> и в соответствующих разделах категорий и тегов.
		</div>
	</div>
	<script>
		function copyCli() {
			var t = document.getElementById('cli-cmd').textContent;
			if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(t); }
			else {
				var ta = document.createElement('textarea');
				ta.value = t; document.body.appendChild(ta); ta.select();
				try { document.execCommand('copy'); } catch (e) {}
				document.body.removeChild(ta);
			}
		}
	</script>
</body>
</html>