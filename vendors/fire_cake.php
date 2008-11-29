<?php
/* SVN FILE: $Id$ */
/**
 * FirePHP Class for CakePHP
 *
 * Provides most of the functionality offered by FirePHPCore
 * Interoperates with FirePHP extension for firefox
 *
 * For more information see: http://www.firephp.org/
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link            http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package         debug_kit.
 * @subpackage      debug_kit.vendors
 * @since           
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Debugger');

class FireCake extends Object {
/**
 * Options for FireCake.
 *
 * @see _defaultOptions and setOptions();
 * @var string
 */
	var	$options = array();
/**
 * Default Options used in CakeFirePhp
 *
 * @var string
 * @access protected
 */
	var $_defaultOptions = array(
		'maxObjectDepth' => 10,
	    'maxArrayDepth' => 20,
	    'useNativeJsonEncode' => true,
	    'includeLineNumbers' => true,
	);
/**
 * Message Levels for messages sent via FirePHP
 *
 * @var array
 */	
	var $_levels = array(
		'log' => 'LOG',
		'info' => 'INFO',
		'warn' => 'WARN',
		'error' => 'ERROR',
		'dump' => 'DUMP',
		'trace' => 'TRACE',
		'exception' => 'EXCEPTION',
		'table' => 'TABLE',
		'groupStart' => 'GROUP_START',
		'groupEnd' => 'GROUP_END',
	);
	
	var $_version = '0.2.1';
/**
 * internal messageIndex counter
 *
 * @var int
 * @access protected
 */
	var $_messageIndex = 1;
/**
 * stack of objects encoded by stringEncode()
 *
 * @var array
 **/
	var $_encodedObjects = array();
/**
 * get Instance of the singleton
 *
 * @access public
 * @static
 * @return void
 */
	function &getInstance() {
		static $instance = array();
		if (!isset($instance[0]) || !$instance[0]) {
			$args = func_get_args();
			if (!isset($args[0])) {
				$args[0] = 'FireCake';
			}
			$instance[0] = new $args[0]();
			$instance[0]->setOptions();
		}
		return $instance[0];
	}

/**
 * setOptions
 *
 * @param array $options Array of options to set.
 * @access public
 * @static
 * @return void
 */
	function setOptions($options = array()) {
		$_this = FireCake::getInstance();
		if (empty($_this->options)) {
			$_this->options = array_merge($_this->_defaultOptions, $options);
		} else {
			$_this->options = array_merge($_this->options, $options);
		}
	}
/**
 * Return boolean based on presence of FirePHP extension
 *
 * @access public
 * @return boolean
 **/
	function detectClientExtension() {
		$ua = FireCake::getUserAgent();
		if (!preg_match('/\sFirePHP\/([\.|\d]*)\s?/si', $ua, $match) || !version_compare($match[1], '0.0.6', '>=')) {
			return false;
		}
		return true;
	}
/**
 * Get the Current UserAgent
 *
 * @access public
 * @static
 * @return string UserAgent string of active client connection
 **/
	function getUserAgent() {
		return env('HTTP_USER_AGENT');
	}
/**
 * Convenience wrapper for LOG messages 
 *
 * @param string $message Message to log 
 * @param string $label Label for message (optional)
 * @access public
 * @static
 * @return void
 */	
	function log($message, $label = null) {
		FireCake::fb($message, $label, 'log');
	}
/**
 * Convenience wrapper for WARN messages 
 *
 * @param string $message Message to log 
 * @param string $label Label for message (optional)
 * @access public
 * @static
 * @return void
 */	
	function warn($message, $label = null) {
		FireCake::fb($message, $label, 'warn');
	}
/**
 * Convenience wrapper for INFO messages 
 *
 * @param string $message Message to log 
 * @param string $label Label for message (optional)
 * @access public
 * @static
 * @return void
 */	
	function info($message, $label = null) {
		FireCake::fb($message, $label, 'info');
	}
/**
 * Convenience wrapper for ERROR messages 
 *
 * @param string $message Message to log 
 * @param string $label Label for message (optional)
 * @access public
 * @static
 * @return void
 */	
	function error($message, $label = null) {
		FireCake::fb($message, $label, 'error');
	}
/**
 * Convenience wrapper for TABLE messages 
 *
 * @param string $message Message to log 
 * @param string $label Label for message (optional)
 * @access public
 * @static
 * @return void
 */	
	function table($message, $label = null) {
		FireCake::fb($message, $label, 'table');
	}
/**
 * Convenience wrapper for DUMP messages 
 *
 * @param string $message Message to log 
 * @param string $label Unique label for message
 * @access public
 * @static
 * @return void
 */	
	function dump($message, $label) {
		FireCake::fb($message, $label, 'dump');
	}
/**
 * Convenience wrapper for TRACE messages 
 *
 * @param string $label Label for message (optional)
 * @access public
 * @return void
 */	
	function trace($label = null) {
		FireCake::fb($label, 'trace');
	}
/**
 * fb - Send messages with FireCake to FirePHP
 *
 * Much like FirePHP's fb() this method can be called with various parameter counts
 * fb($message) - Just send a message defaults to LOG type
 * fb($message, $type) - Send a message with a specific type
 * fb($message, $label, $type) - Send a message with a custom label and type.
 * 
 * @param mixed $message Message to output. For other parameters see usage above.
 * @static
 * @return void
 **/
	function fb($message) {
		$_this = FireCake::getInstance();

		if (headers_sent($filename, $linenum)) {
			trigger_error(sprintf(__('Headers already sent in %s on line %s. Cannot send log data to FirePHP.', true),$filename, $linenum), E_USER_WARNING);
			return false;
		}
		if (!$_this->detectClientExtension()) {
			return false;
		}
	
		$args = func_get_args();
		$type = $label = null;
		switch (count($args)) {
			case 1:
				$type = $_this->_levels['log'];
				break;
			case 2:
				$type = $args[1];
				break;
			case 3:
				$type = $args[2];
				$label = $args[1];
				break;
			default:
				trigger_error(__('Incorrect parameter count for FireCake::fb()', true), E_USER_WARNING);
				return false;
		}
		if (isset($_this->_levels[$type])) {
			$type = $_this->_levels[$type];
		} else {
			$type = $_this->_levels['log'];
		}

		$meta = array();
		$skipFinalObjectEncode = false;		
		if ($type == $_this->_levels['trace']) {
			$trace = debug_backtrace();
			if (!$trace) {
				return false;
			}
			for ($i = 0, $len = count($trace); $i < $len ; $i++) {
				$selfCall = (isset($trace[$i]['class']) && isset($trace[$i]['file']) && $trace[$i]['class'] == 'FireCake');
				if (!$selfCall) {
					$message = array(
						'Class' => isset($trace[$i]['class']) ? $trace[$i]['class'] : '',
						'Type' => isset($trace[$i]['type']) ? $trace[$i]['type'] : '',
						'Function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : '',
						'Message' => $args[0],
						'File' => isset($trace[$i]['file']) ? Debugger::trimPath($trace[$i]['file']) : '',
						'Line' => isset($trace[$i]['line']) ? $trace[$i]['line'] : '',
						'Args' => isset($trace[$i]['args']) ? $_this->stringEncode($trace[$i]['args']) : '',
						'Trace' => $_this->_escapeTrace(array_splice($trace, $i+1))
					);
					$meta['file'] = isset($trace[$i]['file']) ? Debugger::trimPath($trace[$i]['file']):'';
					$meta['line'] = isset($trace[$i]['line']) ? $trace[$i]['line']:'';
					break;
				}
			}
			$skipFinalObjectEncode = true;
		}
		if ($type == $_this->_levels['table']) {
			//handle tables
			
			$skipFinalObjectEncode = true;
		}

		if ($_this->options['includeLineNumbers']) {
			//handle line numbers
		}
		$structureIndex = 1;
		if ($type == $_this->_levels['dump']) {
			$structureIndex = 2;
			$_this->_sendHeader('X-Wf-1-Structure-2','http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1');
		} else {
			$_this->_sendHeader('X-Wf-1-Structure-1','http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
		}

		$_this->_sendHeader('X-Wf-Protocol-1','http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
		$_this->_sendHeader('X-Wf-1-Plugin-1','http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/'. $_this->_version);
		
		if ($type == $_this->_levels['dump']) {
			$dump = $_this->jsonEncode($message, $skipFinalObjectEncode);
			$msg = sprintf('{"%s":%s}', $label, $dump);
		} else {
			$metaMsg = array('Type' => $type);
			if ($label !== null) {
				$metaMsg['Label'] = $label;
			}
			if (isset($meta['file'])) {
				$metaMsg['File'] = $meta['file'];
			}
			if (isset($meta['line'])) {
				$metaMsg['Line'] = $meta['line'];
			}
			$msg = '[' . $_this->jsonEncode($metaMsg) . ',' . $_this->jsonEncode($message, $skipFinalObjectEncode).']';
		}
		$lines = explode("\n", chunk_split($msg, 5000, "\n"));
		foreach ($lines as $i => $line) {
			if (empty($line)) {
				continue;
			}
			$header = sprintf('X-Wf-1-%s-1-%s', $structureIndex, $_this->_messageIndex);
			if (count($lines) > 2) {
				// Message needs to be split into multiple parts
				$first = ($i == 0) ? strlen($msg) : '';
				$end = ($i < count($lines) - 2) ? '\\' : '';
				$message = sprintf('%s|%s|%s', $first, $line, $end);
				$_this->_sendHeader($header, $message);
			} else {
				$_this->_sendHeader($header, strlen($line) . '|' . $line . '|');
			}
			$_this->_messageIndex++;
			if ($_this->_messageIndex > 99999) {
				trigger_error(__('Maximum number (99,999) of messages reached!', true), E_USER_WARNING);
			}
		}
		$_this->_sendHeader('X-Wf-1-Index', $_this->_messageIndex - 1);
		return true;
	}
/**
 * Fix a trace for use in output
 *
 * @param mixed $trace Trace to fix
 * @access protected
 * @static
 * @return string
 **/
	function _escapeTrace($trace) {
		for ($i = 0, $len = count($trace); $i < $len; $i++) {
			if (isset($trace[$i]['file'])) {
				$trace[$i]['file'] = Debugger::trimPath($trace[$i]['file']);
			}
			if (isset($trace[$i]['args'])) {
				$trace[$i]['args'] = $this->stringEncode($trace[$i]['args']);
			}
		}
		return $trace;
	}
/**
 * Encode non string objects to string.
 * Filter out recursion, so no errors are raised by json_encode or $javascript->object()
 * 
 * @param mixed $object Object or variable to encode to string.
 * @param int $objectDepth Current Depth in object chains.
 * @param int $arrayDepth Current Depth in array chains.
 * @static
 * @return void
 **/
	function stringEncode($object, $objectDepth = 1, $arrayDepth = 1) {
		$_this = FireCake::getInstance();
		$return = array();
		if (is_resource($object)) {
			return '** ' . (string)$object . '**';
		}
		if (is_object($object)) {
			if ($objectDepth == $_this->options['maxObjectDepth']) {
				return '** Max Object Depth (' . $_this->options['maxObjectDepth'] . ') **';
			}
			foreach ($_this->_encodedObjects as $encoded) {
				if ($encoded === $object) {
					return '** Recursion (' . get_class($object) . ') **';
				}
			}
			$_this->_encodedObjects[] =  $object;

			$return['__className'] = $class = get_class($object);
			$properties = (array) $object;
			foreach ($properties as $name => $property) {
				$return[$name] = FireCake::stringEncode($property, 1, $objectDepth + 1);
			}
			array_pop($_this->_encodedObjects);
		}
		if (is_array($object)) {
			if ($arrayDepth == $_this->options['maxArrayDepth']) {
				return '** Max Array Depth ('. $_this->options['maxArrayDepth'] . ') **';
			}
			foreach ($object as $key => $value) {
				$return[$key] = FireCake::stringEncode($value, 1, $arrayDepth + 1);
			}
		}
		if (is_string($object) || is_numeric($object)) {
			return $object;
		}
		if (is_bool($object)) {
			return ($object) ? 'true' : 'false';
		}
		if (is_null($object)) {
			return 'null';
		}
		return $return;
	}
/**
 * Encode an object into JSON
 *
 * @param mixed $object Object or array to json encode
 * @param boolean $doIt
 * @access public
 * @static
 * @return string
 **/
	function jsonEncode($object, $skipEncode = false) {
		$_this = FireCake::getInstance();
		if (!$skipEncode) {
			$object = FireCake::stringEncode($object);
		}
		
		if (function_exists('json_encode') && $_this->options['useNativeJsonEncode']) {
			return json_encode($object);
		} else {
			return FireCake::_jsonEncode($object);
		}
	}
/**
 * jsonEncode Helper method for PHP4 compatibility
 *
 * @param mixed $object Something to encode
 * @access protected
 * @static
 * @return string
 **/
	function _jsonEncode($object) {
		if (!class_exists('JavascriptHelper')) {
			App::import('Helper', 'Javascript');
		}
		$javascript = new JavascriptHelper();
		$javascript->useNative = false;
		return $javascript->object($object);
	}
/**
 * Send Headers - write headers.
 *
 * @access protected
 * @return void
 **/
	function _sendHeader($name, $value) {
		header($name . ': ' . $value);
	}
}
?>