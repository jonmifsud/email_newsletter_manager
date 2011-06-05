<?php

if(!defined('ENDIR')) define('ENDIR', EXTENSIONS . "/email_newsletters");
if(!defined('ENVIEWS')) define('ENVIEWS', ENDIR . "/content/templates");

if(!class_exists('ExtensionPage')){
	require_once(ENDIR . '/lib/class.extensionpage.php');
}

require_once(TOOLKIT . '/class.xsltprocess.php');
require_once(ENDIR . '/lib/class.recipientgroupmanager.php');

Class contentExtensionemail_newslettersrecipientgroups extends ExtensionPage{

	function __construct(){
		$this->_XSLTProc = new XsltProcess();
		$this->_XML = new XMLElement("data");
		$this->viewDir = ENVIEWS . '/recipientgroups';
		parent::__construct(Symphony::Engine());
	}

	function __viewIndex(){
		$this->setPageType('index');
		$this->setTitle(__("Symphony - Email Recipients"));
		$recipientgroupManager = new RecipientgroupManager($this);
		$groups = $recipientgroupManager->listAll();
		$senders = new XMLElement('recipientgroups');
		foreach($groups as $group){
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $group);
			$count = new XMLElement('count', $recipientgroupManager->create($group['handle'])->getCount());
			$entry->appendChild($count);
			$senders->appendChild($entry);
		}
		$this->_XML->appendChild($senders);
	}

	function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			foreach((array)$_POST['items'] as $item=>$status){
				Symphony::Database()->query('DELETE FROM `tbl_email_newsletters_recipientgroups` where `id` = "' . Symphony::Database()->cleanValue($item) . '" LIMIT 1');
			}
		}
	}

	function __viewEdit($new = false){
		$this->setPageType('form');
		$this->addScriptToHead(URL . '/extensions/email_newsletters/assets/admin.js', 140);
		$this->addStylesheetToHead(URL . '/extensions/email_newsletters/assets/admin.css', 'screen', 103);
		
		$errors = new XMLElement('errors');
		
		$section_xml = new XMLElement('sections');
		$sectionManager = new SectionManager($this);
		$sections = $sectionManager->fetch();
		foreach($sections as $section){
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $section->get());
			foreach($section->fetchFields() as $field){
				$field_xml = new XMLElement('field');
				General::array_to_xml($field_xml,$field->get()); 
				
				$filter_html = new XMLElement('filter_html');
				$field->displayDatasourceFilterPanel($filter_html, null, $errors, $section->get('id'));
				$field_xml->appendChild($filter_html);
				
				$field_elements = new XMLElement('elements');
				General::array_to_xml($field_elements, $field->fetchIncludableElements());
				$field_xml->appendChild($field_elements);
				$entry->appendChild($field_xml);
			}			
			$section_xml->appendChild($entry);
		}
		$this->_XML->appendChild($section_xml);
		
		$recipientgroup = new XMLElement('recipientgroups');

		if($this->_context[2] == 'saved' || $this->_context[3] == 'saved'){
			$this->pageAlert(
				__(
					__('Email Recipient updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Recipient Groups</a>'),
					array(
						DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
						SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/new/',
						SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/',
					)
				),
				Alert::SUCCESS
			);
		}

		if($new == false){
			$groupManager = new RecipientgroupManager($this);
			$group = $groupManager->create($this->_context[1]);
			if(is_object($group)){
				$entry = new XMLElement('entry');
				$properties = $group->getProperties();
				General::array_to_xml($entry, $group->about());
				
				$source = new XMLElement('source', $properties['section']);
				$entry->appendChild($source);
				
				$fields = new XMLElement('fields');
				
				$email = new XMLElement('email', $properties['email']);
				$fields->appendChild($email);
				
				$name = new XMLElement('name');
				$properties['name']['xslt'] = '<![CDATA[' . $properties['name']['xslt'] . ']]>';
				General::array_to_xml($name, $properties['name']);
				$fields->appendChild($name);
				
				$required_param = new XMLElement('required_param', $properties['required_param']);
				$fields->appendChild($required_param);
				
				$entry->appendChild($fields);
				
				if(!empty($properties['filters'])){
					$filters = new XMLElement('filters');
					$fieldManager = new FieldManager($this);
					foreach($properties['filters'] as $filter=>$val){
						$filter_obj = $fieldManager->fetch($filter);
						if(is_object($filter_obj)){
							$filter_entry = new XMLElement('entry', null, array('id'=>$filter, 'data-type'=>$fieldManager->fetch($filter)->handle()));

							$fieldManager->fetch($filter)->displayDatasourceFilterPanel($filter_entry, $val, $errors, $properties['section']);
							$filters->appendChild($filter_entry);
						}
					}
					$entry->appendChild($filters);
				}						

				$recipientgroup->appendChild($entry);
				$this->_XML->appendChild($recipientgroup);
			}
			else{
				Administration::instance()->errorPageNotFound();
			}
		}
	}
	
	function __actionEdit($new = false){
		$fields = $_POST['fields'];

		$errors = new XMLElement('errors');
		if(isset($_POST['action']['delete'])){
			if(true){
			}
			else{
				$this->pageAlert(
					__('Could not delete: ' . Symphony::Database()->getLastError()),
					Alert::ERROR
				);
				return true;
			}
		}
		if(!empty($fields['name']) && General::validateXML($fields['name-xslt'], $errors = new XMLElement('errors'), false)){
			try{
				if(RecipientGroupManager::save($this->_context[1], $fields, $new)){
					redirect(SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/edit/' . Lang::createHandle($fields['name'], 225, '_') . '/saved');
					return true;
				}
			}
			catch(Exception $e){
				$this->pageAlert(__('Could not save: ' . $e->getMessage()),Alert::ERROR);
			}
		}
		if(empty($fields['name'])){
			$errors->appendChild(new XMLElement('name', __('This field can not be empty.')));
		}
		$this->_XML->appendChild($errors);
	}
	
	function __actionNew(){
		$this->__actionEdit(true);
	}
	
	function __viewNew(){
		$this->_context[1] = 'New';
		$this->_useTemplate = 'viewEdit';
		$this->__viewEdit(true);
	}
}