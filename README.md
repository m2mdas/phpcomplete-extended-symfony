phpcomplete-extended-symfony
============================

phpcomplete-extended-symfony is an extension of
[phpcomplete-extended](https://github.com/m2mdas/phpcomplete-extended)
plugin which provides autocomplete suggestions for a valid
[Symfony2](https://github.com/symfony/symfony) projects.
Completion types includes,

* Services.
* Parameters.
* View files.
* Entity repository menu entries.

Every menu entries are context aware so goto definition and open doc
functionality of `phpcomplete-extended` works as expected.

If [Unite.vim](https://github.com/Shougo/unite.vim) is installed the plugin
provides following sources,

* `symfony/bundles`           : Lists enabled bundle directories.
* `symfony/services`          : Lists public services. Default action is goto the service
  class
* `symfony/tags`              : Lists DIC tags. Default action is list the services defined
  for the tags
* `symfony/entities`          : Lists Doctrine entities.
* `symfony/routes_by_name`    : Lists routes by name
* `symfony/routes_by_pattern` : Lists routes by pattern

Installation
------------
Same as [phpcomplete-extended](https://github.com/m2mdas/phpcomplete-extended),
just add following lines in `.vimrc`

## NeoBundle

    NeoBundle 'm2mdas/phpcomplete-extended-symfony'

## Vundle

    Bundle 'm2mdas/phpcomplete-extended-symfony'

For pathogen clone [the repository](https://github.com/m2mdas/phpcomplete-extended-symfony.git) to your
`.vim/bundle` directory.


## Future plan

Future plan is to implement almost all features of [Symfony Eclipse
plugin](http://symfony.dubture.com/features).
