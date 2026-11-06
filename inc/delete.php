<?php
set_time_limit(0);

$PAGES = array_map('cleanStr', file(_CACHE_.'home.dat'));

$DEL_PAGES = []; $P_cnt = count($PAGES); $cnt = 0; $cnt_d = 0;
foreach($PAGES as $PG){
  if(file_exists(_CACHE_.'pages/'.$PG.'.php')){
    include_once _CACHE_.'pages/'.$PG.'.php';
    
    preg_match("~embed/(.*?)'~", $P_EMBED, $mtch);
    if(isset($mtch[1]) && !empty($mtch[1])){
      $res = getCurl('https://www.pornhub.com/webmasters/is_video_active?id='.$mtch[1]);
      if(!empty($res) && strpos($res, '{"') !== false){
        $res = json_decode($res);
        if(isset($res->active->is_active)){
          if($res->active->is_active == '0'){
            file_put_contents(_CACHE_.'pages/'.$PG.'.php', "<?php\r\n\$P_DELETED = true;");
            $cnt_d++;
          }
        }
      }
    }
  } $cnt++; echo "$cnt / $P_cnt\r\n";
}

echo "\r\nDONE \r\n";
if($cnt_d > 0){ echo "Deleted \033[1;34m {$cnt_d} \033[0m files.\r\n"; }
