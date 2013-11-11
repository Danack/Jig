Jig
===

Lightweight, fast, flexible PHP templating system.

Jig - "A device that holds a piece of machine work and guides the tools operating on it."

Or to put it another way, a jig allows you to work fast with sharp tools without cutting your fingers off.


[![Build Status](https://travis-ci.org/Danack/Jig.png)](https://travis-ci.org/Danack/Jig)

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Danack/Jig/badges/quality-score.png?s=bac5cc7d57c0433c1213290257721948818a78a2)](https://scrutinizer-ci.com/g/Danack/Jig/)

[![Code Coverage](https://scrutinizer-ci.com/g/Danack/Jig/badges/coverage.png?s=70806917f23a4e848d7c7415ac71e25256ec9b58)](https://scrutinizer-ci.com/g/Danack/Jig/)


Features 
========

* Compiles to PHP class and uses APC/APCu class caching for super-duper performance.

* Dynamic inheritance.

* Super lightweight. Zero overhead when templates are already compiled when used with APC/OPCache



How it works
============

Please see the examples in the example directory.


Running example
===============

php -S 0.0.0.0:8000 index.php


TODO
====

* Replace the preg_match_all with a sane parser.

* Allow functions + block level elements to use a plugin system, to be extendable.

* Allow spoiler to be customisable.

* Include example, extend example



public function render(array $context)
    {
        $level = ob_get_level();
        ob_start();
        try {
            $this->display($context);
        } catch (Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }

phpunit --coverage-html /tmp/JigCoverage