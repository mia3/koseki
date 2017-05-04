<?php
namespace Mia3\Koseki\Tests\Unit;

use Mia3\Koseki\ClassParentParser;
use PHPUnit\Framework\TestCase;


/**
 */
class ClassParentParserTest extends TestCase
{


    /**
     */
    public function classMatchingProvider()
    {
        return [
            [
                '<?php
                class Foo {
                    public function bar() {
                    
                    }
                }',
                ['Foo' => []]
            ],
            [
            '<?php
                namespace Hello\World;
                class Foo {
                    public function bar() {
                    
                    }
                }',
                ['Hello\World\Foo' => []]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider classMatchingProvider
     */
    public function classMatching($classContent, $expectedClassMap)
    {
        $parser = new ClassParentParser();
        $parser->addFileContent($classContent);

        $this->assertEquals($parser->getClassesMap(), $expectedClassMap);
    }
}
