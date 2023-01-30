# Plugins

Plugin is small part of a module that it can be hook into any modules where there is a hook available to use.  
There are 2 types of plugin. 1 is action and 2 is alter.

## Action hook
Action hook is for plugin to work in that action with or without any modification of data (if available). You can echo out or use data provided with the hook to have additional work.  
[See actions hook reference](plugins-actions-reference.md).

## Alter hook
Alter hook is for plugin to modify, remove the data provided with the hook. Your plugin must return this data to let other plugins or main application to work. In other word, alter work just like **filters hook** of WordPress.