<?php
namespace Mia3\Koseki;

/**
 */
class ClassParentParser
{
    /**
     * @var array
     */
    protected $classMap = [];



    public function addFile($file) {

    }

    public function addFileContent($fileContent) {
        preg_match_all('/class\s+([a-zA-z1-9]*)\s+{/s', $fileContent, $matches);
        if (!isset($matches[1])) {
            return;
        }

        foreach ($matches[1] as $className) {
            $this->classMap[$className] = [];
        }
    }

    public function getClasses() {
        return array_keys($this->classMap);
    }

    public function getClassesMap() {
        return $this->classMap;
    }
}
