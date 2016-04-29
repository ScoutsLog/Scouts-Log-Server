<?php

/**
 * Associative Array Sort Function (PHP 5)
 * 
 * The purpose of this class is to provide a mechanism in
 * which to sort an associative array by one or more keys
 * 
 * @package php_sorter
 */
class php_sorter {
	/**
	 * Private variable to store data
	 * 
	 * @var array $data Data to be sorted
	 * @access private
	 */
	private $data;
	
	/**
	 * Private variable to store sorting keys
	 * 
	 * Keys must be entered in an array fashion with the first
	 * index being the field name and the second index being
	 * the sorting direction
	 * 
	 * @var array $keys Keys to sort on
	 * @access private
	 */
	private $keys;
	
	/**
	 * Class constructor
	 * 
	 * The constructor function assigns data into the class
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct($data, $keys) {
		$this->data = $data;
		$this->keys = $keys;
	}
  
	/**
	 * Private sorting function
	 * 
	 * This function performs the actual sorting work for
	 * each key in the sorting key array
	 * 
	 * @access private
	 * @return array
	 */
	private function sortcmp($a, $b, $i=0) {
		switch ($this->keys[$i][2]) {
			case 'ALPHANUMERIC':
				
				// Check for empty values
				switch (true) {
					case (empty($a[$this->keys[$i][0]]) and !empty($b[$this->keys[$i][0]])):
						$r = -1;
						
						break(2);
					case (!empty($a[$this->keys[$i][0]]) and empty($b[$this->keys[$i][0]])):
						$r = 1;
						
						break(2);
					case (empty($a[$this->keys[$i][0]]) and empty($b[$this->keys[$i][0]])):
						$r = 0;
						
						break(2);
				}
				
				// Break strings into components
				$a_parts = preg_split("/([a-zA-Z]+)/", preg_replace("/[^a-zA-Z0-9]+/", '', $a[$this->keys[$i][0]]), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				$b_parts = preg_split("/([a-zA-Z]+)/", preg_replace("/[^a-zA-Z0-9]+/", '', $b[$this->keys[$i][0]]), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				
				$a_count = count($a_parts);
				$b_count = count($b_parts);
				
				// Check number of parts
				if ($a_count != $b_count) {
					if ($a_count > $b_count) {
						$temp_b = array();
				
						for ($p=0; $p < $a_count; $p++) {
							if (is_numeric($a_parts[$p])) {
								// Number
				
								if (is_numeric($b_parts[$p]) and isset($b_parts[$p])) {
									$temp_b[$p] = $b_parts[$p];
								} else {
									$temp_b[$p] = 0;
									array_unshift($b_parts, 0);
								}
							} else {
								// Text
				
								if (!is_numeric($b_parts[$p]) and isset($b_parts[$p])) {
									$temp_b[$p] = $b_parts[$p];
								} else {
									$temp_b[$p] = '';
									array_unshift($b_parts, '');
								}
							}
						}
				
						$b_parts = $temp_b;
					} else {
						$temp_a = array();
				
						for ($p=0; $p < $b_count; $p++) {
							if (is_numeric($b_parts[$p])) {
								// Number
				
								if (is_numeric($a_parts[$p]) and isset($a_parts[$p])) {
									$temp_a[$p] = $a_parts[$p];
								} else {
									$temp_a[$p] = 0;
									array_unshift($a_parts, 0);
								}
							} else {
								// Text
				
								if (!is_numeric($a_parts[$p]) and isset($a_parts[$p])) {
									$temp_a[$p] = $a_parts[$p];
								} else {
									$temp_a[$p] = '';
									array_unshift($a_parts, '');
								}
							}
						}
				
						$a_parts = $temp_a;
					}
				}
				
				
				// Compare each set of parts
				$_r = array();
				
				for ($p=0; $p < $a_count; $p++) {
					if (is_numeric($a_parts[$p]) === true) {
						// Number
						$_a = floatval($a_parts[$p]);
						$_b = floatval($b_parts[$p]);
				
						if ($_a == $_b) {
							$_r[$p] = 0;
						} else {
							$_r[$p] = ($_a < $_b) ? -1 : 1;
						}
					} else {
						// Text
				
						$_r[$p] = strcasecmp($a_parts[$p], $b_parts[$p]);
					}
				}
				
				
				// Determine final comparison
				$r = 0;
				
				for ($p=0; $p < $a_count; $p++) {
					if ($_r[$p] == 0) continue;
				
					$r = $_r[$p];
				
					break;
				}
								
				
				break;
			case 'NUMERIC':
				if ($a[$this->keys[$i][0]] == $b[$this->keys[$i][0]]) {
	       			$r = 0;
				} else {
	    			$r = ($a[$this->keys[$i][0]] < $b[$this->keys[$i][0]]) ? -1 : 1;
				}
				
				break;
			case 'ALPHA':
			default:
				$r = strcasecmp($a[$this->keys[$i][0]], $b[$this->keys[$i][0]]);
				
				break;
		}

		// Adjust sort direction
		if ($this->keys[$i][1] == "DESC") $r = $r * -1;

		// See if we need to check additional keys
		if ($r==0) {
			$i++;
			
			if ($this->keys[$i]) {
				$r = $this->sortcmp($a, $b, $i);
			}
		}

		// Return result 
		return $r;
	}

	/**
	 * Sort Method
	 * 
	 * This method sorts the array based on the keys specified
	 * 
	 * @access public
	 * @return array
	 */
	public function sort() {
		if(count($this->keys)) {
			usort($this->data, array($this, "sortcmp"));
		}
		
		return $this->data;
	}
}



