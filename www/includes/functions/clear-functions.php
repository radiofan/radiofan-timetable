<?php
/**
 * удаляем все символы кроме [a-zа-я0-9_@#&-$]
 * @param string $text
 * @return string
 */
function coupon_clear($text){
	return preg_replace('/[^a-zа-я0-9_@#&\\-\\$]/iu', '', $text);
}
/**
 * удаляем все символы кроме [a-z0-9_-]
 * @param string $text
 * @return string
 */
function login_clear($text){
	return preg_replace('/[^a-z0-9_\\-]/iu', '', $text);
}
/**
 * удаляем все символы кроме [a-f0-9], переводит в верхний регистр
 * @param string $text
 * @return string
 */
function hex_clear($text){
	return mb_strtoupper(preg_replace('/[^a-f0-9]/iu', '', $text));
}
/**
 * удаляем все символы кроме [a-z0-9!@%&$?*]
 * @param string $text
 * @return string
 */
function password_clear($text){
	return preg_replace('/[^a-z0-9!@%&\\$\\?\\*]/iu', '', $text);
}

/**
 * то же самое что и login_clear но переводит в нижний регистр
 * @param string $text
 * @return string
 * @see login_clear()
 */
function option_name_clear($text){
	return mb_strtolower(login_clear($text));
}

/**
 * удаляет все символы кроме цифр
 * @param $text
 */
function int_clear($text){
	return preg_replace('/[^0-9]/iu', '', $text);
}

/**
 * сепаратор - любое кол-во, символы [ :/_,.+-];
 * кавычки " или '
 * @param string $text
 * 'секунды SECOND'
 * 'минуты MINUTE'
 * 'часы HOUR'
 * 'дни DAY'
 * 'месяцы MONTH'
 * 'года YEAR'
 * '"минуты:секунды" MINUTE_SECOND'
 * '"часы:минуты" HOUR_MINUTE'
 * '"дни:часы" DAY_HOUR'
 * '"года:месяцы" YEAR_MONTH'
 * '"часы:минуты:секунды" HOUR_SECOND'
 * '"дни:часы:минуты" DAY_MINUTE'
 * '"дни:часы:минуты:секунды" DAY_SECOND'
 * @return string
 */
function sql_time_interval_clear($text){
	$text = trim($text);
	$matches = array();
	preg_match('#( SECOND$)|( MINUTE$)|( HOUR$)|( DAY$)|( MONTH$)|( YEAR$)|( MINUTE_SECOND$)|( HOUR_MINUTE$)|( DAY_HOUR$)|( YEAR_MONTH$)|( HOUR_SECOND$)|( DAY_MINUTE$)|( DAY_SECOND$)#iu', $text, $matches);
	if(!isset($matches[0]))
		return '';
	$type = mb_strtoupper(mb_substr($matches[0], 1));
	$numb = array();
	switch($type){
		case 'SECOND':
		case 'MINUTE':
		case 'HOUR':
		case 'SECDAYOND':
		case 'MONTH':
		case 'YEAR':
			preg_match('#([0-9]+) '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1]))
				return '';
			$numb = (int) $matches[1];
			if($numb <= 0)
				return '';
			return $numb.' '.$type;
		case 'MINUTE_SECOND':
		case 'HOUR_MINUTE':
		case 'DAY_HOUR':
		case 'YEAR_MONTH':
			preg_match('#[\'"]([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[\'"] '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1], $matches[2]))
				return '';
			$numb = array((int) $matches[1], (int) $matches[2]);
			break;
		case 'HOUR_SECOND':
		case 'DAY_MINUTE':
			preg_match('#[\'"]([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[\'"] '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1], $matches[2], $matches[3]))
				return '';
			$numb = array((int) $matches[1], (int) $matches[2], (int) $matches[3]);
			break;
		case 'DAY_SECOND':
			preg_match('#[\'"]([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[ :/_,\\.\\+\\-]+([0-9]+)[\'"] '.$type.'$#iu', $text, $matches);
			if(!isset($matches[1], $matches[2], $matches[3], $matches[4]))
				return '';
			$numb = array((int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[4]);
			break;
	}
	if(array_sum($numb) <= 0)
		return '';
	return '"'.implode(':', $numb).'" '.$type;
}

?>