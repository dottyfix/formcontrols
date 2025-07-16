<?php
namespace Dottyfix\Tools\FormControls;

class Optgroup {
	
	protected $parent;
	protected $label;
	protected $disabled = false;
	protected $list = [];
	
	function __construct($parent, $label, $disabled = false) {
		$this->parent = $parent;
		$this->label = $label;
		$this->disabled = $disabled;
	}
	
	function addOption($label, $value = null, $disabled = false) {
		$this->list[] = new HTMLOption($this->parent, $label, $value, $disabled);
		return $this;
	}
	
	function output() {
		$attr = ['label' => $this->label];
		if($this->disabled)
			$attr[] = ' disabled';
		$out = HTMLFormControl::startTag('optgroup', $attr);
		
		foreach($this->list as $opt)
			$out .= $opt->output()."\n";
		
		return $out.'</optgoup>';
		
	}
}
