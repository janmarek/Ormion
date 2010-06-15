<?php

namespace Ormion;

/**
 * Interface Record
 *
 * @author Jan Marek
 * @license MIT
 */
interface IRecord
{
	const STATE_NEW = 1;
	const STATE_EXISTING = 2;
	const STATE_DELETED = 3;
}