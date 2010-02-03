<?php

/**
 * Ormion form
 *
 * @author Jan Marek
 */
class OrmionForm extends Object {

	public static $formClass = "AppForm";

	public static function create(Config $cfg) {
		$form = new AppForm;

		$form = new self::$formClass;
		
		foreach ($cfg as $key => $item) {
			$args = array($key);

			if (isset($item->label)) {
				$args[] = $item->label;
			}

			if (isset($item->args)) {
				$args = array_merge($args, (array) $item->args);
			}

			call_user_func_array(array($form, "add" . $item->type), $args);
		}

		return $form;
	}

}