<?php

use PHPUnit\Framework\TestCase;
use Horizon\Translation\LanguageBucket;
use Horizon\Translation\Language;

class LanguageBucketTest extends TestCase
{

    /**
     * @var LanguageBucket
     */
    protected $bucket;

    public function setUp()
    {
        $this->bucket = new LanguageBucket();
        $this->bucket->add(new Language(dirname(__DIR__) . '/Resources/language.sit'));
    }

    public function testTranslate()
    {
        $this->assertEquals('Translated text!', $this->bucket->translate('Original text...'));
        $this->assertEquals('The test is working.', $this->bucket->translate('This has a {{ variable }}.', ['variable' => 'test']));
        $this->assertEquals('The test is working.', $this->bucket->translate('This has a {{ nested.variable }}.', ['nested' => ['variable' => 'test']]));
    }

    public function testAutoTranslate()
    {
        $originalText = '
            > This is a test and this is case-insensitive...
            > This ignores excess     spaces. using regular expressions.
            > Original text..., and finally:
            > This has a {{variable}}. And it will be translated.
        ';

        $expectedText = '
            > This is a test and It works...
            > It works.. using regular expressions.
            > Translated text!, and finally:
            > The test is working. And it will be translated.
        ';

        $this->assertEquals($expectedText, $this->bucket->autoTranslate($originalText, ['variable' => 'test', 'nested' => ['variable' => 'nest']]));
    }

}