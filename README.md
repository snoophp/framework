<p align="center"><img src="https://image.ibb.co/mHMgrm/snoophp.png" width="80"></p>

# SnooPHP

![Packagist](https://img.shields.io/packagist/v/snoophp/framework.svg?style=for-the-badge)
![Packagist](https://img.shields.io/packagist/l/snoophp/framework.svg?style=for-the-badge)
![GitHub issues](https://img.shields.io/github/issues-raw/snoophp/framework.svg?style=for-the-badge)

SnooPHP is a light PHP framework inspired by [Laravel](https://laravel.com/).

## Who is it for

SnooPHP is a very simple and light framework, not suitable to develop complex and professional applications (for that I suggest to use mature frameworks such as [Laravel](https://github.com/laravel/laravel) or [Symfony](https://github.com/symfony/symfony)).

It is perfect if you just want to build small projects - like a blog, a forum, a simple chat or a personal website - and you don't want all the complexity that come with other frameworks.

> Simple and easy!

## Features

- **HTTP routing** with support for route parameters.
- **Model ORM** inspired by Laravel's Eloquent ORM (currently supports MySQL).
- **A libcurl interface** to perform HTTP requests server-side, without the hassle of using the libcurl library

Check the [wiki](https://github.com/snoophp/framework/wiki) for more informations.

## Q&A

**Is it fast? Is it faster than other frameworks?**

Don't know actually, never compared. Why don't you try and let me know.

**Can I use the SQL interface with a DBMS other than MySQL?**

Right now only MySQL is supported (which is my DBMS of choice). It should not be difficult to support other DBMSs, take a look at the `Model` classes for more info.

**How can I install SnooPHP?**

SnooPHP and the SnooPHP framework are available on [Packagist](https://packagist.org) so you can simply use composer to create a full project:

```shell
$ composer create-project "snoophp\snoophp" <project-dir>
```

**Why are you developing SnooPHP when there are plenty good frameworks around?**

The truth is that my university project partner refused to use a framework and I ended up creating one.

**Who's better, Ronaldo or Messi?**

Messi

**Are we destined to die?**

Yes