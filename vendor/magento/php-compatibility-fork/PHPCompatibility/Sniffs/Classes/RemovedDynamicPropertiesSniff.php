<?php
/**
 * This file has been modified by Adobe.
 * All modifications are Copyright 2023 Adobe.
 * All Rights Reserved.
 *
 * PHPCompatibility, an external standard for PHP_CodeSniffer.
 *
 * @package   PHPCompatibility
 * @copyright 2012-2023 PHPCompatibility Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCompatibility/PHPCompatibility
 */

namespace PHPCompatibility\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCompatibility\Helpers\ScannedCode;
use PHPCompatibility\Sniff;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Namespaces;
use PHPCSUtils\Utils\ObjectDeclarations;
use PHPCSUtils\Utils\Scopes;
use PHPCSUtils\Utils\UseStatements;

/**
 * Reports usage of dynamic properties in classes as deprecated.
 *
 * As of PHP 8.2, The creation of dynamic properties is deprecated, unless the class opts in by using
 * the #[\AllowDynamicProperties] attribute. stdClass allows dynamic properties.
 * Usage of the __get()/__set() magic methods is not affected by this change.
 *
 * PHP version 8.2
 *
 * @link https://www.php.net/manual/en/migration82.deprecated.php#migration82.deprecated.core.dynamic-properties
 */
class RemovedDynamicPropertiesSniff extends Sniff
{

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            \T_OBJECT_OPERATOR
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (ScannedCode::shouldRunOnOrAbove('8.2') === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Check if pointer is inside a class.
        $classPtr = Conditions::getLastCondition($phpcsFile, $stackPtr, [\T_CLASS]);
        if ($classPtr === false) {
            return;
        }

        // Disregard if class extends another class, uses traits, allows dynamic properties or has magic method __set().
        if (ObjectDeclarations::findExtendedClassName($phpcsFile, $classPtr) !== false
            || $this->isClassUsingTraits($phpcsFile, $classPtr)
            || $this->hasClassAllowDynamicProperties($phpcsFile, $classPtr)
            || $this->hasClassMagicSetter($phpcsFile, $classPtr)
        ) {
            return;
        }

        $propertyNamePtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        // Disregard if property name is not a string.
        if ($propertyNamePtr === false || $tokens[$propertyNamePtr]['code'] !== \T_STRING) {
            return;
        }

        $afterPropertyNamePtr = $phpcsFile->findNext(Tokens::$emptyTokens, ($propertyNamePtr + 1), null, true);
        // Disregard if is not assigned a value.
        if ($tokens[$afterPropertyNamePtr]['code'] !== \T_EQUAL) {
            return;
        }

        // Disregard if property is not accessed directly through $this.
        $thisPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($thisPtr === false
            || $tokens[$thisPtr]['code'] !== \T_VARIABLE
            || $tokens[$thisPtr]['content'] !== '$this'
        ) {
            return;
        }

        // Disregard if $this is preceded with object operator or double colon.
        $beforeThisPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $thisPtr - 1, null, true);
        if ($beforeThisPtr &&
            \in_array(
                $tokens[$beforeThisPtr]['code'],
                [\T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON]
            )
        ) {
            return;
        }

        $propertyName = $tokens[$propertyNamePtr]['content'];
        // Disregard if property is declared in the class.
        if (\in_array($propertyName, $this->getClassDeclaredProperties($phpcsFile, $classPtr), true)
            || \in_array($propertyName, $this->getClassPromotedProperties($phpcsFile, $classPtr), true)
        ) {
            return;
        }

        $className = ObjectDeclarations::getName($phpcsFile, $classPtr);
        $namespace = Namespaces::determineNamespace($phpcsFile, $classPtr);
        if ($namespace !== '') {
            $className = $namespace . '\\' . $className;
        }

        $error = 'Creation of dynamic property %s::$%s is deprecated since PHP 8.2';
        $data  = [$className, $propertyName];
        $phpcsFile->addWarning($error, $propertyNamePtr, 'Deprecated', $data);
    }

    /**
     * Get properties declared in the scope class
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return string[]
     */
    private function getClassDeclaredProperties(File $phpcsFile, $stackPtr)
    {
        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }
        $tokens     = $phpcsFile->getTokens();
        $properties = [];
        $next       = $stackPtr;
        while ($next = $this->findInClass($phpcsFile, $stackPtr, $next + 1, \T_VARIABLE)) {
            if (Scopes::isOOProperty($phpcsFile, $next) !== false) {
                $properties[] = \ltrim($tokens[$next]['content'], '$');
            }
        }
        Cache::set($phpcsFile, __METHOD__, $stackPtr, $properties);
        return $properties;
    }

    /**
     * Get properties declared in the constructor method
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return string[]
     */
    private function getClassPromotedProperties(File $phpcsFile, $stackPtr)
    {
        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }
        $properties = [];
        $next       = $stackPtr;
        while ($next = $this->findInClass($phpcsFile, $stackPtr, $next + 1, \T_FUNCTION)) {
            if (Scopes::isOOMethod($phpcsFile, $next)
                && \strtolower(FunctionDeclarations::getName($phpcsFile, $next)) === '__construct'
            ) {
                $params = FunctionDeclarations::getParameters($phpcsFile, $next);
                foreach ($params as $param) {
                    if (isset($param['property_visibility']) === true) {
                        $properties[] = \ltrim($param['name'], '$');
                    }
                }
                break;
            }
        }
        Cache::set($phpcsFile, __METHOD__, $stackPtr, $properties);
        return $properties;
    }

    /**
     * Check if class has AllowDynamicProperties attribute
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return bool
     */
    private function hasClassAllowDynamicProperties(File $phpcsFile, $stackPtr)
    {
        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }
        $found    = false;
        $tokens   = $phpcsFile->getTokens();
        $previous = $stackPtr;
        while ($previous = $phpcsFile->findPrevious(\T_ATTRIBUTE, $previous - 1)) {
            if (isset($tokens[$previous]['attribute_opener']) === false
                || isset($tokens[$previous]['attribute_closer']) === false
            ) {
                continue;
            }

            // Disregard if the attribute does not target current class
            $next = $phpcsFile->findNext(\T_CLASS, $tokens[$previous]['attribute_opener'] + 1);
            if ($next !== $stackPtr) {
                break;
            }

            $next = $tokens[$previous]['attribute_opener'];
            while ($next = $phpcsFile->findNext(\T_STRING, $next + 1, $tokens[$previous]['attribute_closer'])) {
                if ($tokens[$next]['content'] === 'AllowDynamicProperties') {
                    $beforeAttrPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($next - 1), null, true);
                    if ($tokens[$beforeAttrPtr]['code'] !== \T_NS_SEPARATOR) {
                        $found = true;
                        break 2;
                    }
                    $beforeNSPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($beforeAttrPtr - 1), null, true);
                    if (!\in_array($tokens[$beforeNSPtr]['code'], [\T_STRING, \T_NAMESPACE], true)) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }
        Cache::set($phpcsFile, __METHOD__, $stackPtr, $found);
        return $found;
    }


    /**
     * Check if class has magic method __set()
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return bool
     */
    private function hasClassMagicSetter(File $phpcsFile, $stackPtr)
    {
        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }
        $found = false;
        $next  = $stackPtr;
        while ($next = $this->findInClass($phpcsFile, $stackPtr, $next + 1, \T_FUNCTION)) {
            if (Scopes::isOOMethod($phpcsFile, $next)
                && \strtolower(FunctionDeclarations::getName($phpcsFile, $next)) === '__set'
            ) {
                $found = true;
                break;
            }
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $found);
        return $found;
    }

    /**
     * Check if class uses traits
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return bool
     */
    private function isClassUsingTraits(File $phpcsFile, $stackPtr)
    {
        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }
        $usesTraits = false;
        $next       = $stackPtr;
        while ($next = $this->findInClass($phpcsFile, $stackPtr, $next + 1, \T_USE)) {
            if (UseStatements::isTraitUse($phpcsFile, $next) === true) {
                $usesTraits = true;
                break;
            }
        }
        Cache::set($phpcsFile, __METHOD__, $stackPtr, $usesTraits);
        return $usesTraits;
    }

    /**
     * Find token in class scope
     *
     * @param File             $phpcsFile  The file being scanned.
     * @param int              $classPtr   The position of the class in the stack passed in $tokens.
     * @param int              $currentPtr The position of the current token in the stack passed in $tokens.
     * @param array|int|string $needle     The token to search for.
     *
     * @return int|false
     */
    private function findInClass(File $phpcsFile, $classPtr, $currentPtr, $needle)
    {
        $tokens          = $phpcsFile->getTokens();
        $classScopeEnd   = $tokens[$classPtr]['scope_closer'];
        $classScopeStart = $tokens[$classPtr]['scope_opener'];
        return $phpcsFile->findNext($needle, \max($currentPtr, $classScopeStart), $classScopeEnd);
    }
}
