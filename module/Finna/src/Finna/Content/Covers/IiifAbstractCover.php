<?php

/**
 * IIIF cover loading shim.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2014-2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Content
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Content\Covers;

use \Vufind\Config\Config;

/**
 * IIIF cover loading shim.
 *
 * @category VuFind
 * @package  Content
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
abstract class IiifAbstractCover extends \VuFind\Content\AbstractCover
{
    /* Constructor
     *
     * @param Config   $config      VuFind configuration
     */
    public function __construct(Config &$config)
    {
        if ($config->Content->iiifProxyService) {
            // bypass generation of Cover/Show urls
            $this->directUrls = true;
            // the IIIF image server handles caching
            $this->cacheAllowed = false;
        }
    }

    /**
     * Get image URL for a particular API key and set of IDs (or false if invalid).
     *
     * @param string $key  API key
     * @param string $size Size of image to load (small/medium/large)
     * @param array  $ids  Associative array of identifiers (keys may include 'isbn'
     * pointing to an ISBN object, 'issn' pointing to a string and 'oclc' pointing
     * to an OCLC number string)
     *
     * @return string|bool
     */
    abstract public function getUrl($key, $size, $ids);
}
