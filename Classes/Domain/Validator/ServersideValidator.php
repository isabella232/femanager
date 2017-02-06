<?php
namespace In2code\Femanager\Domain\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ServersideValidator
 */
class ServersideValidator extends AbstractValidator
{

    /**
     * Validation of given Params
     *
     * @param $user
     * @return bool
     */
    public function isValid($user)
    {
        $this->init();

        if ($this->validationSettings['_enable']['server'] !== '1') {
            return $this->isValid;
        }

        foreach ($this->validationSettings as $field => $validations) {
            if (is_object($user) && method_exists($user, 'get' . ucfirst($field))) {

                $value = $user->{'get' . ucfirst($field)}();
                if (is_object($value)) {
                    if (method_exists($value, 'getUid')) {
                        $value = $value->getUid();
                    }
                    if (method_exists($value, 'getFirst')) {
                        $value = $value->getFirst()->getUid();
                    }
                    if (method_exists($value, 'current')) {
                        $current = $value->current();
                        if (method_exists($current, 'getUid')) {
                            $value = $current->getUid();
                        }
                    }
                }

                foreach ($validations as $validation => $validationSetting) {
                    switch ($validation) {

                        case 'required':
                            if ($validationSetting === '1' && !$this->validateRequired($value)) {
                                $this->addErrorFor($field, 'validationErrorRequired');
                                $this->isValid = false;
                            }
                            break;

                        case 'email':
                            if (!empty($value) && $validationSetting === '1' && !$this->validateEmail($value)) {
                                $this->addErrorFor($field, 'validationErrorEmail');
                                $this->isValid = false;
                            }
                            break;

                        case 'min':
                            if (!empty($value) && !$this->validateMin($value, $validationSetting)) {
                                $this->addErrorFor($field, 'validationErrorMin');
                                $this->isValid = false;
                            }
                            break;

                        case 'max':
                            if (!empty($value) && !$this->validateMax($value, $validationSetting)) {
                                $this->addErrorFor($field, 'validationErrorMax');
                                $this->isValid = false;
                            }
                            break;

                        case 'intOnly':
                            if (!empty($value) && $validationSetting === '1' && !$this->validateInt($value)) {
                                $this->addErrorFor($field, 'validationErrorInt');
                                $this->isValid = false;
                            }
                            break;

                        case 'lettersOnly':
                            if (!empty($value) && $validationSetting === '1' && !$this->validateLetters($value)) {
                                $this->addErrorFor($field, 'validationErrorLetters');
                                $this->isValid = false;
                            }
                            break;

                        case 'uniqueInPage':
                            if (
                                !empty($value) &&
                                $validationSetting === '1' &&
                                !$this->validateUniquePage($value, $field, $user)
                            ) {
                                $this->addErrorFor($field, 'validationErrorUniquePage');
                                $this->isValid = false;
                            }
                            break;

                        case 'uniqueInDb':
                            if (
                                !empty($value) &&
                                $validationSetting === '1' &&
                                !$this->validateUniqueDb($value, $field, $user)
                            ) {
                                $this->addErrorFor($field, 'validationErrorUniqueDb');
                                $this->isValid = false;
                            }
                            break;

                        case 'mustInclude':
                            if (!empty($value) && !$this->validateMustInclude($value, $validationSetting)) {
                                $this->addErrorFor($field, 'validationErrorMustInclude');
                                $this->isValid = false;
                            }
                            break;

                        case 'mustNotInclude':
                            if (!empty($value) && !$this->validateMustNotInclude($value, $validationSetting)) {
                                $this->addErrorFor($field, 'validationErrorMustNotInclude');
                                $this->isValid = false;
                            }
                            break;

                        case 'inList':
                            if (!$this->validateInList($value, $validationSetting)) {
                                $this->addErrorFor($field, 'validationErrorInList');
                                $this->isValid = false;
                            }
                            break;

                        case 'sameAs':
                            if (method_exists($user, 'get' . ucfirst($validationSetting))) {
                                $valueToCompare = $user->{'get' . ucfirst($validationSetting)}();
                                if (!$this->validateSameAs($value, $valueToCompare)) {
                                    $this->addErrorFor($field, 'validationErrorSameAs');
                                    $this->isValid = false;
                                }
                            }
                            break;

                        case 'date':
                            // Nothing to do. ServersideValidator runs after converter
                            // If dateTimeConverter exception $value is the old DateTime Object => True
                            // If dateTimeConverter runs well we have an DateTime Object => True
                            break;

                        default:
                            // e.g. search for method validateCustom()
                            if (method_exists($this, 'validate' . ucfirst($validation))) {
                                if (!$this->{'validate' . ucfirst($validation)}($value, $validationSetting)) {
                                    $this->addErrorFor($field, 'validationError' . ucfirst($validation));
                                    $this->isValid = false;
                                }
                            }
                    }
                }
            }
        }

        return $this->isValid;
    }

    /**
     * Adds a property error to validation result object.
     *
     * @param string $property      User property
     * @param string $message       Error message
     * @param int    $code          Error code
     * @param array  $arguments     Arguments to be replaced in message
     * @param string $title         Title of the error
     */
    protected function addErrorFor($property, $message, $code = 0, array $arguments = [], $title = '')
    {
        $code = (is_numeric($code) && intval($code) > 0 ) ? $code : $property;

        /** @var \TYPO3\CMS\Extbase\Validation\Error $error */
        $error = GeneralUtility::makeInstance(
            'TYPO3\CMS\Extbase\Validation\Error',
            $message,
            $code,
            $arguments,
            $title
        );
        $this->result->forProperty($property)->addError($error);
    }
}
