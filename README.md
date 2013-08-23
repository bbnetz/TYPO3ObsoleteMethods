TYPO3ObsoleteMethods
====================

Based on TYPO3 Wiki checks if Extension or Instance will be available under TYPO3 >= 6.0

What it does
------------

Gets all possible obsolete classes from the [TYPO3 Wiki](http://wiki.typo3.org/TYPO3_6.0_Extension_Migration_Tips) and checks if the given list of extension contains those deprecated methods.

This gives no 100% chance that everything will run under >= 6.0 but it could be a first information.


How to use it
-------------

General Usage:

    $ ./obsolete_checker.php [--instancesPath="/var/www/domain.tld/htdocs"] [--extensionPath="/var/www/domain.tld/htdocs/typo3conf/ext/extensionName] [--ignoredFiles="a, b"] [--searchNonStatic]

### Params

##### instancesPath

Path to single TYPO3 instance or to multiple instance. If multiple instances use comma to separate.

##### extensionPath

Path to single TYPO3 extension or to multiple extensions. If multiple extensions use comma to separate.

##### ignoredFiles

List of files to ignore such as `changelog`, `.git` or `readme.md`

##### searchNonStatic

Currently this script only search for the static calls as searching the non static calls would be very hard. I tried to get the non static running, too, but this is under development right now and not yet running smooth. The worst could be, that you could get wrong informations about non static calls.
    

How to contribute
-----------------
The TYPO3 Community lives from your contribution!

You wrote a feature? - Start a pull request!

You found a bug? - Write an issue report!

You are in the need of a feature? - Write a feature request!

You have a problem with the usage? - Ask!
