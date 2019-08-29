<?php
namespace AKEB\Logger\Routes;

/**
 * Class FileRoute
 */
class FileRoute extends \AKEB\Logger\Route {
	/**
	 * @var string Путь к файлу
	 */
	public $filePath;
	/**
	 * @var string Шаблон сообщения
	 */
	public $template = "{date} {level} {message} {context}";

	/**
	 * @inheritdoc
	 */
	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		$dirName = dirname($this->filePath);
		$have_file = @file_exists($this->filePath);
		if (!$have_file) {
			@mkdir($dirName,0775,true);
			touch($this->filePath);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function log($level, $message, array $context = []) {
		file_put_contents($this->filePath, trim(strtr($this->template, [
			'{date}' => $this->getDate(),
			'{level}' => $level,
			'{message}' => $message,
			'{context}' => implode(' || ', $context),
		])) . PHP_EOL, FILE_APPEND);
	}
}
