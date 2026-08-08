# MagicPro

![Laravel](https://img.shields.io/badge/Laravel-13-red?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-42b883?logo=vue.js&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

**A CMS that keeps Laravel intact.**

MagicPro adds CMS-style speed to Laravel without replacing Laravel itself.

Pages, routes, controllers, views, menus, structured data and settings can be managed from one admin panel — while your pages remain Blade, your controllers remain PHP, and the entire Laravel framework stays available.

## 🧭 Why MagicPro

A typical Laravel page may involve a route, controller, Blade template and configuration spread across several directories.

For large applications that separation makes sense. For ordinary website pages, however, controllers often contain almost no business logic: they fetch some data and pass it to a template.

MagicPro removes that unnecessary jumping between files.

A page is represented by one article. Its name defines the route, its body contains the Blade template, and an optional controller lives beside it. MagicPro generates the required application files automatically.

What MagicPro deliberately does **not** replace:

**Blade, Eloquent, middleware, validation, queues, events, packages or Laravel itself.**

Anything Laravel can do, a MagicPro page can do too.

## ✨ A page in MagicPro

Create an article named `price` and it becomes:

```text
/price
```

Its body is ordinary Blade:

```blade
@extends('magic::main')

<h1>{{ $Env['title'] }}</h1>

@foreach (MproHelper::getChildrenByName('topMenu') as $child)
    <a href="/{{ $child['name'] }}">{{ $child['title'] }}</a>
@endforeach
```

Need PHP logic before rendering? Add a controller to the same article.

Need a reusable fragment?

```blade
@include('magic::block')
```

or:

```blade
<x-magic::block />
```

No route file to edit and no controller or view file to create manually.

## 🤖 MCP server

MagicPro includes an MCP server that allows an AI agent to work directly with the project.

The agent can inspect the site structure, search articles, create and modify pages, work with feeds and read or write project files.

Instead of describing a page in a ticket and then implementing it manually, you can describe what you need and let the agent build it directly inside MagicPro.

The generated result is still normal Laravel code and can be edited manually at any time.

## 📰 Articles

Articles form the site hierarchy and simultaneously describe:

- pages;
- routes;
- menus;
- site structure.

Each article can define GET parameters, positional route parameters, UTM handling, POST support and access restrictions.

## 🗂 Feeds

Feeds provide structured application data without creating a new migration for every type of content.

Examples:

- products;
- prices;
- news;
- courses;
- directories;
- any other structured records.

Fields are defined from the admin panel and records are edited there as well.

On the Laravel side they behave like normal models:

```php
$items = Feed::where('code', 'products')->first()
    ?->items()
    ->where('__visible', true)
    ->orderByDesc('created_at')
    ->paginate(20);
```

```blade
{{ $item->title }}
{{ $item->price }}
{{ $item->expiration?->format('d.m.Y') }}
```

Feeds support relations to records from other feeds, images, visibility, search, sorting and pagination.

The **Builder** generates ready-to-use Laravel code for reading, creating, updating, deleting and listing records according to the actual schema of the selected feed.

## 🖼 Images

MagicPro can resize images dynamically:

```php
$img = MproHelper::imageReduceX($file, 800);
```

The derived image is generated on first request and cached afterwards. The original image is never modified.

Supported output formats:

**WebP, AVIF, JPEG and PNG.**

Image size limits, quality and error placeholders are configured centrally.

## ✉️ Mail

MagicPro provides its own API for immediate and scheduled email delivery.

Supported transports:

- SMTP;
- Amazon SES API.

Messages can be queued, inspected from the admin panel and logged together with delivery attempts.

Sending mail from application code is a single call:

```php
MproHelper::sendMail([
    'email' => $email,
    'subj'  => 'Order',
    'html'  => $html,
]);
```

## ⚡ Static generation

MagicPro can publish dynamic pages as static HTML.

When a static version exists, Nginx serves it directly. Laravel handles the request only when dynamic processing is required.

This makes it possible to keep Laravel flexibility while serving ordinary content pages with essentially static-site performance.

## 🛠 Admin panel

The administration interface manages:

- articles and site structure;
- feeds and records;
- users and permissions;
- application settings;
- mail queues;
- files;
- generated code.

The built-in editor uses ACE Editor and includes formatting tools for application files.

MagicPro also performs installation diagnostics and can create or repair required application resources.

## 🔐 Authentication

Built-in APIs provide:

- registration;
- login and logout;
- authorization;
- user management.

## 🔧 Also included

**MagicProBuilder**
Generates controllers, views, routes and application code.

**Helpers**
Mail, Telegram, reCAPTCHA, AES tokens, logs, text processing and image utilities.

**File manager**
Editing of project files directly from the administration interface.

**API architecture**
Administrative functionality is exposed through a unified command-style API.

## 🚀 Production use

MagicPro is not just a prototype.

The first production website powered by MagicPro was launched in December 2025. Since then it has been used for additional websites and together with the headless **MagicShop** catalog/store system.

Development is active and MagicPro currently targets **Laravel 13**.

## 🛠 Technologies

- **Backend:** Laravel 13
- **Frontend:** Vue 3, Bootstrap 5, PrimeVue
- **Editor:** ACE Editor
- **Images:** cwebp, libvips
- **Email:** SMTP, Amazon SES
- **Databases:** SQLite, MySQL, PostgreSQL
- **Server:** Ubuntu, Nginx

## ⚙️ Installation

Install Laravel — see [Cookbook.md](Cookbook.md#laravel).

```bash
composer require dixipro/magicpro

php artisan migrate

(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $(pwd) && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | sort -u | sudo crontab -u www-data -
```

The last command adds Laravel Scheduler to the `www-data` crontab using the current project directory automatically.

Then open:

```text
/a_dmin
```

MagicPro creates the required directories, publishes assets, links storage and reports anything still missing.

For development setup see [Cookbook.md](Cookbook.md#magicpro).

## 📜 Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT © dixiRu
