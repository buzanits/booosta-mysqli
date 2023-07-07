# Booosta Mysqli module - Tutorial

## Abstract

This tutorial covers the mysqli module of the Booosta PHP framework. If you are new to this framework, we strongly
recommend, that you first read the [general tutorial of Booosta](https://github.com/buzanits/booosta-installer/blob/master/tutorial/tutorial.md).

## Purpose

The purpose of this module is to provide access to a Mysql or MariaDB database for the framework. When it is active and 
properly configured, every class derived from the Booosta `Base` class has access to the configured database connection.

## Installation

If you follow the instructions in the [installer module](https://github.com/buzanits/booosta-installer), this module is 
automatically installed as a dependency. If for any reason this module is not yet installed, it can be loaded with

```
composer require booosta/mysqli
```

This also loads addtional dependent modules.

## Configuration

This module is configured in the main configuration file `local/config.incl.php`.


