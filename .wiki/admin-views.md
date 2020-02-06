# Admin views

To make your module display in admin page with **RdbAdmin** module properly your admin views must have specific rules.

* Your views file must be in **Views** folder. For more information about folder structure please read on [folder structure page][1].
* Views file must have only elements that are in `<body>`...`</body>` element. The admin views is not full views page but it is partial page that will be put into the main layout.
* Views file must have **.php** as file extension.

Example:
From the [admin controllers document][2], the views file should be in **ModuleName/Views/Admin/Index/index_v.php**.
```
ModuleName/
    Views/
        Admin/
            Index/
                index_v.php
```

Example views file content.
```php
<h1><?php echo ($pageHtmlTitle ?? 'My module admin'); ?></h1>
<form id="modulename-mymanagement-form" class="rd-form">
    <input type="text" name="name" value="">
    <button type="submit">Save</button>
</form>
```

**RdbAdmin** module use **[RDTA][3]** (Rundiz Template for Admin) CSS and JS. Click on the [link][3] to learn more.

[1]: module-folder-structure-for-rdbadmin.md
[2]: admin-controllers.md
[3]: https://rundiz.com/?p=346