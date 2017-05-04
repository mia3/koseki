<?php
namespace Mia3\Koseki\Tests\Unit;

use Mia3\Koseki\ClassParentParser;
use Mia3\Koseki\ClassReflectionParser;
use PHPUnit\Framework\TestCase;


/**
 */
class ClassReflectionParserTest extends TestCase
{


    /**
     */
    public function classMatchingProvider()
    {
        return [
            [
                '<?php
                namespace Foo\Bar;
                use Some\Class;
                use Some\Other\Class as Alias;
                use Hello\WorldInterface as AliasInterface;
                
                class Foo extends Class, Alias,\Some\Third\Thing implements \Some\Interface,AliasInterface {
                    public function bar() {
                    
                    }
                }
                
                use Hello\World2Interface as AliasInterface;
                
                namespace Foo {
                    class Guz extends Class, Alias,\Some\Third\Thing implements \Some\Interface,AliasInterface {
                        public function bar() {
                        
                        }
                    }
                }',
                [
                    'classes' => [
                        'Foo\Bar\Foo' => [
                            'className' => 'Foo\Bar\Foo',
                            'parentClasses' => [
                                'Some\Class',
                                'Some\Other\Class',
                                'Some\Third\Thing',
                            ],
                            'interfaces' => [
                                'Some\Interface',
                                'Hello\WorldInterface',
                            ],
                        ],
                        'Foo\Guz' => [
                            'className' => 'Foo\Guz',
                            'parentClasses' => [
                                'Some\Class',
                                'Some\Other\Class',
                                'Some\Third\Thing',
                            ],
                            'interfaces' => [
                                'Some\Interface',
                                'Hello\World2Interface',
                            ],
                        ],
                    ],
                    'uses' => [
                        'Class' => 'Some\Class',
                        'Alias' => 'Some\Other\Class',
                        'AliasInterface' => 'Hello\World2Interface',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider classMatchingProvider
     */
    public function classMatching($classContent, $expectedClassMap)
    {
        $parser = new ClassReflectionParser();
        $result = $parser->parse($classContent);

        $this->assertEquals($result, $expectedClassMap);
    }
}
