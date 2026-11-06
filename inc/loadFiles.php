<?php

	$TAGLIST = array_map('cleanStr', file(_DATA_.'TAGLIST.txt'));
	
	$RATETXT_SRC = file(_DATA_.'MINI-DESC-SPIN.txt');	
  $DESC_SRC = file(_DATA_.'DESC-SPIN.txt');
	$MAIN_CONTENT = file(_DATA_.'SPINTAX/'._SPIN_);
	