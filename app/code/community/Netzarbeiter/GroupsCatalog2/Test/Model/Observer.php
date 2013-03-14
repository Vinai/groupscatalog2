<?php

/**
 * @see Netzarbeiter_GroupsCatalog2_Model_Observer
 *
 * @loadSharedFixture global.yaml
 * @doNotIndexAll
 */
class Netzarbeiter_GroupsCatalog2_Test_Model_Observer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function catalogProductCollectionLoadBefore()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function catalogCategoryCollectionLoadBefore()
    {
        $this->markTestSkipped();
    }

    /**
     * @test
     */
    public function catalogCategoryLoadAfter()
    {
        $this->markTestSkipped();
    }

    /**
     * @test
     */
    public function catalogProductLoadAfter()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function catalogCategorySaveAfter()
    {
        $this->markTestSkipped();
    }

    /**
     * @test
     */
    public function customerGroupSaveAfter()
    {
        $this->markTestSkipped();
    }

    /**
     * @test
     */
    public function wishlistItemCollectionLoadBefore()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function catalogProductCollectionBeforeAddCountToCategories()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function salesQuoteLoadAfter()
    {
        $this->markTestIncomplete();
    }
}
