<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Constraints;

use Exception;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class JsonArrayValidator.
 */
class JsonArrayValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof JsonArray) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\JsonArray');
        }

        if (false !== $value && !empty($value)) {
            $jsonHelper = new JSONHelper();
            try {
                $jsonHelper->decodeArray($value);
            } catch (Exception $e) {
                if ($this->context instanceof ExecutionContextInterface) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value))
                        ->setCode(JsonArray::IS_JSON_ARRAY_ERROR)
                        ->addViolation();
                } else {
                    $this->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value))
                        ->setCode(JsonArray::IS_JSON_ARRAY_ERROR)
                        ->addViolation();
                }
            }
        }
    }
}
