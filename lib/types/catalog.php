<?php
/**
 * Created by PhpStorm.
 * User: maxim
 * Date: 22.08.2017
 * Time: 23:25
 */

namespace Catalog\Generator\Types;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeLanguageTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Catalog\Generator\Steps;
use CIBlock;
use CIBlockProperty;
use Catalog\Generator\Exception as GeneratorException;

/**
 * Class Catalog
 * responsible for generating catalogs
 *
 * @package Catalog\Generator\Types
 */
class Catalog implements Generateable
{
    /**
     * @var bool
     */
    private $hasSku;

    /**
     * @var int
     */
    private $skuPerProduct;

    /**
     * Catalog constructor.
     */
    public function __construct()
    {
        $this->skuPerProduct = (int) Option::get("catalog.generator", "sku_count");
        // need sku?
        if($this->skuPerProduct > 0)
            $this->hasSku = true;
        else
            $this->hasSku = false;
    }

    /**
     * @inheritdoc
     */
    function generate()
    {
        $this->includeModules();
        $this->makeType();
        $this->cleanCache();
        $this->makeIblocks();
    }

    /**
     * Include modules
     */
    private function includeModules()
    {
        if(!Loader::includeModule("catalog"))
            throw new GeneratorException("Catalog module is not included!");
        if(!Loader::includeModule("iblock"))
            throw new GeneratorException("Iblock module is not included!");
    }

    /**
     * Create iblock types
     */
    private function makeType()
    {
        $id = Steps::IBLOCK_TYPE;
        $typesRes = TypeTable::getList(["filter" => ["ID" => $id]]);
        if(!$typeFields = $typesRes->fetch()){
            TypeTable::add(["ID" => $id]);
            TypeLanguageTable::add([
                "IBLOCK_TYPE_ID" => $id,
                "LANGUAGE_ID" => "en",
                "NAME" => "Catalog Generator",
                "SECTIONS_NAME" => "Section",
                "ELEMENTS_NAME" => "Element"
            ]);
        }
    }

    /**
     * Clean cache
     */
    private function cleanCache()
    {
        global $CACHE_MANAGER;
        $CACHE_MANAGER->CleanAll();
    }

    /**
     * Create iblock data
     *
     * @throws GeneratorException
     */
    private function makeIblocks()
    {
        $typeRes = TypeTable::getList([
            "filter" => ["=ID" => Steps::IBLOCK_TYPE],
            "select" => ["ID"]
        ]);
        if ($typeFields = $typeRes->fetch()){
            $catalogId = $this->makeCatalogIblock($typeFields["ID"]);
            $this->setUpCatalog($catalogId);
            if($this->hasSku === true){
                $skuId = $this->makeSkuIblock($typeFields["ID"]);
                $linkPropertyId = $this->linkSkuToCatlaog($skuId, $catalogId);
                $this->connectSkuToCatalog($skuId, $catalogId, $linkPropertyId);
            }
        } else {
            throw new GeneratorException("Тип инфоблока не создан!");
        }
    }

    /**
     * Creates iblock for catalog
     *
     * @param $iblockType
     * @return bool
     * @throws GeneratorException
     */
    private function makeCatalogIblock($iblockType)
    {
        $iblockRes = IblockTable::getList([
            "filter" => ["CODE" => "catalog_" . $iblockType],
            "select" => ["ID"],
            "limit" => 1
        ]);
        if($iblockRes->fetch())
            throw new GeneratorException("Каталог уже создан. Удалите каталог");
        $ib = new CIBlock;
        $arFields = [
            "ACTIVE" => "Y",
            "NAME" => "Catalog",
            "CODE" => "catalog_" . $iblockType,
            "LIST_PAGE_URL" => "/catalog/",
            "SECTION_PAGE_URL" => "/catalog/#SECTION_CODE_PATH#/",
            "DETAIL_PAGE_URL"  => "/catalog/#SECTION_CODE_PATH#/#ELEMENT_CODE#/",
            "IBLOCK_TYPE_ID" => $iblockType,
            "SITE_ID" => ["s1"], // @TODO check it
            "SORT" => 500
        ];
        $catalogId = $ib->Add($arFields);
        if($catalogId <= 0)
            throw new GeneratorException($ib->LAST_ERROR . " =>" .$iblockType . " error happened!");
        return $catalogId;
    }

    /**
     * Set up iblock as catalog
     *
     * @param $catalogId
     * @throws \Exception
     */
    private function setUpCatalog($catalogId)
    {
        $arFields = ['IBLOCK_ID' => $catalogId];
        CatalogIblockTable::add($arFields);
    }

    /**
     * Creates iblock for sku
     *
     * @param $iblockType
     * @return bool
     * @throws GeneratorException
     */
    private function makeSkuIblock($iblockType)
    {
        $iblockRes = IblockTable::getList([
            "filter" => ["CODE" => "sku_" . $iblockType],
            "select" => ["ID"],
            "limit" => 1
        ]);
        if($iblockRes->fetch())
            throw new GeneratorException("Каталог sku уже создан. Удалите каталог sku");
        $ib = new CIBlock;
        $arFields = [
            "ACTIVE" => "Y",
            "NAME" => "Sku",
            "CODE" => "sku_" . $iblockType,
            "IBLOCK_TYPE_ID" => $iblockType,
            "SITE_ID" => ["s1"], // @TODO get from existing site
            "SORT" => 500
        ];
        $skuId = $ib->Add($arFields);
        if($skuId <= 0)
            throw new GeneratorException($ib->LAST_ERROR . " error happened!");
        return $skuId;
    }

    /**
     * Add property link to catalog
     *
     * @param $skuId
     * @param $catalogId
     * @return bool
     */
    private function linkSkuToCatlaog($skuId, $catalogId)
    {
        $arFields = [
            "NAME" => "Catalog element id",
            "ACTIVE" => "Y",
            "SORT" => "500",
            "CODE" => "CML2_LINK",
            "PROPERTY_TYPE" => "E",
            "IBLOCK_ID" => $skuId,
            "LINK_IBLOCK_ID" => $catalogId
        ];
        $ibp = new CIBlockProperty;
        $linkPropertyId = $ibp->Add($arFields);
        return $linkPropertyId;
    }

    /**
     * Connect sku iblock to catalog
     *
     * @param $skuId
     * @param $catalogId
     * @param $linkPropertyId
     * @throws \Exception
     */
    private function connectSkuToCatalog($skuId, $catalogId, $linkPropertyId)
    {
        $arFields = [
            'IBLOCK_ID' => $skuId,
            'PRODUCT_IBLOCK_ID' => $catalogId,
            'SKU_PROPERTY_ID' => $linkPropertyId
        ];
        CatalogIblockTable::add($arFields);
    }

    /**
     * @return int
     */
    public function getCountToGenerate():int
    {
        return 1;
    }
}