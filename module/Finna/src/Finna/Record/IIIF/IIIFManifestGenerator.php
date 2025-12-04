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
use \VuFind\RecordDriver\AbstractBase as RecordDriver;
use \VuFind\View\Helper\Root\RecordLinker;
use \Finna\View\Helper\Root\RecordImage;

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
     * URL helper
     *
     * @var Url
     */
    protected $urlHelper;

    /**
     * RecordImage helper
     *
     * This is needed for the getImageAsCoverLinks() method
     *
     * @var RecordImage
     */
    protected $recordImageHelper;

    /**
     * RecordLinker helper
     *
     * For getting the URL of the record action constructing this class
     * @var RecordLinker
     */
    protected $recordLinker;

    /**
     * Constructor.
     *
     * @param Url $url
     */
    public function __construct(
        Url $url,
        RecordImage $recordImage,
        RecordLinker $recordLinker,
    ) {
        $this->urlHelper = $url;
        $this->recordImageHelper = $recordImage;
        $this->recordLinker = $recordLinker;
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
        $manifestId = $this->recordLinker->getActionUrl($driver, 'IIIFManifest');

        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => $manifestId,
            'type' => 'Manifest',
            'thumbnail' => [],
            'metadata' => [],
            'items' => [],
        ];

        foreach ($images as $idx => &$image) {
            $canvasItem = [
                'id' => 'https://jotainjotain', //TODO
                'type' => 'Canvas',
                'items' => [],
            ];
            if ($img = $image['urls']['large']
                       ?? $image['urls']['medium']
                       ?? null) {
                $coverLinks = $this->recordImageHelper->getImageAsCoverLinks($idx);
                $annotationPageItem = [
                    'id' => 'https://jotainjotain2', //TODO
                    'type' => 'AnnotationPage',
                    'items' => [
                        'id' => 'https://jotainjotain3', //TODO
                        'type' => 'Annotation',
                        'motivation' => 'painting',
                        'body' => [
                            'id' => 'https://jotainjotain4', //TODO
                            'type' => 'Image',
                            'format' => 'image/jpeg', //TODO kaiva tähän oikea arvo jostain
                            'height' => 1234,
                            'width' => 1234,
                        ],
                    ],
                    'target' => 'https://jotainjotain',
                ];
                $canvasItem['items'][] = $annotationPageItem;
                break; // only take the largest $size
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
