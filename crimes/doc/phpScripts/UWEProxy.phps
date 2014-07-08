<?php

	class UWEProxy{
		
		public static function getFileContent($uri) {
		 $context = stream_context_create(
		 array('http'=>
			 array('proxy'=>'proxysg.uwe.ac.uk:8080',
					  'header'=>'Cache-Control: no-cache'
				   )
		  ));  
		 $contents = file_get_contents($uri, false, $context);
		 return $contents;
		}
		
		public static function putContentToUWE($uri, $data) {
		//get a file via UWE proxy and stop caching
		 $context = stream_context_create(
		 array('http'=>
			 array('proxy'=>'proxysg.uwe.ac.uk:8080',
					  'header'=>'Cache-Control: no-cache'
				   )
		  ));  
		 $contents = file_put_contents($uri,$data);
		 return $contents;
		}		
		
		
	}

?>