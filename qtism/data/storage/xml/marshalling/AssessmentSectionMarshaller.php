<?php

namespace qtism\data\storage\xml\marshalling;

use qtism\data\QtiComponent;
use qtism\data\QtiComponentCollection;
use qtism\data\AssessmentSection;
use qtism\data\SectionPartCollection;
use qtism\data\RubricBlockCollection;
use \DOMElement;
use \DOMXPath;
use \ReflectionClass;

class AssessmentSectionMarshaller extends RecursiveMarshaller {
	
	protected function unmarshallChildrenKnown(DOMElement $element, QtiComponentCollection $children, AssessmentSection $assessmentSection = null) {
		$baseMarshaller = new SectionPartMarshaller();
		$baseComponent = $baseMarshaller->unmarshall($element);
		
		if (($title = static::getDOMElementAttributeAs($element, 'title')) !== null) {
			
			if (($visible = static::getDOMElementAttributeAs($element, 'visible', 'boolean')) !== null) {
				
				if (empty($assessmentSection)) {
					$object = new AssessmentSection($baseComponent->getIdentifier(), $title, $visible);
				}
				else {
					$object = $assessmentSection;
					$object->setIdentifier($baseComponent->getIdentifier());
					$object->setTitle($title);
					$object->setVisible($visible);
				}
				
				
				// One day... We will be able to overload methods in PHP... :'(
				$object->setRequired($baseComponent->isRequired());
				$object->setFixed($baseComponent->isFixed());
				$object->setPreConditions($baseComponent->getPreConditions());
				$object->setBranchRules($baseComponent->getBranchRules());
				$object->setItemSessionControl($baseComponent->getItemSessionControl());
				$object->setTimeLimits($baseComponent->getTimeLimits());
				
				// Deal with the keepTogether attribute.
				if (($keepTogether = static::getDOMElementAttributeAs($element, 'keepTogether', 'boolean')) !== null) {
					$object->setKeepTogether($keepTogether);
				}
				
				// Deal with selection elements.
				$selectionElements = $element->getElementsByTagName('selection');
				if ($selectionElements->length == 1) {
					$marshaller = $this->getMarshallerFactory()->createMarshaller($selectionElements->item(0));
					$object->setSelection($marshaller->unmarshall($selectionElements->item(0)));
				}
				
				// Deal with ordering elements.
				$orderingElements = $element->getElementsByTagName('ordering');
				if ($orderingElements->length == 1) {
					$marshaller = $this->getMarshallerFactory()->createMarshaller($orderingElements->item(0));
					$object->setOrdering($marshaller->unmarshall($orderingElements->item(0)));
				}
				
				// Deal with rubrickBlocks.
				$rubricBlockElements = $element->getElementsByTagName('rubricBlock');
				if ($rubricBlockElements->length > 0) {
					$rubricBlocks = new RubricBlockCollection();
					for ($i = 0; $i < $rubricBlockElements->length; $i++) {
						$marshaller = $this->getMarshallerFactory()->createMarshaller($rubricBlockElements->item($i));
						$rubricBlocks[] = $marshaller->unmarshall($rubricBlockElements->item($i));
					}
					
					$object->setRubricBlocks($rubricBlocks);
				}
				
				// Deal with section parts... which are known :) !
				$object->setSectionParts($children);
				
				return $object;
			}
			else {
				$msg = "The mandatory attribute 'visible' is missing from element '" . $element->nodeName . "'.";
				throw new UnmarshallingException($msg, $element);
			}
		}
		else {
			$msg = "The mandatory attribute 'title' is missing from element '" . $element->nodeName . "'.";
			throw new UnmarshallingException($msg, $element);
		}
	}
	
	protected function marshallChildrenKnown(QtiComponent $component, array $elements) {
		$baseMarshaller = new SectionPartMarshaller();
		$element = $baseMarshaller->marshall($component);
		
		self::setDOMElementAttribute($element, 'title', $component->getTitle());
		self::setDOMElementAttribute($element, 'visible', $component->isVisible());
		self::setDOMElementAttribute($element, 'keepTogether', $component->mustKeepTogether());
		
		// Deal with selection element
		$selection = $component->getSelection();
		if (!empty($selection)) {
			$marshaller = $this->getMarshallerFactory()->createMarshaller($selection);
			$element->appendChild($marshaller->marshall($selection));
		}
		
		// Deal with ordering element.
		$ordering = $component->getOrdering();
		if (!empty($ordering)) {
			$marshaller = $this->getMarshallerFactory()->createMarshaller($ordering);
			$element->appendChild($marshaller->marshall($ordering));
		}
		
		// Deal with rubricBlock elements.
		foreach ($component->getRubricBlocks() as $rubricBlock) {
			$marshaller = $this->getMarshallerFactory()->createMarshaller($rubricBlock);
			$element->appendChild($marshaller->marshall($rubricBlock));
		}
		
		// And finally...
		// Deal with sectionPart elements that are actually known...
		foreach ($elements as $elt) {
			$element->appendChild($elt);
		}
		
		return $element;
	}
	
	protected function isElementFinal(DOMElement $element) {
		return $element->nodeName != 'assessmentSection';
	}
	
	protected function isComponentFinal(QtiComponent $component) {
		return !$component instanceof AssessmentSection;
	}
	
	protected function getChildrenElements(DOMElement $element) {
		if ($element->nodeName == 'assessmentSection') {
			$doc = $element->ownerDocument;
			$xpath = new DOMXPath($doc);
			$nodeList = $xpath->query('assessmentSection | assessmentSectionRef | assessmentItemRef', $element);
			
			if ($nodeList->length == 0) {
				$xpath->registerNamespace('qti', $doc->lookupNamespaceURI($doc->namespaceURI));
				$nodeList = $xpath->query('qti:assessmentSection | qti:assessmentSectionRef | qti:assessmentItemRef', $element);
			}
			
			$returnValue = array();
			
			for ($i = 0; $i < $nodeList->length; $i++) {
				$returnValue[] = $nodeList->item($i);
			}
			
			return $returnValue;
		}
		else {
			return array();
		}
	}
	
	protected function getChildrenComponents(QtiComponent $component) {
		if ($component instanceof AssessmentSection) {
			return $component->getSectionParts()->getArrayCopy();
		}
		else {
			return array();
		}
	}
	
	protected function createCollection(DOMElement $currentNode) {
		return new SectionPartCollection();
	}
	
	public function getExpectedQtiClassName() {
		return '';
	}
}