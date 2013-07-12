<?php

namespace qtism\data\storage\xml\marshalling;

use qtism\data\QtiComponent;
use qtism\data\utils\Reflection;
use qtism\data\storage\xml\Utils;
use \DOMDocument;
use \DOMElement;
use \RuntimeException;
use \InvalidArgumentException;
use \ReflectionClass;

abstract class Marshaller {
	
	private static $marshallerMapping = array(	'default' => 'DefaultVal',
													'null' => 'NullValue',
													'max' => 'Operator',
													'min' => 'Operator',
													'gcd' => 'Operator',
													'lcm' => 'Operator',
													'multiple' => 'Operator',
													'ordered' => 'Operator',
													'containerSize' => 'Operator',
													'isNull' => 'Operator',
													'random' => 'Operator',
													'member' => 'Operator',
													'delete' => 'Operator',
													'contains' => 'Operator',
													'not' => 'Operator',
													'and' => 'Operator',
													'or' => 'Operator',
													'match' => 'Operator',
													'lt' => 'Operator',
													'gt' => 'Operator',
													'lte' => 'Operator',
													'gte' => 'Operator',
													'durationLT' => 'Operator',
													'durationGTE' => 'Operator',
													'sum' => 'Operator',
													'product' => 'Operator',
													'subtract' => 'Operator',
													'divide' => 'Operator',
													'power' => 'Operator',
													'integerDivide' => 'Operator',
													'integerModulus' => 'Operator',
													'truncate' => 'Operator',
													'round' => 'Operator',
													'integerToFloat' => 'Operator',
													'outcomeIf' => 'OutcomeControl',
													'outcomeElseIf' => 'OutcomeControl',
													'outcomeElse' => 'OutcomeControl');
	
	
	/**
	 * The DOMCradle is a DOMDocument object which will be used as a 'DOMElement cradle'. It
	 * gives the opportunity to marshallers to create DOMElement that can be imported in an
	 * exported document later on.
	 * 
	 * @var DOMDocument
	 */
	private static $DOMCradle = null;
	
	/**
	 * A reference to the Marshaller Factory to use when creating other marshallers
	 * from this marshaller.
	 * 
	 * @var MarshallerFactory
	 */
	private $marshallerFactory = null;
	
	/**
	 * Get a DOMDocument to be used by marshaller implementations in order to create
	 * new nodes to be imported in a currenlty exported document.
	 * 
	 * @return DOMDocument A unique DOMDocument object.
	 */
	protected static function getDOMCradle() {
		if (empty(self::$DOMCradle)) {
			self::$DOMCradle = new DOMDocument('1.0', 'UTF-8');
		}
		
		return self::$DOMCradle;
	}
	
	protected static function getMarshallerMapping() {
		return self::$marshallerMapping;
	}
	
	/**
	 * Set the MarshallerFactory object to use to create other Marshaller objects.
	 * 
	 * @param MarshallerFactory $marshallerFactory A MarshallerFactory object.
	 */
	public function setMarshallerFactory(MarshallerFactory $marshallerFactory = null) {
		$this->marshallerFactory = $marshallerFactory;
	}
	
	/**
	 * Return the MarshallerFactory object to use to create other Marshaller objects.
	 * If no MarshallerFactory object was previously defined, a default 'raw' MarshallerFactory
	 * object will be returned.
	 * 
	 * @return MarshallerFactory A MarshallerFactory object.
	 */
	public function getMarshallerFactory() {
		if ($this->marshallerFactory === null) {
			$this->setMarshallerFactory(new MarshallerFactory());
		}
		
		return $this->marshallerFactory;
	}
	
	public function __call($method, $args) {
		if ($method == 'marshall' || $method == 'unmarshall') {
			if (count($args) >= 1) {
				if ($method == 'marshall') {
					$component = $args[0];
					if ($this->getExpectedQtiClassName() === '' || ($component->getQtiClassName() == $this->getExpectedQtiClassName())) {
						return $this->marshall($component);
					}
				}
				else {
					$element = $args[0];
					if ($this->getExpectedQtiClassName() === '' || ($element->nodeName == $this->getExpectedQtiClassName())) {
						return call_user_func_array(array($this, 'unmarshall'), $args);
					}
					else {
						throw new RuntimeException("Unexpected nodeName/className '" . $element->nodeName . "'/'" . $this->getExpectedQtiClassName() . "'.");
					}
				}
			}
			else {
				throw new RuntimeException("Method '${method}' only accepts a single argument.");
			}
		}
			
		throw new RuntimeException("Unknown method Marshaller::'${method}'.");
	}
	
	/**
	 * Get the attribute value of a given DOMElement object, cast in a given datatype.
	 * 
	 * @param DOMElement $element The element the attribute you want to retrieve the value is bound to.
	 * @param string $attribute The attribute name.
	 * @param string $datatype The returned datatype. Accepted values are 'string', 'integer', 'float', 'double' and 'boolean'.
	 * @throws InvalidArgumentException If $datatype is not in the range of possible values.
	 * @return mixed The attribute value with the provided $datatype, or null if the attribute does not exist in $element.
	 */
	public static function getDOMElementAttributeAs(DOMElement $element, $attribute, $datatype = 'string') {
		$attr = $element->getAttribute($attribute);
		
		if ($attr !== '') {
			switch ($datatype) {
				case 'string':
					return $attr;
				break;
				
				case 'integer':
					return intval($attr);
				break;
				
				case 'float':
					return floatval($attr);
				break;
				
				case 'double':
					return doubleval($attr);
				break;
				
				case 'boolean':
					return ($attr == 'true') ? true : false;
				break;
				
				default:
					throw new InvalidArgumentException("Unknown datatype '${datatype}'.");
				break;
			}
		}
		else {
			return null;
		}
	}
	
	/**
	 * Set the attribute value of a given DOMElement object. Boolean values will be transformed
	 * 
	 * @param DOMElement $element A DOMElement object.
	 * @param string $attribute An XML attribute name.
	 * @param mixed $value A given value.
	 */
	public static function setDOMElementAttribute(DOMElement $element, $attribute, $value) {
		switch (gettype($value)) {
			case 'boolean':
				$element->setAttribute($attribute, ($value === true) ? 'true' : 'false');
			break;
			
			default:
				$element->setAttribute($attribute, '' . $value);
			break;
		}
	}
	
	/**
	 * Set the node value of a given DOMElement object. Boolean values will be transformed as 'true'|'false'.
	 * 
	 * @param DOMElement $element A DOMElement object.
	 * @param mixed $value A given value.
	 */
	public static function setDOMElementValue(DOMElement $element, $value) {
		switch (gettype($value)) {
			case 'boolean':
				$element->nodeValue = ($value === true) ? 'true' : 'false';
			break;
			
			default:
				$element->nodeValue = $value;
			break;
		}
	} 
	
	/**
	 * Get the first child DOM Node with nodeType attribute equals to XML_ELEMENT_NODE.
	 * This is very useful to get a sub-node without having to exclude text nodes, cdata,
	 * ... manually.
	 * 
	 * @param DOMElement $element A DOMElement object
	 * @return DOMElement|boolean A DOMElement If a child node with nodeType = XML_ELEMENT_NODE or false if nothing found.
	 */
	public static function getFirstChildElement($element) {
		$children = $element->childNodes;
		for ($i = 0; $i < $children->length; $i++) {
			$child = $children->item($i);
			if ($child->nodeType === XML_ELEMENT_NODE) {
				return $child;
			}
		}
		
		return false;
	}
	
	/**
	 * Get the children DOM Nodes with nodeType attribute equals to XML_ELEMENT_NODE.
	 * 
	 * @param DOMElement $element A DOMElement object.
	 * @return array An array of DOMElement objects.
	 */
	public static function getChildElements($element) {
		$children = $element->childNodes;
		$returnValue = array();
		
		for ($i = 0; $i < $children->length; $i++) {
			if ($children->item($i)->nodeType === XML_ELEMENT_NODE) {
				$returnValue[] = $children->item($i);
			}
		}
		
		return $returnValue;
	}
	
	/**
	 * Get the child elements of a given element by tag name. This method does
	 * not behave like DOMElement::getElementsByTagName. It only returns the direct
	 * child elements that matches $tagName but does not go recursive.
	 * 
	 * @param DOMElement $element A DOMElement object.
	 * @param mixed $tagName The name of the tags you would like to retrieve or an array of tags to match.
	 * @return array An array of DOMElement objects.
	 */
	public static function getChildElementsByTagName($element, $tagName) {
		if (!is_array($tagName)) {
			$tagName = array($tagName);
		}
		
		$rawElts = self::getChildElements($element);
		$returnValue = array();
		
		foreach ($rawElts as $elt) {
			
			if (in_array(Utils::getLocalNodeName($elt->nodeName), $tagName)) {
				$returnValue[] = $elt;
			}
		}
		
		return $returnValue;
	}
	
	/**
	 * Marshall a QtiComponent object into its QTI-XML equivalent.
	 * 
	 * @param QtiComponent $component A QtiComponent object to marshall.
	 * @return DOMElement A DOMElement object.
	 * @throws MarshallingException If an error occurs during the marshalling process.
	 */
	abstract protected function marshall(QtiComponent $component);
	
	/**
	 * Unmarshall a DOMElement object into its QTI Data Model equivalent.
	 * 
	 * @param DOMElement $element A DOMElement object.
	 * @return QtiComponent A QtiComponent object.
	 */
	abstract protected function unmarshall(DOMElement $element);
	
	/**
	 * Get the class name/tag name of the QtiComponent/DOMElement which can be handled
	 * by the Marshaller's implementation. 
	 * 
	 * Return an empty string if the marshaller implementation does not expect a particular
	 * QTI class name. 
	 * 
	 * @return string A QTI class name or an empty string.
	 */
	abstract public function getExpectedQtiClassName();
}