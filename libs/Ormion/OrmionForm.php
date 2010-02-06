<?php

/**
 * Ormion form
 *
 * @author Jan Marek
 * @license MIT
 */
class OrmionForm extends Object {

	/** @var string */
	public static $formClass = "AppForm";
	

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct() {
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}


	/**
	 * Form factory
	 * @param array $config
	 * @return AppForm
	 */
	public static function create($config) {
		// TODO generovat základní validaci, alespoň required

		$form = new self::$formClass;
		
		foreach ($config as $key => $item) {
			$args = array($key);

			if (isset($item["label"])) {
				$args[] = $item["label"];
			}

			if (isset($item["args"])) {
				$args = array_merge($args, $item["args"]);
			}

			call_user_func_array(array($form, "add" . $item["type"]), $args);
		}

		return $form;
	}

}