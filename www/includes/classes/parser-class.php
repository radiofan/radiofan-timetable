<?php
class rad_parser{
	private static $error_codes=array(
		0 => 'CURLE_OK',
		1 => 'CURLE_UNSUPPORTED_PROTOCOL',
		2 => 'CURLE_FAILED_INIT',
		3 => 'CURLE_URL_MALFORMAT',
		4 => 'CURLE_URL_MALFORMAT_USER',
		5 => 'CURLE_COULDNT_RESOLVE_PROXY',
		6 => 'CURLE_COULDNT_RESOLVE_HOST',
		7 => 'CURLE_COULDNT_CONNECT',
		8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
		9 => 'CURLE_REMOTE_ACCESS_DENIED',
		11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
		13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
		14 =>'CURLE_FTP_WEIRD_227_FORMAT',
		15 => 'CURLE_FTP_CANT_GET_HOST',
		17 => 'CURLE_FTP_COULDNT_SET_TYPE',
		18 => 'CURLE_PARTIAL_FILE',
		19 => 'CURLE_FTP_COULDNT_RETR_FILE',
		21 => 'CURLE_QUOTE_ERROR',
		22 => 'CURLE_HTTP_RETURNED_ERROR',
		23 => 'CURLE_WRITE_ERROR',
		25 => 'CURLE_UPLOAD_FAILED',
		26 => 'CURLE_READ_ERROR',
		27 => 'CURLE_OUT_OF_MEMORY',
		28 => 'CURLE_OPERATION_TIMEDOUT',
		30 => 'CURLE_FTP_PORT_FAILED',
		31 => 'CURLE_FTP_COULDNT_USE_REST',
		33 => 'CURLE_RANGE_ERROR',
		34 => 'CURLE_HTTP_POST_ERROR',
		35 => 'CURLE_SSL_CONNECT_ERROR',
		36 => 'CURLE_BAD_DOWNLOAD_RESUME',
		37 => 'CURLE_FILE_COULDNT_READ_FILE',
		38 => 'CURLE_LDAP_CANNOT_BIND',
		39 => 'CURLE_LDAP_SEARCH_FAILED',
		41 => 'CURLE_FUNCTION_NOT_FOUND',
		42 => 'CURLE_ABORTED_BY_CALLBACK',
		43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
		45 => 'CURLE_INTERFACE_FAILED',
		47 => 'CURLE_TOO_MANY_REDIRECTS',
		48 => 'CURLE_UNKNOWN_TELNET_OPTION',
		49 => 'CURLE_TELNET_OPTION_SYNTAX',
		51 => 'CURLE_PEER_FAILED_VERIFICATION',
		52 => 'CURLE_GOT_NOTHING',
		53 => 'CURLE_SSL_ENGINE_NOTFOUND',
		54 => 'CURLE_SSL_ENGINE_SETFAILED',
		55 => 'CURLE_SEND_ERROR',
		56 => 'CURLE_RECV_ERROR',
		58 => 'CURLE_SSL_CERTPROBLEM',
		59 => 'CURLE_SSL_CIPHER',
		60 => 'CURLE_SSL_CACERT',
		61 => 'CURLE_BAD_CONTENT_ENCODING',
		62 => 'CURLE_LDAP_INVALID_URL',
		63 => 'CURLE_FILESIZE_EXCEEDED',
		64 => 'CURLE_USE_SSL_FAILED',
		65 => 'CURLE_SEND_FAIL_REWIND',
		66 => 'CURLE_SSL_ENGINE_INITFAILED',
		67 => 'CURLE_LOGIN_DENIED',
		68 => 'CURLE_TFTP_NOTFOUND',
		69 => 'CURLE_TFTP_PERM',
		70 => 'CURLE_REMOTE_DISK_FULL',
		71 => 'CURLE_TFTP_ILLEGAL',
		72 => 'CURLE_TFTP_UNKNOWNID',
		73 => 'CURLE_REMOTE_FILE_EXISTS',
		74 => 'CURLE_TFTP_NOSUCHUSER',
		75 => 'CURLE_CONV_FAILED',
		76 => 'CURLE_CONV_REQD',
		77 => 'CURLE_SSL_CACERT_BADFILE',
		78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
		79 => 'CURLE_SSH',
		80 => 'CURLE_SSL_SHUTDOWN_FAILED',
		81 => 'CURLE_AGAIN',
		82 => 'CURLE_SSL_CRL_BADFILE',
		83 => 'CURLE_SSL_ISSUER_ERROR',
		84 => 'CURLE_FTP_PRET_FAILED',
		85 => 'CURLE_RTSP_CSEQ_ERROR',
		86 => 'CURLE_RTSP_SESSION_ERROR',
		87 => 'CURLE_FTP_BAD_FILE_LIST',
		88 => 'CURLE_CHUNK_FAILED'
	);
	
	public static function get_rand_user_agent($path){
		//Максимальная разумная длина одной строки
		$max_str_len = 256;
		//Берем немного с запасом
		$triple_buffer = $max_str_len * 3;
		$file_size = filesize($path);
		//На всякий случай проверим длину файла
		if(empty($file_size) || $file_size < $triple_buffer){
			trigger_error('Файл user_agents слишком мал.', E_USER_WARNING);
			return '';
		}
		$rnd = rand(0, $file_size - $triple_buffer);
		$handle = fopen($path, "r");
		//Устанавливаем указатель в случайном месте файла
		fseek($handle, $rnd);
		$temp = fread($handle, $triple_buffer);
		fclose($handle);
		//Разбиваем текст на строки
		//$str = explode($delimiter, $temp);
		$str = preg_split('/(\\n)|(\\r\\n?)/', $temp);
		array_shift($str);
		array_pop($str);
		return $str[array_rand($str)];
	}
	
	/**
	 * @param string[]|array $pages - массив строк url или [['url' => string, 'options' => array],...]
	 * @param null|callable $callback - функция обрабатывающая ответ curl'а, function($content, $info, $status, $status_text)
	 * @param array $callback_data - дополнительные переменные передаваемые callback'у
	 * @return array - если callback указан, то массив результатов обработки callback'ом; иначе ответы curl'а
	 */
	public static function get_pages_content($pages, $callback=null, $callback_data=array()){
		//собираем каналы запросов
		if(!is_array($pages))
			$pages = array($pages);
		$len = sizeof($pages);
		$chanels = array();
		$mh = curl_multi_init();
		for($i=0; $i<$len; $i++){
			if(!is_array($pages[$i]))
				$pages[$i] = array('url' => (string) $pages[$i]);
			if(empty($pages[$i]['url'])){
				trigger_error('url не найден, элемент #'.$i, E_USER_WARNING);
				continue;
			}
			$option = array(
				CURLOPT_URL => $pages[$i]['url'],
				CURLOPT_RETURNTRANSFER => 1,//кидать код страницы в переменную
				CURLOPT_NOBODY => 0,//не загружать тело страницы
				CURLOPT_HEADER => 0,//заголовки курла
				CURLINFO_HEADER_OUT => 1,
				CURLOPT_CONNECTTIMEOUT => !empty($pages[$i]['options']['connecttimeout']) ? absint($pages[$i]['options']['connecttimeout']) : 30, // таймаут соединения
				CURLOPT_TIMEOUT => !empty($pages[$i]['options']['timeout']) ? absint($pages[$i]['options']['timeout']) : 30,// таймаут ответа
				CURLOPT_FAILONERROR => 0,
				CURLOPT_SSL_VERIFYPEER => 0 //Не проверяем ССЛ
				//CURLOPT_COOKIEFILE => "cookie.txt", // Сюда будем записывать cookies, файл в той же папке, что и сам скрипт
				//CURLOPT_COOKIEJAR => "cookie.txt"
			);
			if(isset($pages[$i]['options']['useragent'])){
				$option[CURLOPT_USERAGENT] = $pages[$i]['options']['useragent'];
			}
			if(isset($pages[$i]['options']['referer'])){
				$option[CURLOPT_REFERER] = $pages[$i]['options']['referer'];
			}
			if(isset($pages[$i]['options']['headers'])){
				$option[CURLOPT_HTTPHEADER] = $pages[$i]['options']['headers'];
			}
			if(!empty($pages[$i]['options']['post_data']) && is_array($pages[$i]['options']['post_data'])){
				$option[CURLOPT_POST] = 1;//Отправка пост запроса
				$option[CURLOPT_POSTFIELDS] = $pages[$i]['options']['post_data'];//данные для пост запроса
			}
			$chanels[] = array('option' => $option, 'ch' => curl_init());
			$ind = sizeof($chanels)-1;
			//print_r($option);
			curl_setopt_array($chanels[$ind]['ch'], $option);
			curl_multi_add_handle($mh, $chanels[$ind]['ch']);
		}
		
		unset($option, $pages);
		
		//выполняем запросы
		$len = sizeof($chanels);
		$output = array();
		$run = null;
		do{
			$tmp = curl_multi_exec($mh, $run);
				// получаю информацию о текущих соединениях
			while($info = curl_multi_info_read($mh)){
				if(is_array($info) && ($ch = $info['handle'])){
					$ind = -1;
					for($i=0; $i<$len; $i++){
						if($ch == $chanels[$i]['ch']){
							$ind = $i;
							break;
						}
					}
					// получаю содержимое загруженной страницы
					$content = curl_multi_getcontent($ch);
					$ch_info = curl_getinfo($ch);
					$ch_info['options'] = $chanels[$ind]['option'];
					if(!is_null($callback)){
						// вызов callback-обработчика
						$output[] = call_user_func($callback, $content, $ch_info, $info['result'], curl_error($ch), ...$callback_data);
					}else{
						// добавление в хеш результатов
						$output[] = array('content' => $content, 'info' => $ch_info, 'status' => $info['result'], 'status_text' => curl_error($ch));
					}
				}
			}
		}while($run > 0);
		
		$len = sizeof($chanels);
		for($i=0; $i<$len; $i++){
			curl_multi_remove_handle($mh, $chanels[$i]);
			curl_close($chanels[$i]);
		}
		curl_multi_close($mh);
		
		return $output;
	}
	
	public static function get_page_content($page, $options=array()){
		$option = array(
			CURLOPT_URL => (string)$page,
			CURLOPT_RETURNTRANSFER => 1,//кидать код страницы в переменную
			CURLOPT_NOBODY => 0,//не загружать тело страницы
			CURLOPT_HEADER => 0,//заголовки курла
			CURLOPT_CONNECTTIMEOUT => !empty($options['connecttimeout']) ? abs((int)$options['connecttimeout']) : 60, // таймаут соединения
			CURLOPT_TIMEOUT => !empty($options['timeout']) ? abs((int)$options['timeout']) : 60,// таймаут ответа
			CURLOPT_FAILONERROR => 0,
			CURLOPT_SSL_VERIFYPEER => 0, //Не проверяем ССЛ
			CURLOPT_SSL_VERIFYHOST => 0 //Не проверяем ССЛ
			//CURLOPT_ENCODING => 'gzip,deflate'
		);
		if(isset($options['referer'])){
			$option[CURLOPT_REFERER] = $options['referer'];
		}
		if(isset($options['useragent'])){
			$option[CURLOPT_USERAGENT] = $options['useragent'];
		}
		if(isset($options['headers'])){
			$option[CURLOPT_HTTPHEADER] = $options['headers'];
		}
		if(!empty($options['post_data']) && is_array($options['post_data'])){
			$option[CURLOPT_POST] = 1;//Отправка пост запроса
			$option[CURLOPT_POSTFIELDS] = $options['post_data'];//данные для пост запроса
		}
		$ch = curl_init();
		//print_r($option);
		curl_setopt_array($ch, $option);
		
		$output = array('content' => curl_exec($ch), 'info' => curl_getinfo($ch), 'status' => curl_errno($ch), 'status_text' => curl_error($ch));
		
		curl_close($ch);
		
		return $output;
	}
}
?>