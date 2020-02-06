## Translate the email messages.

You can translate the email message into your language by using this format.<br>
Original message: MyMessage.html<br>
Translated to Thai: MyMessage-th.html

Please note that "th" is refer from framework config/language.php > `languages` array key > and the key of the language you will be translate to.

## Add image to the email messages.

You can add image to the email message by using related path from "Modules/MyModuleName" where "MyModuleName" is the module you are using.<br>
Example:

```
<img src="assets/img/logo.png" alt="Logo">
```

The mailer class will look into "Modules/MyModuleName/assets/img/logo.png" folder.

## Styling the email message.

You can only use style tag in the html file. You cannot use link tag to external css file.<br>
The safest way to design is using inline stylesheet because mostly email provider restricted them.<br>
Example:

```
<html>
    <body>
        <p style="color: #555;">Hello world.</p>
    </body>
</html>
```