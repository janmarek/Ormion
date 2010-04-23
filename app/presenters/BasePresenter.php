<?php

use Nette\Application\Presenter;

/**
 * Base presenter
 */
abstract class BasePresenter extends Presenter {

	protected function beforeRender() {
		$this->template->pages = Page::findAll()->orderBy("created");
	}

}