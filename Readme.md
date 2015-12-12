# Koseki ClassRegister

This library helps you to quickly and easily find classes that implement an interface
or extend from a base class. It uses the registered interfaces of composer to find/build
the registry. The registry itself is cached in the temporary directory returned by "sys_get_temp_dir()".
The cache entry will automatically be recached if the the composer autoload_file changes. But it can
does not pick up new/changes files, for development you should use the parameter "forceRecache". it's
still wicked fast.

**Usage cached**

```php
$implementations = ClassRegister::getImplementations('Acme\Package\MyCoolInterface');
```

**Usage with forceRecache**

```php
$implementations = ClassRegister::getImplementations('Acme\Package\MyCoolInterface', TRUE);
```

That's it :)


**Installation**

```
composer require mia3/koseki
```


**Ignoring incompatible/failing files**

There are 2 choices where you can specify files that should be ignored by the ClassRegistry.

- ```.koseki-ignore```
- ```.gitattributes```

The syntax is based on the common .gitignore format: https://git-scm.com/docs/gitignore#_pattern_format

The ClassRegister will automatically add a file to the root ```.koseki-ignore``` file if it
encounters a fatal error while including a class file.
