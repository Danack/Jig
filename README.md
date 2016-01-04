# Jig - Lightweight, fast, testable PHP templating system.

Jig - "A device that holds a piece of machine work and guides the tools operating on it."

Or to put it another way, a jig allows you to work fast with sharp tools without cutting your fingers off.


<table>
    <thead>
        <tr>
            <th>Build status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <a href="https://travis-ci.org/Danack/Jig ">
                    <img src="https://travis-ci.org/Danack/Jig.png" alt="Latest Stable Version" style="max-width:100%;">
                </a>
            </td>
        </tr>
    </tbody>
</table>


## What

Jig is a template renderer that promotes the view layer to be a first class citizen in your application.

By using it, all of your templates are unit-testable as it does not use the 'service locator' pattern like most other templating systems do.

* Compiles to PHP class for super-duper performance.

* Uses real Dependency injection in templates, to allow unit testing of views.  

* Super lightweight. Zero overhead when templates are already compiled when used with APC/OPCache

* Simple but powerful plugin system.

## Documentation

Please run this to view the documentation:

```
git clone https://github.com/danack/TierJigDocs
cd TierJigDocs/
composer install
mkdir -p var/cache
php -S localhost:8000 -t public
```

