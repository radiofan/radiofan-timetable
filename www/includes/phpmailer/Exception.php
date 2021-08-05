<?php
/**
 * PHPMailer Exception class.
 * PHP Version 5.5.
 * @see https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 */

namespace PHPMailer\PHPMailer;

class Exception extends \Exception{
	public function errorMessage(){
		return '<strong>'.htmlspecialchars($this->getMessage())."</strong><br>\n";
	}
}
