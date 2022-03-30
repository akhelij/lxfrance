<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShop\PrestaShop\Core\Product\Search;

class ProductSearchResult
{
    /**
     * @var array
     */
    private $products = [];
    /**
     * @var
     */
    private $totalProductsCount;
    /**
     * @var
     */
    private $facetCollection;
    /**
     * @var
     */
    private $encodedFacets;
    /**
     * @var array
     */
    private $availableSortOrders = [];
    /**
     * @var
     */
    private $currentSortOrder;

    /**
     * @param array $products
     *
     * @return $this
     */
    public function setProducts(array $products)
    {
        $this->products = $products;

        return $this;
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param $totalProductsCount
     *
     * @return $this
     */
    public function setTotalProductsCount($totalProductsCount)
    {
        $this->totalProductsCount = $totalProductsCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalProductsCount()
    {
        return $this->totalProductsCount;
    }

    /**
     * @param FacetCollection $facetCollection
     *
     * @return $this
     */
    public function setFacetCollection(FacetCollection $facetCollection)
    {
        $this->facetCollection = $facetCollection;

        return $this;
    }

    /**
     * @return FacetCollection
     */
    public function getFacetCollection()
    {
        return $this->facetCollection;
    }

    /**
     * @param $encodedFacets
     *
     * @return $this
     */
    public function setEncodedFacets($encodedFacets)
    {
        $this->encodedFacets = $encodedFacets;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEncodedFacets()
    {
        return $this->encodedFacets;
    }

    /**
     * @param SortOrder $sortOrder
     *
     * @return $this
     */
    public function addAvailableSortOrder(SortOrder $sortOrder)
    {
        $this->availableSortOrders[] = $sortOrder;

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableSortOrders()
    {
        return $this->availableSortOrders;
    }

    /**
     * @param array $sortOrders
     *
     * @return $this
     */
    public function setAvailableSortOrders(array $sortOrders)
    {
        $this->availableSortOrders = [];

        foreach ($sortOrders as $sortOrder) {
            $this->addAvailableSortOrder($sortOrder);
        }

        return $this;
    }

    /**
     * @param SortOrder $currentSortOrder
     *
     * @return $this
     */
    public function setCurrentSortOrder(SortOrder $currentSortOrder)
    {
        $this->currentSortOrder = $currentSortOrder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrentSortOrder()
    {
        return $this->currentSortOrder;
    }
}
