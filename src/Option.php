<?php
namespace Dottyfix\FormControls;

class Option {
	
	protected $parent;
	protected $label;
	protected $disabled = false;
	protected $value = null;
	
	function __construct($parent, $label, $value = null, $disabled = false) {
		$this->parent = $parent;
		$this->label = $label;
		$this->disabled = $disabled;
		$this->value = ($value == null) ? $label : $value;
	}
	
	function output() {
		$v = $this->parent->get();
		$attr = [];
		if($this->value == $v)
			$attr[] = ' selected';
		if($this->disabled)
			$attr[] = ' disabled';
		if($this->value != $this->label)
			$attr['value'] = $this->value;
		return HTMLFormControl::startTag('option', $attr).$this->label.'</option>';
	}
	

	
}
