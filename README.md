# Laravel ResumableJS

## Installation

This package can be used in Laravel 5.4 or higher.

You can install the package via composer:

``` bash
composer require sparemusic/resumable-js
```

In Laravel 5.5 the service provider will automatically be registered. In older versions of Laravel, add the service provider in the `config/app.php` file:

```php
'providers' => [
    // ...
    SpareMusic\ResumableJS\Providers\ResumableServiceProvider::class,
];
```

## Usage

### Resumable Request

To create a resumable request class, use the `make:resumable-request` Artisan CLI command:

```bash
php artisan make:resumable-request SomeUpload
```

The generated class will be placed in the `app/Http/ResumableRequests` directory. If this directory does not exist, it will be created when you run the `make:resumable-request` command.
Inside of this class there will be a setup method which will setup the upload.

```php
/**
 * Setup resumable instance
 */
public function setup()
{
    $chunkPath = Storage::disk('local')->path('chunks');
    $uploadPath = Storage::disk('local')->path('uploads');

    $this->setChunkPath($chunkPath)
        ->setUploadPath($uploadPath)
        ->setValidator(function (UploadedFile $file, ResumableParameters $parameters) {
            return true;
        });
}
```

The `$chunkPath` variable is the directory to store all the chunks of the uploaded file (the directory will be created if it doesn't already exist).  
The `$uploadPath` variable is the directory to store the completed upload (the directory will be created if it doesn't already exist).  
The validator validates the uploaded file. If it returns false, it cancels the upload. Otherwise, it continues running.

### Routing

To create the routes needed for a resumable upload, you can use the `Route::resumable()` method.

```php
Route::resumable("/upload", "UploadController@upload");
```

You could also do this manually.

```php
Route::get("/upload", "UploadController@upload");
Route::post("/upload", "UploadController@upload");
```

### Controller

All you need to do is type-hint the resumable request on your controller method. This will setup up the resumable upload:

```php
public function upload(SomeUpload $upload)
{
    $upload->process();

    if ($upload->isComplete()) {
        // File uploaded, do something with file
        // $upload->getFilename(true); filename with extension
        // $upload->getFilepath(); full filepath
    }

    return $upload->respond();
}
```

