# Extended Export Framework (XPortX)

This is a toolkit for creating fixed custom exports, that can be used as search actions from 
any CiviCRM search - using these custom exports couldn't be easier.

However, *defining* such exports is a lot more difficult; this part of the extension aims at expert users 
that have a good understanding of the data structures underlying CiviCRM. If that doesn't scare 
you, have a look at the example specification files in ``xportx_configurations/examples``.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## How does it work?

An XPortX export is defined by three parts:
1. XPortX modules as building blocks to compile an SQL search query. You can find them in ``CRM/Xportx/Module``
2. XPortX export modules to render the search results. There currently are CSV, XLS, and PDF exporters. You can find them in ``CRM/Xportx/Exporter``
3. A ``json`` specification file defining the search, by specifying which modules are used in which order.

Once this specification file is placed in the config folder (either this extension's ``xportx_configurations`` folder
or the system's ``sites/default/files/civicrm/persist/xportx_configurations`` folder), 
the search is available to the user.

## How can I extend this?

First of all, if you have created a module that works on CiviCRM's core data structure, PRs for 
new modules are always welcome.

However, since the modules are simply referred to by class name in the ``json`` specification file, nothing will stop 
you from creating your own modules and add them to the search. We have created
a couple of extensions that ship their own XPortX modules to access their particular
data structures.