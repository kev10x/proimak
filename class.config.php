<?php

class Config {
	private $data = array();

	function __construct() {
		include("install/table_names.inc");
		list($qhq, $numq) = dbQuery("SELECT * FROM $CONFIG_TABLE");
		for($i = 0; $i < $numq; $i++) {
			$configdata = dbResult($qhq, $i);
			$this->data[$configdata[0]] = $configdata[1];
		}
	}

	function get($value) {
		if(array_key_exists($value, $this->data))
			return $this->data[$value];
	}

	function set($field, $value) {
		include("install/table_names.inc");
		$this->data[$field] = $value;
		dbQuery("UPDATE $CONFIG_TABLE SET value='$value' WHERE name='$field'");
	}
}

// vim:ai:ts=4:sw=4
?>
