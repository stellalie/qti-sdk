<?php

namespace qtism\data;

use qtism\data\state\ResponseDeclarationCollection;
use qtism\data\state\OutcomeDeclarationCollection;
use qtism\common\utils\Format;
use \SplObjectStorage;
use \InvalidArgumentException;

/**
 * The AssessmentItem QTI class implementation. It contains all the information
 * needed by a QTI Test Runner to run a test which include items.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
class AssessmentItem extends QtiComponent implements QtiIdentifiable {
	
	/**
	 * From IMS QTI:
	 * 
	 * The principle identifier of the item. This identifier must have a 
	 * corresponding entry in the item's metadata.
	 * 
	 * @var string
	 */
	private $identifier;
	
	/**
	 * The language used in the Item.
	 * 
	 * @var string
	 */
	private $lang = '';
	
	/**
	 * From IMS QTI:
	 * 
	 * Items are classified into Adaptive Items and Non-adaptive Items.
	 * 
	 * @var boolean
	 */
	private $adaptive = false;
	
	/**
	 * Wether the item is time dependent or not.
	 * 
	 * @var boolean
	 */
	private $timeDependent;
	
	/**
	 * The response declarations.
	 * 
	 * @var ResponseDeclarationCollection
	 */
	private $responseDeclarations;
	
	/**
	 * The outcome declarations.
	 * 
	 * @var OutcomeDeclarationCollection
	 */
	private $outcomeDeclarations;
	
	/**
	 * Create a new AssessmentItem object.
	 * 
	 * @param string $identifier A QTI Identifier.
	 * @param boolean $timeDependent Whether the item is time dependent.
	 * @param string $lang The language (code) of the item.
	 * @throws InvalidArgumentException If $identifier is not a valid QTI Identifier, if $timeDependent is not a boolean value, or if $lang is not a string value. 
	 */
	public function __construct($identifier, $timeDependent, $lang = '') {
		$this->setIdentifier($identifier);
		$this->setTimeDependent($timeDependent);
		$this->setLang($lang);
		$this->setResponseDeclarations(new ResponseDeclarationCollection());
		$this->setOutcomeDeclarations(new OutcomeDeclarationCollection());
	}
	
	/**
	 * Set the identifier.
	 * 
	 * @param string $identifier A QTI Identifier.
	 * @throws InvalidArgumentException If $identifier is not a valid QTI Identifier.
	 */
	public function setIdentifier($identifier) {
		if (Format::isIdentifier($identifier)) {
			$this->identifier = $identifier;
		}
		else {
			$msg = "The identifier argument must be a valid QTI Identifier, '" . $identifier . "' given.";
			throw new InvalidArgumentException($msg);
		}
	}
	
	/**
	 * Get the identifier.
	 * 
	 * @return string A QTI identifier.
	 */
	public function getIdentifier() {
		return $this->identifier;
	}
	
	/**
	 * Set the language code.
	 * 
	 * @param string $lang A language code.
	 * @throws InvalidArgumentException If $lang is not a string.
	 */
	public function setLang($lang = '') {
		if (is_string($lang)) {
			$this->lang = $lang;
		}
		else {
			$msg = "The lang argument must be a string, '" . gettype($lang) . "' given.";
			throw new InvalidArgumentException($msg);
		}
	}
	
	/**
	 * Get the language code.
	 * 
	 * @return string A language code.
	 */
	public function getLang() {
		return $this->lang;
	}
	
	/**
	 * Wether the AssessmentItem has a language.
	 * 
	 * @return boolean
	 */
	public function hasLang() {
		$lang = $this->getLang();
		return !empty($lang);
	}
	
	/**
	 * Set whether the item is adaptive.
	 * 
	 * @param boolean $adaptive Adaptive or not.
	 * @throws InvalidArgumentException If $adaptive is not a boolean value.
	 */
	public function setAdaptive($adaptive) {
		if (is_bool($adaptive)) {
			$this->adaptive = $adaptive;
		}
		else {
			$msg = "The adaptive argument must be a boolean, '" . gettype($adaptive) . "' given.";
			throw new InvalidArgumentException($msg);
		}
	}
	
	/**
	 * Whether the item is adaptive.
	 * 
	 * @return boolean
	 */
	public function isAdaptive() {
		return $this->adaptive;
	}
	
	/**
	 * Set whether the item is time dependent or not.
	 * 
	 * @param boolean $timeDependent Time dependent or not.
	 * @throws InvalidArgumentException If $timeDependent is not a boolean value.
	 */
	public function setTimeDependent($timeDependent) {
		if (is_bool($timeDependent)) {
			$this->timeDependent = $timeDependent;
		}
		else {
			$msg = "The timeDependent argument must be a boolean, '" . gettype($timeDependent) . "' given.";
			throw new InvalidArgumentException($msg);
		}
	}
	
	/**
	 * Wether the item is time dependent.
	 * 
	 * @return boolean 
	 */
	public function isTimeDependent() {
		return $this->timeDependent;
	}
	
	/**
	 * Set the response declarations.
	 * 
	 * @param ResponseDeclarationCollection $responseDeclarations A collection of ResponseDeclaration objects
	 */
	public function setResponseDeclarations(ResponseDeclarationCollection $responseDeclarations) {
		$this->responseDeclarations = $responseDeclarations;
	}
	
	/**
	 * Get the response declarations.
	 * 
	 * @return ResponseDeclarationCollection A collection of ResponseDeclaration objects.
	 */
	public function getResponseDeclarations() {
		return $this->responseDeclarations;
	}
	
	/**
	 * Set the outcome declarations.
	 * 
	 * @param OutcomeDeclarationCollection $outcomeDeclarations A collection of OutcomeDeclaration objects.
	 */
	public function setOutcomeDeclarations(OutcomeDeclarationCollection $outcomeDeclarations) {
		$this->outcomeDeclarations = $outcomeDeclarations;
	}
	
	/**
	 * Get the outcome declarations.
	 * 
	 * @return OutcomeDeclarationCollection A collection of OutcomeDeclaration objects.
	 */
	public function getOutcomeDeclarations() {
		return $this->outcomeDeclarations;
	}
	
	public function getQtiClassName() {
		return 'assessmentItem';
	}
	
	public function getComponents() {
		$comp = array_merge($this->getOutcomeDeclarations()->getArrayCopy(),
							$this->getResponseDeclarations()->getArrayCopy());
		
		return new QtiComponentCollection($comp);
	}
}