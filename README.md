User friendly URLs multilanguage tools package for yii2
=

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-packagist]

**This extension will work only with [multilanguage extension][link-multilang-extension]**

<!--
The extension is a package of tools to implement multilanguage in Yii2 project:
- Automatically redirects the user to the URL selected (automatically or manually) language and remembers the user
selected language
- Automatically collect all new translates into DB
- Has a widget to set a correct hreflang attributes
- Provides a CRUD actions for edit the list of languages and the interface translations
- Has a widget to create language selector (for adminlte theme)
-->

Installation
-

1.  The preferred way to install this extension is through [composer](http://getcomposer.org/download/), run:
    ```bash
    php composer.phar require --prefer-dist xz1mefx/yii2-ufu "~1.0"
    ```
    
1.  Previous action also install (if need) the [multilanguage extension][link-multilang-extension],
so if you did not set it earlier you will need to do it **in the first place**

1.  Execute migration:
    ```bash
    php yii migrate --migrationPath=@vendor/xz1mefx/yii2-ufu/migrations --interactive=0
    ```
    or you can create new migration and extend it, example:
    ```php
    require(Yii::getAlias('@vendor/xz1mefx/yii2-ufu/migrations/m161223_113345_ufu_init.php'));

    /**
    * Class m161221_135355_ufu_init
    */
    class m161221_135355_ufu_init extends m161223_113345_ufu_init
    {
    }
    ```

1.  Add ufu-component in main config file:
    ```php
    'ufu' => [
        'class' => \xz1mefx\ufu\components\UFU::className(),
    ],
    ```

1.  [*not necessary*] If you use [`iiifx-production/yii2-autocomplete-helper`][link-autocomplete-extension] you need to run:
    ```bash
    composer autocomplete
    ```


[ico-version]: https://img.shields.io/github/release/xz1mefx/yii2-ufu.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-downloads]: https://img.shields.io/packagist/dt/xz1mefx/yii2-ufu.svg

[link-packagist]: https://packagist.org/packages/xz1mefx/yii2-ufu
[link-multilang-extension]: https://github.com/xZ1mEFx/yii2-multilang
[link-adminlte-extension]: https://github.com/xZ1mEFx/yii2-adminlte
[link-autocomplete-extension]: https://github.com/iiifx-production/yii2-autocomplete-helper
