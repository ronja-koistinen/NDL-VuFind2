<?php

/**
 * IIIF manifest generator service
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Content
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Record\IIIF;

use Laminas\View\Helper\Url;
use Laminas\View\Helper\ServerUrl;
use \VuFind\RecordDriver\AbstractBase as RecordDriver;
use \VuFind\View\Helper\Root\RecordLinker;
use \Finna\View\Helper\Root\RecordImage;
use VuFind\View\Helper\Root\Record as RecordHelper;

/**
 * IIIF manifest generator service
 *
 * @category VuFind
 * @package  Content
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class IIIFManifestGenerator implements
    \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Constructor.
     *
     * @param Url $url                   URL helper
     * @param ServerUrl $serverUrl       Server URL helper
     * @param RecordLinker $recordLinker RecordLinker helper
     *                                   For getting the URL of the record action constructing
     *                                   this class
     * @param RecordHelper $recordHelper
     */
    public function __construct(
        protected Url $url,
        protected ServerUrl $serverUrl,
        protected RecordLinker $recordLinker,
        protected RecordHelper $recordHelper,
    ) {
    }

    /**
     * Generate IIIF presentation manifest (version 3)
     *
     * @param  RecordDriver     $driver
     * @return array|null
     */
    public function generate(RecordDriver $driver): array|null {
        $images = $driver->tryMethod('getAllImages');
        if(!$images) {
            return null;
        }

        $recordId = $driver->getUniqueID();
        $manifestId = ($this->serverUrl)(
            $this->recordLinker->getActionUrl(
                $driver, 'IIIFManifest',
                options: ['force_canonical' => true]
            )
        );

        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => $manifestId,
            'type' => 'Manifest',
            'thumbnail' => [],
            'metadata' => [],
            'items' => [],
        ];

        foreach ($images as $idx => &$image) {
            $canvasId = "$manifestId/$idx";
            $canvasItem = [
                'id' => $canvasId,
                'type' => 'Canvas',
                'items' => [],
            ];
            foreach (['large', 'medium', 'small'] as $size) {
                if (isset($image['urls'][$size])) {
                    $bodyId = ($this->url)(
                        'cover-show', [], ['force_canonical' => true]
                    ) . '?' . http_build_query([
                        'id' => $recordId,
                        'index' => $idx,
                        'size' => $size,
                        'source' => $driver->getSourceIdentifier()
                    ]);
                    $annotationPageItem = [
                        'id' => "$manifestId/$idx/$size",
                        'type' => 'AnnotationPage',
                        'items' => [
                            'id' => "$manifestId/$idx/$size/1",
                            'type' => 'Annotation',
                            'motivation' => 'painting',
                            'body' => [
                                'id' => $bodyId,
                                'type' => 'Image',
                                'format' => 'image/jpeg', //TODO kaiva tähän oikea arvo jostain
                                'height' => 1234,
                                'width' => 1234,
                            ],
                        ],
                        'target' => $canvasId,
                    ];
                    $canvasItem['items'][] = $annotationPageItem;
                    break; // only take the largest $size
                }
            }
            $manifest['items'][] = $canvasItem;
        }

        if(empty($manifest['items'])) {
            return null;
        } else {
            return $manifest;
        }
    }
}
