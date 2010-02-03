<?php

class PagePresenter extends BasePresenter {

	public function actionDefault() {
		
	}

	public function createComponentCreateForm() {
		$form = Page::createForm("modify");
		
		$form->onSubmit[] = array($this, "saveCreateForm");

		return $form;
	}

	public function saveCreateForm($form) {
		
	}

}
