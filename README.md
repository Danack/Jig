Jig
===

Lightweight, fast, flexible PHP templating system.

Jig - "A device that holds a piece of machine work and guides the tools operating on it."

Or to put it another way, a jig allows you to work fast with sharp tools without cutting your fingers off.


[![Build Status](https://travis-ci.org/Danack/Jig.png)](https://travis-ci.org/Danack/Jig)


Features 
========

* Compiles to PHP class and uses APC/APCu class caching for super-duper performance.

* Dynamic inheritance.

* Super lightweight. Zero overhead when templates are already compiled when used with APC/OPCache



How it works
============

Please see the examples in the example directory.


TODO
====

* Replace the preg_match_all with a sane parser.

* Allow functions + block level elements to use a plugin system, to be extendable.

* Allow spoiler to be customisable.
