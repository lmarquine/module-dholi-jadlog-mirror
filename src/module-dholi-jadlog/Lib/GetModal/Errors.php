<?php
/**
* 
* Frente com Jadlog para Magento 2
* 
* @category     Dholi
* @package      Modulo Frente com Jadlog
* @copyright    Copyright (c) 2019 dholi (https://www.dholi.dev)
* @version      1.0.0
* @license      https://www.dholi.dev/license/
*
*/
declare(strict_types=1);

namespace Dholi\Jadlog\Lib\GetModal;

class Errors {

	private static $errors = [
		'999' => 'Erro inesperado.',
		'001' => 'Falha na conexão com a Jadlog. Por favor, tente mais tarde.',
		'002' => 'País de origem/destino deve ser Brasil.',
		'003' => 'Código Postal da Loja está incorreto.',
		'004' => 'Dimensões não encontradas para o produto %s.'
	];

	public static function getMessage($code) {
		if (array_key_exists($code, self::$errors)) {
			return self::$errors[$code];
		} else {
			return self::$errors['999'];
		}
	}

}
