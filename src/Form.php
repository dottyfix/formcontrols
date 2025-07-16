<?php
namespace Dottyfix\Tools\FormControls;

class Form implements \ArrayAccess {	/* TODO token CSRF, Captcha, protect password ? */
	
	protected $controls = [];
	protected $attributes = [];
	protected $CSRF = null;
	protected $CSRFtoCheck = null;
	protected $hydrated = false;
	
	function __construct($attributes = []) {
		$this->attributes = $attributes;
	}
	
	static function new($attributes) {
		return new static($attributes);
	}
	
	function csrf($CSRF) {
		$this->CSRF = $CSRF;
	}
	
	function addControl($n, $type = 'text', $attributes = [], $label = '') {
		$attributes['name'] = $n;
		$this->controls[$n] = new FormControl($type, $attributes, $label);
		return $this;
		return $this->controls[$n];
	}
	
	static function flattenArrayToHydrate($array, $prefix = '') {
		$first = ($prefix == '');
		$result = [];

		foreach ($array as $key => $value) {
			if(!$first)
				$key = "[$key]";
			if (is_array($value) && array_keys($value) === range(0, count($value) - 1)) {
				$result[$prefix . $key . '[]'] = $value;
			} elseif (is_array($value)) {
				$newPrefix = $prefix . "$key";
				$result = array_merge($result, self::flattenArrayToHydrate($value, $newPrefix));
			} else {
				$result[$prefix . $key] = $value;
			}
		}
		return $result;
	}

    public static function unflattenHydratationArray($array) {
        $result = [];

        foreach ($array as $key => $value) {
            $keys = preg_replace('/\[(\w*)\]/', '.$1', rtrim($key, '[]'));
			$keys = str_replace('[', '.', $keys);
            $keys = explode('.', $keys);
            $ref = &$result;

            foreach ($keys as $subKey) {
                if ($subKey === '') {
                    $ref = &$ref[];
                } else {
                    if (!isset($ref[$subKey])) {
                        $ref[$subKey] = [];
                    }
                    $ref = &$ref[$subKey];
                }
            }
            $ref = $value;
        }

        return $result;
    }
    
    function toArray() {
		$arr = [];
		foreach($this->controls as $k => $c)
			$arr[$k] = $c->get();
		return self::unflattenHydratationArray($arr);
	}
	
	function hydrate($post) {
		if($this->CSRF)
			$this->CSRFtoCheck = isset($post['CSRF']) ? $post['CSRF'] : null;
			
		foreach(self::flattenArrayToHydrate($post) as $n => $v)
			if(isset($this->controls[$n]))
				$this->controls[$n]->set($v);
		
		$this->hydrated = true;
		return $this;
	}
	
	function check() {
		if(!$this->hydrated or ($this->CSRF and $this->CSRF !== $this->CSRFtoCheck))
			return false;
		foreach($this->controls as $n => $ctrl) {
			if(!$ctrl->check())
				return false;
		}
		return true;
	}
	
	function getControl($n) {
		return $this->controls[$n];
	}
	
	function output() {
		$out = $this->start();
		foreach($this->controls as $ctrl) {
			$out .= $ctrl->output();
		}
		$out .= $this->end();
		return $out;
	}
	
	/* MAGIC Methods*/
	
	function __toString() {
		return $this->output();
	}
	
	function start() {
		$attributes = $this->attributes;
		if(!isset($attributes['enctype']))
			foreach($this->controls as $ctrl)
				if($ctrl->type() == 'file')	/* TODO */
					$attributes['enctype'] = 'multipart/form-data';
		$out = FormControl::startTag('form', $attributes);
		if($this->CSRF)
			$out .= FormControl::startTag('input', ['type' => 'hidden', 'name' => 'CSRF', 'value' => $this->CSRF]);
		return $out;
	}
	
	function end() {
		return '</form>';
	}
	
    public function offsetSet($n, $v): void {
		if(isset($this->controls[$n]))
			$this->controls[$n]->set($v);
    }

    public function offsetExists($n): bool {
        return isset($this->controls[$n]);
    }

    public function offsetUnset($n): void {
        unset($this->controls[$n]);
    }

    public function offsetGet($n): mixed {
        return isset($this->controls[$n]) ? $this->controls[$n] : null;
    }
	
	
	/* TODO	feed select elements
	
	function addOptgroup
	function addOption
	
	$lastFormControl->method($args) et :
	chaining : return $this;
	
	*/
	
}
