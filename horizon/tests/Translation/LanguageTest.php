<?php

use PHPUnit\Framework\TestCase;
use Horizon\Translation\Language;

class LanguageTest extends TestCase
{

    public function testDeclaration()
    {
        $language = new Language(dirname(__DIR__) . '/Resources/language.sit');
        $this->assertNotNull($language->getParseTime());

        return $language;
    }

    /**
     * @depends testDeclaration
     */
    public function testGetHeaders(Language $language)
    {
        $this->assertEquals(true, $language->hasHeader('name'));
        $this->assertEquals('Language Name', $language->getHeader('name'));

        $this->assertEquals(true, $language->hasHeader('version'));
        $this->assertEquals('1.2.3', $language->getHeader('version'));

        $this->assertEquals(true, $language->hasHeader('author'));
        $this->assertEquals('The Horizon Project', $language->getHeader('author'));
        $this->assertEquals('1.2.3', $language->getHeader('version'));

        $this->assertEquals(false, $language->hasHeader('copyright'));
    }

    /**
     * @depends testDeclaration
     */
    public function testGetVariables(Language $language)
    {
        $this->assertEquals(true, $language->hasVariable('UpdateToken'));
        $this->assertEquals(true, $language->hasVariable('PurchaseEnabled'));
        $this->assertEquals(true, $language->hasVariable('UserId'));

        $this->assertEquals('5qN3vol4uXnGnVzZLqqr96ueX5Z2N2Za', $language->getVariable('UpdateToken'));
        $this->assertEquals(true, $language->getVariable('PurchaseEnabled'));
        $this->assertEquals(15669103.6, $language->getVariable('UserId'));
    }

    /**
     * @depends testDeclaration
     */
    public function testGetNamespaces(Language $language)
    {
        $this->assertEquals(true, $language->hasNamespace('horizon.index'));
        $this->assertInstanceOf('Horizon\Translation\Language\NamespaceDefinition', $language->getNamespace('horizon.index'));
    }

    /**
     * @depends testDeclaration
     */
    public function testGetTranslations(Language $language)
    {
        // Without flags
        $this->assertEquals('Translated text!', $language->translate('Original text...'));
        $this->assertNull($language->translate('original text...'));
        $this->assertNull($language->translate('original text... '));

        // With variables
        $this->assertEquals('The {{ variable }} is working.', $language->translate('This has a {{ variable }}.'));
        $this->assertEquals('The {{ variable }} is working.', $language->translate('This has a {{variable}}.'));

        // With a constraint
        $this->assertEquals('Translated text!', $language->translate('Original text...'), 'horizon.index');
        $this->assertEquals('Translated text!', $language->translate('Original text...'), ['horizon.index']);

        // With flags
        $this->assertEquals('It works.', $language->translate('this is case-insensitive.'));
        $this->assertNull($language->translate('this  is case-insensitive.'));
        $this->assertEquals('It works..', $language->translate('This ignores excess  spaces.'));
        $this->assertNull($language->translate('this ignores excess  spaces.'));
        $this->assertEquals('It works...', $language->translate('and this  is both in one.'));
    }

}