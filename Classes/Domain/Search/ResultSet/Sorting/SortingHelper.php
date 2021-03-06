<?php

namespace ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2018 Timo Hund <timo.hund@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class SortingHelper
 */
class SortingHelper {

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Constructor
     *
     * @param array $sortingConfiguration Raw configuration from plugin.tx_solr.search.sorting.options
     */
    public function __construct(array $sortingConfiguration)
    {
        $this->configuration = $sortingConfiguration;
    }

    /**
     * Gets a list of configured sorting fields.
     *
     * @deprecated Since 8.1 will be removed in EXT:solr 9.0
     * @return array Array of (resolved) sorting field names.
     */
    public function getSortFields()
    {
        trigger_error('SortingHelper::getSortFields is deprecated please use the sorting of the SearchResultSet now or retrieve it on your own.', E_USER_DEPRECATED);
        $sortFields = [];
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        foreach ($this->configuration as $optionName => $optionConfiguration) {
            $fieldName = $contentObject->stdWrap(
                $optionConfiguration['field'],
                $optionConfiguration['field.']
            );

            $sortFields[] = $fieldName;
        }

        return $sortFields;
    }

    /**
     * Takes the tx_solr[sort] URL parameter containing the option names and
     * directions to sort by and resolves it to the actual sort fields and
     * directions as configured through TypoScript. Makes sure that only
     * configured sorting options get applied to the query.
     *
     * @param string $urlParameters tx_solr[sort] URL parameter.
     * @return string The actual index field configured to sort by for the given sort option name
     * @throws \InvalidArgumentException if the given sort option is not configured
     */
    public function getSortFieldFromUrlParameter($urlParameters)
    {
        $sortFields = [];
        $sortParameters = GeneralUtility::trimExplode(',', $urlParameters);

        $removeTsKeyDot = function($sortingKey) { return trim($sortingKey, '.'); };
        $configuredSortingName = array_map($removeTsKeyDot, array_keys($this->configuration));

        foreach ($sortParameters as $sortParameter) {
            list($sortOption, $sortDirection) = explode(' ', $sortParameter);

            if (!in_array($sortOption, $configuredSortingName)) {
                throw new \InvalidArgumentException('No sorting configuration found for option name ' . $sortOption, 1316187644);
            }

            $sortField = $this->configuration[$sortOption . '.']['field'];
            $sortFields[] = $sortField . ' ' . $sortDirection;
        }

        return implode(', ', $sortFields);
    }

    /**
     * Gets the sorting options with resolved field names in case stdWrap was
     * used to define them.
     *
     * @deprecated Since 8.1 will be removed in EXT:solr 9.0
     * @return array The sorting options with resolved field names.
     */
    public function getSortOptions()
    {
        trigger_error('SortingHelper::getSortOptions is deprecated please use the sorting of the SearchResultSet now or retrieve it on your own.', E_USER_DEPRECATED);

        $sortOptions = [];
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        foreach ($this->configuration as $optionName => $optionConfiguration) {
            $optionField = $contentObject->stdWrap(
                $optionConfiguration['field'],
                $optionConfiguration['field.']
            );

            $optionLabel = $contentObject->stdWrap(
                $optionConfiguration['label'],
                $optionConfiguration['label.']
            );

            $optionName = substr($optionName, 0, -1);
            $sortOptions[$optionName] = [
                'field' => $optionField,
                'label' => $optionLabel,
                'defaultOrder' => $optionConfiguration['defaultOrder']
            ];
        }

        return $sortOptions;
    }
}
