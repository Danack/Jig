Jig
===

Lightweight, fast, flexible PHP templating system.

Jig - "A device that holds a piece of machine work and guides the tools operating on it."

Or to put it another way, a jig allows you to work fast with sharp tools without cutting your fingers off.


<table>
    <thead>
        <tr>
            <th>Build status</th>
            <th>Coverage</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <a href="https://travis-ci.org/Danack/Jig ">
                    <img src="https://travis-ci.org/Danack/Jig.png" alt="Latest Stable Version" style="max-width:100%;">
                </a>
            </td>
            <td>
                <a href="https://scrutinizer-ci.com/g/Danack/Jig/">
                    <img src="https://scrutinizer-ci.com/g/Danack/Jig/badges/coverage.png?s=70806917f23a4e848d7c7415ac71e25256ec9b58" alt="Coverage Status" style="max-width:100%;">
                </a>
            </td>
        </tr>
    </tbody>
</table>


Features 
========

* Compiles to PHP class for super-duper performance.

* Dependency injection of objects in the view, 

* Super lightweight. Zero overhead when templates are already compiled when used with APC/OPCache


How it works
============

Please see the examples in the example directory.


Running example
===============

php -S 0.0.0.0:8000 index.php



phpunit --coverage-html /tmp/JigCoverage