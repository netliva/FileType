netliva/filetype
============
This package adds file type to Symfony Form


Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require netliva/filetype
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require netliva/filetype
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Netliva\FileTypeBundle\NetlivaFileTypeBundle(),
        );

        // ...
    }

    // ...
}
```

Configurations
==============

Routes Definations
------------------

```yaml
netliva_file_route:
  resource: .
  type: netliva_file_route
```

Config Definations
------------------
Yükleyeceğiniz dosyaların nereye yükleneceğini ve 
indirme linkinin neresi olacağını aşağıdaki kodları ayarlar dosyanıza ekleyerek
düzenleyebilirsiniz. Bu ayarlar opsiyonel olup varsayılan değerler 
aşağıdaki gibidir. 

```yaml
# Symfony >= 4.0. Create a dedicated netliva_config.yaml in config/packages with:
# Symfony >= 3.3. Add in your app/config/config.yml:

netliva_file_type:
    file_config:
        upload_dir: public/netliva_uploads
        download_uri: /uploads
```
* **upload_dir:** dosyalarınızın proje ana klasöründen itibaren nereye yükleneceğini tanımlamanızı sağlar.
* **download_uri:**  yüklenen dosyalarınızın hangi klasör altından indirileceğini gösteren sanal bir dizindir. 
Dosyalarınız gerçekte bu klasör altında yeralmaz sadece dosyanın görünen url'ini belirler. 
Eğer burada belirteceğiniz klasör gerçekte proje ana dizininde bulunursa görüntülemede sıkıntı çıkabilir.


Basic Usage
===========
Öncelikle `json_array` veya `text` formatında veritabanı alanınızı oluşturun.
Ardından bu alan için formtype'a aşağıdaki gibi tanımlamanızı ekleyin.

 
 ```php
<?php
//...
public function buildForm (FormBuilderInterface $formBuilder, array $options)
{
	//...
	$formBuilder->add('images', NetlivaFileType::class, [ 'label' => 'Images', 'multiple' => false]);
	//...
}
//...
?>
 ```
 