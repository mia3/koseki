<?php
namespace Mia3\Koseki;

/**
 * This BooleanParser helps to parse and evaluate boolean expressions.
 * it's basically a recursive decent parser that uses a tokenizing regex
 * to walk a given source while evaluating each step along the way.
 *
 * For a basic recursive decent exampel check out:
 * http://stackoverflow.com/questions/2093138/what-is-the-algorithm-for-parsing-expressions-in-infix-notation
 *
 * Parsingtree:
 *
 *  evaluate/compile: start the whole cycle
 *      parseOrToken: takes care of "||" parts
 *
 */
class ClassReflection
{

    /**
     * Regex to parse a source into tokens
     */
    const TOKEN_REGEX = '/
			(
                namespace\s+(?<namespace>([^;{]+))
            |
                use\s+(?<use>([^;]+));
            |
                (?<class>((class|abstract|interface)\s+[^{]+)){
			)
	/xs';

    /**
     * Evaluate a source to a boolean
     *
     * @param string $source to be parsed
     * @return boolean
     */
    public function parse($source)
    {
        preg_match_all(self::TOKEN_REGEX, $source, $matches);
        $meta = [
            'classes' => [],
            'uses' => []
        ];
        $currentNamespace = null;
        $currentClassName = null;
        foreach ($matches[1] as $index => $match) {
            if ($this->startsWith($match, 'namespace')) {
                $currentNamespace = trim($matches['namespace'][$index]);
            }

            if ($this->startsWith($match, 'use')) {
                $use = $matches['use'][$index];
                $parts = preg_split('/\sas\s/', $use);
                if (count($parts) > 1) {
                    $className = trim($parts[0]);
                    $alias = trim($parts[1]);
                    $meta['uses'][$alias] = $className;
                } else {
                    $parts = explode('\\', $use);
                    $meta['uses'][end($parts)] = $use;

                }
            }

            if ($this->startsWith($match, 'class')) {
                $classMatch = trim($matches['class'][$index]);
                $parts = preg_split('/\s+/', $classMatch);
                $mode = 'class';
                foreach ($parts as $part) {
                    if (in_array($part, ['class', 'abstract', 'interface', 'extends', 'implements'])) {
                        $mode = $part;
                        continue;
                    }

                    switch ($mode) {
                        case 'class':
                        case 'abstract':
                        case 'interface':
                            $currentClassName = $currentNamespace . '\\' . trim($part);
                            $meta['classes'][$currentClassName] = [
                                'className' => $currentClassName,
                                'parentClasses' => [],
                                'interfaces' => []
                            ];
                            break;

                        case 'extends':
                            $parentClasses = explode(',', trim($part, ', '));
                            foreach ($parentClasses as $parentClass) {
                                if (isset($meta['uses'][$parentClass])) {
                                    $parentClass = $meta['uses'][$parentClass];
                                }
                                $meta['classes'][$currentClassName]['parentClasses'][] = ltrim($parentClass, '\\');
                            }
                            break;

                        case 'implements':
                            $interfaces = explode(',', trim($part, ', {'));
                            foreach ($interfaces as $interface) {
                                if (isset($meta['uses'][$interface])) {
                                    $interface = $meta['uses'][$interface];
                                }
                                $meta['classes'][$currentClassName]['interfaces'][] = ltrim($interface, '\\');
                            }
                            break;
                    }
                }
            }
        }
        return $meta;
    }

    public function startsWith($haystack, $string)
    {
        return substr($haystack, 0, strlen($string)) == $string;
    }
}
