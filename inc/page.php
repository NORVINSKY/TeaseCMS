<?php
$URI = $match['params']['id']; $E = ''; //Ужасный костыль
if(file_exists(_CACHE_.'pages/'.$URI.'.php')){
	
  define('PGTYPE', 'page');
	include_once _CACHE_.'pages/'.$URI.'.php'; 
  
  if(isset($P_DELETED) && $P_DELETED){ header("Location: /"); exit; }
  
  //похожие странички
  if(!isset($P_RELATED)){
    if(file_exists(_CACHE_.'home.dat') && filesize(_CACHE_.'home.dat') > 2050){
      $links = array_map('cleanStr', file(_CACHE_.'home.dat'));
      $links = cleanArray($links); shuffle($links);
      $links = array_chunk($links, 20);
      if(count($links[0]) > 5){
        file_put_contents(_CACHE_.'pages/'.$URI.'.php', "\$P_RELATED = '".serialize($links[0])."';\r\n", FILE_APPEND|LOCK_EX);
        $P_RELATED = $links[0];
      }
    } 
  }else{ $P_RELATED = unserialize($P_RELATED); }

  if(_PSEUDO_ === 1): //формируем псевдо странички с кадрами
  
    $FRPAGES = (_FRPAGES_ < 5 && _FRPAGES_ > 0)? _FRPAGES_ : 2 ;
    $FRAMESC = (_FRAMES_ <= 8 && _FRAMES_ > 0)? _FRAMES_ : 5 ;

    $P_FRAMES = array_chunk(unserialize($P_FRAMES), $FRAMESC);

    $PSEUDO_PAGES = [];

    if(count($P_FRAMES) >= $FRPAGES){ //кол-во блоков с фреймами соответсвует кол-ву страниц
      $PSEUDO_PAGES = $P_FRAMES;
      if(count($P_FRAMES[$FRPAGES-1]) < 3){ //фреймов не хватает, выводим псевдо плеер
        $P_FRAMES[$FRPAGES-1] = 'PSEUDOPLAYER';
      }

    }else{//фреймов не хватает, выводим псевдо плеер
      $P_FRAMES[$FRPAGES-1] = 'PSEUDOPLAYER';
    }

    //формируем контент
    if(isset($_GET['step']) && is_numeric($_GET['step']) && $_GET['step'] <= $FRPAGES){ //остальные странички
      $N_CONTENT = preparePseudoPage($P_FRAMES[$_GET['step']-1], $_GET['step']);
    }elseif(isset($_GET['step']) && $_GET['step'] == 'video'){ //видео
      $N_CONTENT = $P_EMBED;
    }else{ //первая страничка и все не попадающие в условия
      $N_CONTENT = preparePseudoPage($P_FRAMES[0], 1);
    }
  
  else:
    $N_CONTENT = $P_EMBED;
  endif;

}else{ define('PGTYPE', '404'); page404(); }

