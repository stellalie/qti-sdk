<?php

namespace qtism\runtime\expressions\processing;

use qtism\data\expressions\operators\Gt;
use qtism\data\expressions\Expression;
use \InvalidArgumentException;

/**
 * The GtProcessor class aims at processing Gt operators.
 * 
 * From IMS QTI:
 * 
 * The gt operator takes two sub-expressions which must both have single cardinality
 * and have a numerical base-type. The result is a single boolean with a value of 
 * true if the first expression is numerically greater than the second and false if 
 * it is less than or equal to the second. If either sub-expression is NULL then 
 * the operator results in NULL.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
class GtProcessor extends OperatorProcessor {
	
	public function setExpression(Expression $expression) {
		if ($expression instanceof Gt) {
			parent::setExpression($expression);
		}
		else {
			$msg = "The GtProcessor class only processes Gt QTI Data Model objects.";
			throw new InvalidArgumentException($msg);
		}
	}
	
	/**
	 * Process the Gt operator.
	 * 
	 * @return boolean|null Whether the first sub-expression is numerically greather than the second or NULL if either sub-expression is NULL.
	 * @throws ExpressionProcessingException
	 */
	public function process() {
		$operands = $this->getOperands();
		
		if ($operands->containsNull() === true) {
			return null;
		}
		
		if ($operands->exclusivelySingle() === false) {
			$msg = "The Gt operator only accepts operands with a single cardinality.";
			throw new ExpressionProcessingException($msg, $this);
		}
		
		if ($operands->exclusivelyNumeric() === false) {
			$msg = "The Gt operator only accepts operands with a float or integer baseType.";
			throw new ExpressionProcessingException($msg, $this);
		}
		
		return $operands[0] > $operands[1];
	}
}