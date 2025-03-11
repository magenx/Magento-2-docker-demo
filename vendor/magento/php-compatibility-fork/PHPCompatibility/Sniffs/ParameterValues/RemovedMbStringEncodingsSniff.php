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

namespace PHPCompatibility\Sniffs\ParameterValues;

use PHP_CodeSniffer\Files\File;
use PHPCompatibility\AbstractFunctionCallParameterSniff;
use PHPCompatibility\Helpers\ScannedCode;
use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\TextStrings;

/**
 * Reports usage of the QPrint, Base64, Uuencode, and HTML-ENTITIES 'text encodings' with certain MBString functions
 * as deprecated.
 *
 * As of PHP 8.2, usage of the QPrint, Base64, Uuencode, and HTML-ENTITIES 'text encodings' is deprecated
 * for all MBString functions. Unlike all the other text encodings supported by MBString,
 * these do not encode a sequence of Unicode codepoints, but rather a sequence of raw bytes.
 * It is not clear what the correct return values for most MBString functions should be when one of these non-encodings
 * is specified. Moreover, PHP has separate, built-in implementations of all of them;
 * for example, UUencoded data can be handled using convert_uuencode()/convert_uudecode().
 *
 * PHP version 8.2
 *
 * @link https://www.php.net/manual/en/migration82.deprecated.php#migration82.deprecated.mbstring
 */
class RemovedMbStringEncodingsSniff extends AbstractFunctionCallParameterSniff
{

    /**
     * List of MB functions and corresponding encoding argument to check for.
     *
     * @var array
     */
    protected $targetFunctions = [
        'mb_check_encoding' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_chr' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_convert_case' => [
            'position' => 3,
            'name' => 'encoding',
        ],
        'mb_convert_encoding' => [
            'position' => 2,
            'name' => 'to_encoding',
        ],
        'mb_convert_kana' => [
            'position' => 3,
            'name' => 'encoding',
        ],
        'mb_convert_variables' => [
            'position' => 1,
            'name' => 'to_encoding',
        ],
        'mb_decode_numericentity' => [
            'position' => 3,
            'name' => 'encoding',
        ],
        'mb_encode_numericentity' => [
            'position' => 3,
            'name' => 'encoding',
        ],
        'mb_encoding_aliases' => [
            'position' => 1,
            'name' => 'encoding',
        ],
        'mb_ord' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_scrub' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_str_split' => [
            'position' => 3,
            'name' => 'encoding',
        ],
        'mb_strcut' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strimwidth' => [
            'position' => 5,
            'name' => 'encoding',
        ],
        'mb_stripos' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_stristr' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strlen' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_strpos' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strrchr' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strrichr' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strripos' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strrpos' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strstr' => [
            'position' => 4,
            'name' => 'encoding',
        ],
        'mb_strtolower' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_strtoupper' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_strwidth' => [
            'position' => 2,
            'name' => 'encoding',
        ],
        'mb_substr_count' => [
            'position' => 3,
            'name' => 'encoding',
        ],
        'mb_substr' => [
            'position' => 4,
            'name' => 'encoding',
        ],
    ];

    /**
     * List of disallowed encodings and their alternatives.
     *
     * @var string[]
     */
    private $disallowedEncodings = [
        'qprint' => [
            'name' => 'QPrint',
            'alternative' => 'quoted_printable_encode/quoted_printable_decode',
        ],
        'quoted-printable' => [
            'name' => 'QPrint',
            'alternative' => 'quoted_printable_encode/quoted_printable_decode',
        ],
        'base64' => [
            'name' => 'Base64',
            'alternative' => 'base64_encode/base64_decode',
        ],
        'uuencode' => [
            'name' => 'Uuencode',
            'alternative' => 'convert_uuencode/convert_uudecode',
        ],
        'html-entities' => [
            'name' => 'HTML entities',
            'alternative' => 'htmlspecialchars, htmlentities, or mb_encode_numericentity/mb_decode_numericentity',
        ],
        'html' => [
            'name' => 'HTML entities',
            'alternative' => 'htmlspecialchars, htmlentities, or mb_encode_numericentity/mb_decode_numericentity',
        ],
    ];

    /**
     * Prevents this sniff from running if target PHP versions are lower than PHP 8.2.
     *
     * @return bool
     */
    protected function bowOutEarly()
    {
        return ScannedCode::shouldRunOnOrAbove('8.2') === false;
    }

    /**
     * Process the parameters of a matched function.
     *
     * This method has to be made concrete in child classes.
     *
     * @param File   $phpcsFile    The file being scanned.
     * @param int    $stackPtr     The position of the current token in the stack.
     * @param string $functionName The token content (function name) which was matched.
     * @param array  $parameters   Array with information about the parameters.
     *
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function processParameters(File $phpcsFile, $stackPtr, $functionName, $parameters)
    {
        $paramInfo   = $this->targetFunctions[\strtolower($functionName)];
        $targetParam = PassedParameters::getParameterFromStack($parameters, $paramInfo['position'], $paramInfo['name']);
        if ($targetParam !== false) {
            $encoding = \strtolower(TextStrings::stripQuotes($targetParam['clean']));
            if (isset($this->disallowedEncodings[$encoding])) {
                $phpcsFile->addWarning(
                    \sprintf(
                        'Handling %s via mbstring is deprecated since PHP 8.2; Use %s instead',
                        $this->disallowedEncodings[$encoding]['name'],
                        $this->disallowedEncodings[$encoding]['alternative']
                    ),
                    $stackPtr,
                    'Found',
                    [$targetParam['clean']]
                );
            }
        }
    }
}
