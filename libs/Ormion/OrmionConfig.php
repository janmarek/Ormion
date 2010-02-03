<?php

/**
 * OrmionConfig
 *
 * @author Jan Marek
 */
class OrmionConfig extends Config {

	public static function fromFile($file, $section = NULL, $flags = self::READONLY) {
		return new self(ConfigAdapterIni::load($file, $section), $flags);
	}

}