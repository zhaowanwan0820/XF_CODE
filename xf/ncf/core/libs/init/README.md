# Autoload机制说明

当前的`Autoload`类加载机制遵循[PSR-0][]标准，兼容`ThinkPHP`框架类加载机制，并且兼容任何符合[PSR-0][]标准的第三方组件。

## 使用方法

在项目的入口文件(例如`init.php`或`index.php`)顶端`require`或者`import` Autoload.php文件。

## ThinkPHP兼容

当常量`ADMIN_ROOT`已定义，并且`Think`类定义已存在的情况下，优先使用ThinkPHP的类加载机制。此时`Autoloader`会使用`Think::autoload`方法尝试查找并加载类，并且无论此次是否加载成功，执行过`Think::autoload`方法后，都会继续执行依据[PSR-0][]标准的类加载方法。

## 第三方组件兼容

第三方组件请放置在`/libs/vendors`目录，其命名空间不需要做任何修改，可以直接引用。具体的实现逻辑是，`Autoloader`首先以当前项目目录为根目录依据[PSR-0][]加载类文件(在此之前会检查是否需要兼容ThinkPHP)，如果加载失败，继续以`/libs/vendors`目录为根目录依据[PSR-0][]加载类文件。



[PSR-0]: http://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
