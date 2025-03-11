<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Api\Data;

interface PaymentConfigButtonStylesInterface
{
    public const LAYOUT = 'layout';
    public const COLOR = 'color';
    public const SHAPE = 'shape';
    public const LABEL = 'label';
    public const TAGLINE = 'tagline';
    public const HEIGHT = 'height';
    public const DEFAULT_HEIGHT = 'use_default_height';

    /**
     * Get layout
     *
     * @return string
     */
    public function getLayout();

    /**
     * Set layout
     *
     * @param string $layout
     * @return $this
     */
    public function setLayout($layout);

    /**
     * Get color
     *
     * @return string
     */
    public function getColor();

    /**
     * Set color
     *
     * @param string $color
     * @return $this
     */
    public function setColor($color);

    /**
     * Get shape
     *
     * @return string
     */
    public function getShape();

    /**
     * Set shape
     *
     * @param string $shape
     * @return $this
     */
    public function setShape($shape);

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel();

    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label);

    /**
     * Get showTagline
     *
     * @return bool
     */
    public function hasTagline();

    /**
     * Set showTagline
     *
     * @param bool $showTagline
     * @return $this
     */
    public function setHasTagline($showTagline);

    /**
     * Get height
     *
     * @return int
     */
    public function getHeight();

    /**
     * Set height
     *
     * @param int $height
     * @return $this
     */
    public function setHeight($height);

    /**
     * Get height
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseDefaultHeight();

    /**
     * Set height
     *
     * @param bool $useDefaultHeight
     * @return $this
     */
    public function setUseDefaultHeight($useDefaultHeight);
}
