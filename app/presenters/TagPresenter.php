<?php

use Nette\Application\AppForm;
use Nette\String;

/**
 * TagPresenter
 *
 * @author Jan Marek
 */
class TagPresenter extends BasePresenter {

	public function renderDefault() {
		$this->template->title = "Edit tags";
		$this->template->tags = Tag::findAll()->orderBy("name");
	}


	public function renderDetail($id) {
		$tag = Tag::findByUrl($id);
		$this->template->title = $tag->name;
		$this->template->tag = $tag;
	}


	public function handleDelete($id) {
		Tag::create($id)->delete();
		$this->flashMessage("Tag was deleted!");
		$this->redirect("default");
	}

	
	protected function createComponentAddTagForm() {
		$form = new AppForm;
		$form->addText("name", "Name", 40, 50);
		$form->addSubmit("s", "Add");

		$presenter = $this;

		$form->onSubmit[] = function ($form) use ($presenter) {
			$name = $form->values["name"];

			Tag::create(array(
				"name" => $name,
				"url" => String::webalize($name),
			))->save();

			$presenter->flashMessage("Tag was added!");
			$presenter->redirect("default");
		};

		return $form;
	}

}