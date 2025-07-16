<?php
namespace Dottyfix\Tools\FormControls;

class FormControl {
	
	static $inputTypes = ['button', 'checkbox', 'file', 'hidden', 'image', 'radio', 'reset', 'submit', 'text', 'color', 'date', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week'];
	static $wrappedByLabelTypes = ['checkbox', 'radio'];
	
	static $booleanTrueValue = '1';
	static $booleanFalseValue = '0';
	
	static $specialsTypes = [
		'button' => [
			'tagName' => 'button',	/* Attention input[type=button] exists and button[type=submit] exists */
			'attributes' => []
		],
		'boolean' => [	/* TODO : autodetect = quand name ne se termine pas par '[]' */
			'tagName' => 'input',
			'attributes' => ['type' => 'checkbox'],
			'sanitize' => FILTER_VALIDATE_BOOLEAN,	// sanitize = validate pour boolean
			'validate' => FILTER_VALIDATE_BOOLEAN
		],
		'integer' => [		// https://stackoverflow.blog/2022/12/26/why-the-number-input-is-the-worst-input/
			'tagName' => 'input',
			'attributes' => ['type' => 'number', 'inputmode' => 'numeric', 'pattern' => '[0-9]*'],
			'sanitize' => FILTER_SANITIZE_NUMBER_INT,
			'validate' => FILTER_VALIDATE_INT
		],
		'number' => [		// https://stackoverflow.blog/2022/12/26/why-the-number-input-is-the-worst-input/
			'tagName' => 'input',
			'attributes' => ['type' => 'number', 'inputmode' => 'decimal', 'pattern' => '[0-9]*\.?[0-9]*'],	// '/\d*[.,]?\d+/' pour accepter les virgules?
			'sanitize' => FILTER_SANITIZE_NUMBER_FLOAT,
			'validate' => FILTER_VALIDATE_FLOAT
		],
		'url' => [
			'tagName' => 'input',
			'attributes' => ['type' => 'url', 'inputmode' => 'url'],
			'sanitize' => FILTER_SANITIZE_URL,
			'validate' => FILTER_VALIDATE_URL
		],
		'email' => [
			'tagName' => 'input',
			'attributes' => ['type' => 'email', 'inputmode' => 'email'],
			'sanitize' => FILTER_SANITIZE_EMAIL,
			'validate' => FILTER_VALIDATE_EMAIL
		],
		'tel' => [
			'tagName' => 'input',
			'attributes' => ['type' => 'tel', 'inputmode' => 'tel', 'pattern' => "^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$"]
		],
		'search' => [
			'tagName' => 'input',
			'attributes' => ['type' => 'tel', 'inputmode' => 'search']
		],
	];
	
	// on a des types spéciaux : boolen, integer
	
	protected $type;
	protected $label;
	public $labelBefore = true;
	protected $tagName = 'input';
	protected $attributes = [];
	protected $sanitize = FILTER_DEFAULT;
	protected $validate = FILTER_DEFAULT;
	protected $initialValue = null;	// attribute value, mais pas pour textarea !
	protected $isModified = false;
	protected $value;
	
	protected $list = [];
	
	function type() {
		return $this->type;
	}
	
	function boolAttribute($attributeName) {
		if(in_array($attributeName, $this->attributes))	//  ex: required
			return true;
		if(isset($this->attributes[$attributeName]) and $this->attributes[$attributeName] == $attributeName)	//  ex: required=required
			return true;
		if(isset($this->attributes[$attributeName]) and $this->attributes[$attributeName] == 'true')	//  ex: required=true	=> attention. risque d'inconsistance avec html. voir au cas par cas?
			return true;
		return false;
	}
	
	function __construct($type = 'text', $attributes = [], $label = '') {
		$this->type = $type;
		if(isset(self::$specialsTypes[$type])) {
			$this->tagName = self::$specialsTypes[$type]['tagName'];
			if(isset(self::$specialsTypes[$type]['sanitize']))
				$this->sanitize = self::$specialsTypes[$type]['sanitize'];
			if(isset(self::$specialsTypes[$type]['validate']))
				$this->validate = self::$specialsTypes[$type]['validate'];
			$attributes = array_merge(self::$specialsTypes[$type]['attributes'], $attributes);
		}
		elseif(in_array($type, self::$inputTypes)) {
			$this->tagName = 'input';
			$attributes['type'] = $type;
		} else {
			$this->tagName = $type;
		}
		if(isset($attributes['value'])) {
			$this->initialValue = $this->sanitize($attributes['value']);
			if($this->tagName == 'teaxtarea')
				unset($attributes['value']);
		}
		
		$this->attributes = $attributes;
		$this->label = $label;
	}
	
	function set($v) {
		$this->isModified = true;
		if($v !== $this->initialValue) {
			$v = $this->sanitize($v);
			if($this->validate($v))
				$this->value = $v;
		}
	}
	
	function filterParams($nullable = false) {
		$params = ['options' => [], 'flags' => []];
		$options = [];
		if($nullable) {
			$options['default'] = null;
			$options['flags'][] = FILTER_NULL_ON_FAILURE;
		}
		if(isset($this->attributes['min']))
			$options['min_range'] = $this->attributes['min'];
		if(isset($this->attributes['max']))
			$options['max_range'] = $this->attributes['max'];
		$params['options'] = $options;
		return $params;
	}
	
	function sanitize($v) {
		return filter_var($v, $this->sanitize, $this->filterParams(true));
	}
	
	function validate($v) {
		$nullable = !$this->boolAttribute('required');
		
		if( !$nullable and ($v == null or $v == '') )
			return false;
		if( $nullable and ($v == null or $v == '') )
			return true;
		
		return filter_var($v, $this->validate, $this->filterParams()) ? true : false;
	}
	
	function check() {
		if(!$this->isModified)
			$v = $this->initialValue;
		else
			$v = $this->value;
		return $this->validate($v);
	}
	
	function get() {
		if(!$this->isModified)
			return $this->initialValue;
		else
			return $this->value;
	}
	
	static function startTag($n, $_attrs = []) {
		$attrs = '';
		foreach($_attrs as $k => $v)
			if(is_numeric($k))
				$attrs .= ' '.$v;
			elseif($v !== null and $v !== false)
				$attrs .= ' '.$k.'="'.$v.'"';
		return '<'.$n.$attrs.'>';
	}
	
	function outputLabel($close = true) {
		if(!$this->label)
			return;
		$attrs = [];
		if(isset($this->attributes['id']))
			$attrs['for'] = $this->attributes['id'];
		$out = self::startTag('label');
		if(!$close)
			return $out;
		return $out.$this->label.'</label>';
	}
	
	function addOptgroup($label, $disabled = false) {	// label required
		$group = new HTMLOptgroup($this, $label, $disabled);
		$this->list[] = $group;
		return $group;
	}
	
	function addOption($label, $value = null, $disabled = false) {
		$this->list[] = new HTMLOption($this, $value, $disabled);
		return $this;
	}
	
	function outputControl() {			// Attention. Type boolean => input type hidden devant.
		$out = '';
		$attributes = $this->attributes;
		if($this->type == 'boolean') {
			$out .= self::startTag($this->tagName, ['name' => $this->attributes['name'], 'type' => 'hidden', 'value' => self::$booleanFalseValue], false);
			$attributes['value'] = self::$booleanTrueValue;
			if($this->value)
				$attributes[] = 'checked';
		} elseif($this->tagName == 'teaxtarea') {
			unset($attributes['value']);
		} else {
			$attributes['value'] = $this->get();
		}
		$out .= self::startTag($this->tagName, $attributes, false);
		if($this->tagName == 'input')
			return $out;
		if($this->tagName == 'button')
			$out .= $this->label;
		if($this->tagName == 'teaxtarea')
			$out .= $this->get();
		if($this->tagName == 'select')
			foreach($this->list as $opt)
				$out .= $opt->output()."\n";
		
		return $out.'</'.$this->tagName.'>';
	}
	
	function outputControlWithLabel() {
		$ctrl = $this->outputControl();
		if(!$this->label or $this->type == 'hidden' or $this->tagName == 'button')
			return $ctrl;
		if(in_array($this->attributes['type'], self::$wrappedByLabelTypes))
			return $this->outputLabel(false).$ctrl.$this->label.'</label>';
		if($this->labelBefore)
			return $this->outputLabel().$ctrl;
		return $ctrl.$this->outputLabel();
	}
	
	function getPossibleErrors() {		/* TODO : i18n & customization */
		$r = $this->boolAttribute('required') ? ' required' : '';
		$list = ['type-'.$this->type => 'This '.$r.' field must be a valid '.$this->type.'.'];
		if(isset($this->attributes['min']))
			$list['min'] = 'Minumum value is '.$this->attributes['min'].'.';
		if(isset($this->attributes['max']))
			$list['max'] = 'Maximum value is '.$this->attributes['max'].'.';
		return $list;
	}
	
	function output() {
		if( $this->tagName == 'button' or ( $this->tagName == 'input' and in_array($this->attributes['type'], ['image', 'submit', 'hidden']) ) )
			return $this->outputControlWithLabel();
		$valid = ($this->check() or !$this->isModified);

		$out = '<div class="form-control '.$this->type.'">'.
			$this->outputControlWithLabel().
			'<ul class="possible-errors"';
		if(!$valid)
			$out .= '>';
		else
			$out .= ' hidden>';
		
		foreach($this->getPossibleErrors() as $type => $error)
			$out .= '<li class="error-'.$type.'">'.$error.'</li>';
		
		$out .= '</ul></div>';
		return $out;
	}
	
	function __toString() {
		return $this->output();
	}
	
}

/* + TODO boolean required (consents) */

/* + TODO : select multiple, select size ? */
/* + TODO alternative rendering ? */
/* + TODO FILTER_CALLBACK  array('options' => $callable) */


/* https://developer.mozilla.org/fr/docs/Learn/Forms/Other_form_controls
	
	
	TODO range ?
	TODO datalist elmt + list attribute ?
	TODO jauge ?
	TODO autres controls ?
	TODO Tester select

 */
