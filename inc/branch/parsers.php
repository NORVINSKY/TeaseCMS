<?php

class P_vid4tb{
	
	public static $varInf = '4Tube';
	
	public static function url($w){ return 'http://www.4tube.com/search?q='.urlencode(mb_strtolower($w)); }
	
	public static function parse($html){
		$contents = [];

		if(preg_match_all('~<a href="/videos/(.*)".*title="(.*)".*data-master="(.*)"~Us', $html, $m)){

		for ($i=0; $i<count($m[1]); $i++) {
	
			if(strpos($m[1][$i], '/') !== false){
				$contents[$i]['title'] = prepareTXT($m[2][$i]);
				$contents[$i]['thumb'] = $m[3][$i];
				$t = explode('/', $m[1][$i]);
				$contents[$i]['code'] = $t[0];
			}	
		}
	
		if(count($contents)>0){ return $contents; }

		} return;
	}
	
	public static function pack($code){
		return '<iframe src="http://www.4tube.com/embed/'.$code.'" frameborder="0" width="560" height="315" scrolling="no" allowfullscreen></iframe>';
	}
}


class P_vidFxcm{
	
	public static $varInf = 'Fux';
	
	public static function url($w){ return 'http://www.fux.com/search?q='.urlencode(mb_strtolower($w)); }
	
	public static function parse($html){
		$contents = [];

		if(preg_match_all('~<a href="/video/(.*)".*title="(.*)".*data-master="(.*)"~Us', $html, $m)){

		for ($i=0; $i<count($m[1]); $i++) {
	
			if(strpos($m[1][$i], '/') !== false){
				$contents[$i]['title'] = prepareTXT($m[2][$i]);
				$contents[$i]['thumb'] = $m[3][$i];
				$t = explode('/', $m[1][$i]);
				$contents[$i]['code'] = $t[0];
			}	
		}
	
		if(count($contents)>0){ return $contents; }

		} return;
	}
	
	public static function pack($code){
		return '<iframe src="http://www.fux.com/embed/'.$code.'" frameborder="0" width="560" height="315" scrolling="no" allowfullscreen></iframe>';
	}
}

class P_vidPrntb{
	
	public static $varInf = 'PornTube';
	
	public static function url($w){ return 'http://www.porntube.com/search?q='.urlencode(mb_strtolower($w)); }
	
	public static function parse($html){
		$contents = [];

		if(preg_match_all('~<a href="/videos/(.*)".*title="(.*)".*data-master="(.*)"~Us', $html, $m)){

		for ($i=0; $i<count($m[1]); $i++) {

			if(strpos($m[1][$i], '_') !== false){
				$contents[$i]['title'] = prepareTXT($m[2][$i]);
				$contents[$i]['thumb'] = $m[3][$i];
				$t = explode('_', $m[1][$i]);
				$contents[$i]['code'] = array_pop($t);
			}			
		}
		
		if(count($contents)>0){ return $contents; }

		} return;
	}
	
	public static function pack($code){
		return '<iframe src="http://www.porntube.com/embed/'.$code.'" frameborder="0" width="560" height="315" scrolling="no" allowfullscreen></iframe>';
	}
}


class P_vidRdtb{
	
	public static $varInf = 'RedTube';
	
	public static function url($w){ return 'http://www.redtube.com/?search='.urlencode(mb_strtolower($w)); }
	
	public static function parse($html){
		$contents = [];
		
		$html = resendV8($html, __CLASS__);

		$html = substr($html, strrpos($html, '<ul class="video-listing two-in-row">')+1);
		$html = substr($html, 0, strrpos($html, '<div class="pages">')); 
		if(preg_match_all('~<li.*<a href="/(.*)".*<img.*data-src="(.*)".*alt="(.*)"~Us', $html, $m)){

		for ($i=0; $i<count($m[1]); $i++) {

			$contents[$i]['title'] = prepareTXT($m[3][$i]);
			$contents[$i]['code'] = $m[1][$i];
			$contents[$i]['thumb'] = $m[2][$i];
		}
		
		if(count($contents)>0){ return $contents; }

		} return;
	}
	
	public static function pack($code){
		return '<iframe src="http://embed.redtube.com/?id='.$code.'&bgcolor=000000" frameborder="0" width="560" height="315" scrolling="no" allowfullscreen></iframe>';
	}
}


class P_vidPrnhb{
	
	public static $varInf = 'PornHub';
	
	public static function url($w){ return 'http://www.pornhub.com/video/search?search='.urlencode(mb_strtolower($w)); }
	
	public static function parse($html, $word=''){
		$contents = [];

		$html = resendV8($html, __CLASS__);

		if(preg_match_all('~<div class="phimage">.*<a href="(.*)" title="(.*)".*<img.*data-mediumthumb="(.*)"~uUs', $html, $m)){
		
		for ($i=0; $i<count($m[1]); $i++) {
			$tmp = explode('viewkey=', $m[1][$i]);

			$contents[$i]['title'] = prepareTXT($m[2][$i]);
			$contents[$i]['code'] = $tmp[1];
			$contents[$i]['thumb'] = $m[3][$i];
		}
		
		if(count($contents)>0){ return $contents; }

		} return;
	}
	
	public static function pack($code){
		return '<iframe src="http://www.pornhub.com/embed/'.$code.'" frameborder="0" width="560" height="340" scrolling="no" allowfullscreen></iframe>';
	}
}
