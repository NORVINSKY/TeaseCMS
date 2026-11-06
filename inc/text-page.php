<?php

/*
	Контроллер текстовых (юридических) страниц.
	URL: /text/{slug}  →  data/text/{slug}.html
	Слаги: privacy, terms, dmca, contact

	POST на /text/contact — обработчик формы контактов («обманка»):
	сообщение никуда не отправляется, вместо этого пользователю показывается
	страница благодарности. Работает и без JavaScript.
*/

$TP_RAW = isset($match['params']['id']) ? (string)$match['params']['id'] : '';
$TP_SLUG = preg_replace('~[^a-z0-9\-]~', '', strtolower(trim($TP_RAW)));
$TP_FILE = _DATA_.'text/'.$TP_SLUG.'.html';

$TP_SENT  = false; // флаг «форма отправлена — показываем благодарность»
$TP_ERROR = '';    // текст ошибки валидации
$TP_NAME  = '';    // имя из формы (для благодарности и для возврата в поля)

if($TP_SLUG !== '' && file_exists($TP_FILE)){

	define('PGTYPE', 'text');

	$TP_CONTENT = file_get_contents($TP_FILE, LOCK_EX);
	$TP_UPDATED = filemtime($TP_FILE);

	//Заголовки и описания страниц
	$TP_MAP = [
		'privacy' => ['Privacy Policy', 'How we collect, use, and protect information on this site.'],
		'terms'   => ['Terms of Service', 'The rules that apply to the use of this site.'],
		'dmca'    => ['DMCA Policy', 'How to submit a copyright takedown notice for content on this site.'],
		'contact' => ['Contact Us', 'How to reach the site administration.'],
	];

	if(isset($TP_MAP[$TP_SLUG])){
		$TP_TITLE = $TP_MAP[$TP_SLUG][0];
		$TP_DESC  = $TP_MAP[$TP_SLUG][1];
	}else{
		$TP_TITLE = mb_ucwords(str_replace('-', ' ', $TP_SLUG));
		$TP_DESC  = '';
	}

	//Обработка формы контактов
	$TP_IS_POST = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $TP_SLUG === 'contact');

	$TP_NAME = '';
	$TP_MAIL = '';
	$TP_SUBJ = '';
	$TP_TEXT = '';
	$TP_TRAP = '';
	$TP_CONSENT = '';

	if($TP_IS_POST){
		$TP_NAME    = trim(isset($_POST['name'])    ? (string)$_POST['name']    : '');
		$TP_MAIL    = trim(isset($_POST['email'])   ? (string)$_POST['email']   : '');
		$TP_SUBJ    = trim(isset($_POST['subject']) ? (string)$_POST['subject'] : '');
		$TP_TEXT    = trim(isset($_POST['message']) ? (string)$_POST['message'] : '');
		$TP_TRAP    = trim(isset($_POST['website']) ? (string)$_POST['website'] : ''); // honeypot
		$TP_CONSENT = empty($_POST['privacy_consent'])? '' : 'checked';
	}

	//Подставляем значения в форму (на GET — пустые, на POST — введённые)
	$TP_CONTENT = str_replace('{{NAME}}',    htmlspecialchars($TP_NAME, ENT_QUOTES, 'UTF-8'), $TP_CONTENT);
	$TP_CONTENT = str_replace('{{EMAIL}}',   htmlspecialchars($TP_MAIL, ENT_QUOTES, 'UTF-8'), $TP_CONTENT);
	$TP_CONTENT = str_replace('{{MESSAGE}}', htmlspecialchars($TP_TEXT, ENT_QUOTES, 'UTF-8'), $TP_CONTENT);
	$TP_CONTENT = str_replace('{{CONSENT}}', $TP_CONSENT, $TP_CONTENT);

	if($TP_SUBJ !== ''){
		$TP_CONTENT = preg_replace('~(<option\s+value="'.preg_quote($TP_SUBJ, '~').'")~', '$1 selected', $TP_CONTENT, 1);
	}

	if($TP_IS_POST){

		if($TP_TRAP !== ''){
			// honeypot заполнен — это бот: показываем благодарность и не задаём вопросов
			$TP_SENT = true;

		}elseif($TP_NAME === '' || $TP_MAIL === '' || $TP_TEXT === '' || !filter_var($TP_MAIL, FILTER_VALIDATE_EMAIL)){
			$TP_ERROR = 'Please fill in all the required fields and check the email address.';

		}elseif($TP_CONSENT === ''){
			$TP_ERROR = 'Please confirm that you accept the Privacy Policy.';

		}else{
			//Письмо намеренно не отправляется — «обманка»
			$TP_SENT = true;
		}
	}

}else{
	define('PGTYPE', '404'); page404();
}
