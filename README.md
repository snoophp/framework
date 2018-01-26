<p align="center"><img src="https://image.ibb.co/mHMgrm/snoophp.png" width="80"></p>

# SnooPHP

SnooPHP is a light PHP framework inspired by Laravel.

## Who is it for

SnooPHP is a small project, not meant to be used to develop complex applications (for that I suggest to use mature frameworks such as [Laravel](https://github.com/laravel/laravel) or [Symfony](https://github.com/symfony/symfony)), but it is perfect if you just want to build small projects - like a blog, a forum, a simple chat or a personal website - and you don't want all the stuff that other frameworks offer and you're probably never going to use.

## Features

- **Very simple routing** with the possibility to define multiple routers and keep your routes organized.
- **cURL interface** to perform simple HTTP requests server-side, without the hassle of the `curl` PHP library
- **Incredibly simple SQL database interface** inspired by Eloquent (only MySQL supported right now).
- **Easy-to-use websocket interface**
- **Ready-to-use git webhook script**
- Other stuff ...

## Q&A

**Is it fast? Is it faster than other frameworks?**

Don't know actually, never tried to compare. Why don't you try and let me know.

**Can I use the SQL interface with a DBMS other than MySQL?**

Right now only MySQL is supported (which is my DBMS of choice). It should not be difficult to support other (SQL) DBMS, take a look at the `Model` classes for more info.

**How can I install SnooPHP?**

SnooPHP and the SnooPHP framework are available on [Packagist](https://packagist.org) so you can simply use composer to install it:

```terminal
> composer require "snoophp\snoophp"
```

> Note, this repository contains just the framework

**Why are you developing SnooPHP when there are plenty good frameworks around?**

The truth is that my university project partner refused to use a framework and I ended up creating one.

**Who's better, Ronaldo or Messi?**

Messi

**Are we destined to die?**

Yes