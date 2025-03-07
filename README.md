# Laravel Inertia CRUD

This is a Laravel package that provides a simple and efficient way to create CRUD (Create, Read, Update, Delete) operations using Inertia.js, React and TypeScript.

## Features

- Seamless integration with Laravel and Inertia.js (React)
- Pre-built CRUD operations
- Easy to customize and extend

## Requirements

- PHP >= 8.1
- Laravel >= 10.0
- Inertia.js >= 2.0
- `@wedevs/tail-react` [package](https://github.com/wedevsOfficial/tail-react)
- `@heroicons/react` [package](https://heroicons.com/)

## Installation

1. Install the package via Composer:

    ```bash
    composer require tareq1988/laravel-inertia-crud
    ```

2. Install `shacdn` and `@heroicons/react` package:

See https://ui.shadcn.com/docs/installation for instructions for your framework.
Also, you need to install the following components: AlertDialog, Button, Input, 

    ```bash
   
    ```

See the [usage instruction](https://github.com/wedevsOfficial/tail-react) of `@wedevs/tail-react` package.

## Usage

1. Generate a new CRUD resource:

    ```bash
    php artisan inertia:make-resource Modelname
    ```

It'll create the controller, model and add a resource route in your `web.php` route. Please manually import the controller there.

2. Generate React page CRUD components:

    ```bash
    php artisan inertia:make-component Comment
    ```

You've to pass the model name here, it'll create the required components.


## Contributing

Contributions are welcome! 

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [Tareq Hasan](https://github.com/tareq1988)
