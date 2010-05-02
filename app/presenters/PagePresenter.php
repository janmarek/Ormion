<?php

use Nette\Application\AppForm;
use Nette\Web\Html;

class PagePresenter extends BasePresenter {

	public function renderDefault($id) {
		$page = Page::find($id);
		$page->visits++;
		$page->save();

		$this->template->title = $page->name;
		$this->template->page = $page;
	}


	public function actionDelete($id) {
		Page::create($id)->delete();
		$this->flashMessage("Page was deleted!");
		$this->redirect("Homepage:");
	}

	
	public function renderAdd() {
		$this->template->title = "Add page";
	}


	public function renderEdit($id) {
		$this->template->title = "Edit page";
	}


	protected function createPageFormBase($name, $new) {
		$form = new AppForm($this, $name);

		if (!$new) $form->addHidden("id");
		$form->addText("name", "Name", 40, 50);
		$form->addTextArea("description", "Description", 40, 3);
		$form->addTextArea("text", "Text", 40, 15);
		$form->addMultiSelect("tags", "Tags", Tag::findAll()->fetchPairs("id", "name"))
			->setOption("description", Html::el("a")->href($this->link("Tag:"))->setText("Edit tags"));
		$form->addCheckbox("allowed", "Allowed");

		$form->addSubmit("s", "Save");

		return $form;
	}


	protected function createComponentPageAddForm($name) {
		$form = $this->createPageFormBase($name, true);

		$presenter = $this;

		$form->onSubmit[] = function ($form) use ($presenter) {
			$values = $form->values;

			$page = Page::create($values);
			$page->Tags = array_map(function ($id) {
				return Tag::create($id);
			}, $values["tags"]);

			try {
				$page->save();

				$presenter->flashMessage("Page '$page->name' was added!");
				$presenter->redirect("default", array("id" => $page->id));
				
			} catch (\ModelException $e) {
				$page->addErrorsToForm($form);
			}
		};
	}


	protected function createComponentPageEditForm($name) {
		$form = $this->createPageFormBase($name, false);

		if (!$form->isSubmitted()) {
			$id = $this->getParam("id");
			$page = Page::find($id);
			$values = $page->getValues();
			$values["tags"] = $page->Tags->fetchColumn("id");
			$form->setDefaults($values);
		}

		$presenter = $this;

		$form->onSubmit[] = function ($form) use ($presenter) {
			$values = $form->values;

			$page = Page::create($values);
			$page->Tags = array_map(function ($id) {
				return Tag::create($id);
			}, $values["tags"]);
			$page->save();

			$presenter->flashMessage("Page '$page->name' was changed!");
			$presenter->redirect("default", array("id" => $page->id));
		};
	}


	protected function createComponentAddCommentForm() {
		$form = new AppForm;

		$form->addTextArea("text", "Text", 40, 10);
		$form->addText("name", "Author", 40);
		$form->addText("mail", "E-mail", 40);

		$form->addSubmit("s", "Send comment");

		$presenter = $this;

		$form->onSubmit[] = function ($form) use ($presenter) {
			$values = $form->values;
			$values["page"] = $presenter->getParam("id");

			Comment::create($values)->save();

			$presenter->flashMessage("Comment added!");
			$presenter->redirect("this");
		};

		return $form;
	}

}
