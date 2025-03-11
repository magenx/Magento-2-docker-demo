<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

class ServicesConfigMessage
{
    /**#@+
     * Config message response enum
     */
    public const OK = "OK";
    public const NOT_CHANGED = "NOT_CHANGED";
    public const ERROR_SAVE_FAILED = "ERROR_SAVE_FAILED";
    public const ERROR_REQUEST_FAILED = "ERROR_REQUEST_FAILED";
    public const ERROR_KEYS_NOT_VALID = "ERROR_KEYS_NOT_VALID";
    public const ERROR_PRIVATE_KEY_SIGN_FAILED = "ERROR_PRIVATE_KEY_SIGN_FAILED";
    public const ERROR_IMS_CREDENTIALS_TYPE_NOT_SET = "ERROR_IMS_CREDENTIALS_TYPE_NOT_SET";
    public const ERROR_REQUEST_NOT_ALLOWED_DOMAIN = "ERROR_REQUEST_NOT_ALLOWED_DOMAIN";
    /**#@-*/
}
