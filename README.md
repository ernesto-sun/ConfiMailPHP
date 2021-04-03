# Confi Mail PHP

A 'kind of safe' simple script for one purpose: 
Enable the public (humans only) to send any text-message to any email-address of your choice.

This is useful e.g. for contact forms. This project is meant to help you make your online contact form on your own website work in little time.

*Confi Mail PHP* is Open Source.

This kind of Microservice, SaaS, or API, combines 3 projects:

* [SecurImage Captcha](https://www.phpcaptcha.org/)
* [PHPMailer Mailing System](https://github.com/PHPMailer/PHPMailer) 
* [Confi Config PHP](https://github.com/ernesto-sun/ConfiConfigPHP) 

See a live demo here: https://exa.run/ConfiMailPHP/demo.htm


## Why this project?

Easy to configure, easy to unleash some of the power the PHPMailer
gives us. Well, E-Mailing is easy but not as easy as you think, specially
if you want some degree of privacy and security from hacker attacks. 

An emailing script is security-sensitive. Especially when used e.g. with a 
public contact form, that does not provide a safe AUTH. A hacker could
use a mail script to send massive amounts of emails somewhere, from 
your sever, you wouldn't even notice, ...

In short: This API wrapping PHPMailer shall provide an easy to set up and
kind-of-safe SaaS solution for anybody who wants to use good old light-speed
electronic messaging. 


## Who is it for?

Confi Mail PHP is made for developers, hackers, coders, website makers, etc. 


## Getting Started

### Prerequisites

* PHP (>=5) running on a web-server like Apache
* File-Access for PHP-scripts (read and write)  (So that ./config_script.php works.)
* mail() function working / well-setup PHP server
* And/or a well-setup SMTP-server 


### Installing

Copy the project files (as download-able here) to your desired location.

Set your settings at config_script_mail.php and config_script_sec.php.

Change the API-Key at the top of config_script.php itself.

Execute the config_script.php with your API key. 

For more info about Config Management see: [Confi Config PHP](https://github.com/ernesto-sun/ConfiConfigPHP) 

If all is set up well, you can try 'demo.htm' on your own server.

IMPORTANT: Inside the folder '_3p' must be the folders 'PHPMailer' and 'securimage'. Not all of the third-party code but some of it. The versions of these two projects, stored within this repository, are only for your reference to know, what files are necessary.


### Using Confi Mail PHP

Install ConfiMailPHP and know the URL. E.g.: https://yourserver.com/ConfiMailPHP

At your website of choice, make your own contact-form and add the following snippets.

Note: All the following code examples are put together at demo.htm  


#### The Captcha Image:

```

    <img class='captcha-image' src='_3p/securimage/securimage_show.php' alt='Captcha Image' />
    <a class='captcha-reload' href='#' onclick='this.closest("form").querySelector("img.captcha-image").src="_3p/securimage/securimage_show.php?"+Math.random();event.preventDefault();'>
    <span>Different image</span>
    </a>

```

Note: You might have to adapt the src-arguments to point to _3p/securimage/ wherever it is at your server. 


#### The Captcha Input:


```

    <input type='text' name='captcha-code' 
        size='6' minlength='6' maxlength='6' required='required'
        placeholder='Code' autocomplete="off" value =""/>

```

Note: It is required to have the name='captcha-code' and to have this input within a form-element. 


#### Your Form Submit

Your contact-form needs to run JavaScript code at the onsubmit-event. You need to write your own function to validate your own form and to combine the complete message into one string, just as you like it to be sent to your email.

This can look like this:

```

<form action="#" method='POST' onsubmit="SEND.call(this, event)">
...

```

and within your JavaScript:

```

function SEND(e)  
{
    e.preventDefault();

    var dform = this,
        subject = dform["subject"].value, 
        message = dform["message"].value, 
        data = {subject: subject, message: message},
        msg = JSON.stringify(data);
    ...

```


#### The Effective Sending

Having the URL to your ConfiMailPHP-installation, the form-data as one string, and the captcha-code, we can call the one function that does all the rest. 

```
    ...
    const captcha = dform['captcha-code'].value,
          url = "https://yourserver.com/ConfiMailPHP";


    ConfiMailPHP(url, captcha, msg).then((ok) => 
    {
        if(ok == 1)
        {
            console.log("MAIL sending done.");
        }
        else
        {
            console.log("Wrong captcha input!");  
        }

    }, (ex) =>
    {
        console.error("Mail sending failed. Check the system! Info: ", ex);
    });


```


#### The Function

And, of course, the function ConfiMailPHP must be defined somewhere.

```

function ConfiMailPHP(url, cap, msg) 
{ 
    return new Promise(function(ok, no)
    {
        var r = Math.round(Math.random() * 1998000000) + 2000000,
            ids = "captcha-script-sys",
            ds = document.getElementById(ids);    
        if(ds) ds.parentNode.removeChild(ds);
        document.body.appendChild(ds = document.createElement("script"));
        ds.id = ids;
        ds.onload = () => 
        { 
            if(typeof _MAIL_err_cap == "undefined") no("ConfiMailPHP system failure!");
            if(_MAIL_err_cap == 0)
            {
                _MAIL_send(msg, r).then((b) =>
                {
                    if(b == 1) ok(1);
                    else no("Function _MAIL_send() failed. Check the system!");
                }); 
            }
            else
            {
                if(_MAIL_err_cap == 1) ok(0);
                else no("Mail sending failed. Check the system!");
            }
        }
        ds.setAttribute("src", url + "/mail_script.php?r=" + r + "&c=" + cap);
    });
}   


```

Thats it! 


### Debugging

You can...

* Check out console.log at your Browser (the build-in 'Developer Tools') 
* Check out the log-files. (Part of your ConfiConfigPHP folders.)
* Set ``` $GLOBALS['debug'] = 1; ``` at the top of each PHP-File.


## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) if you want to be part of this project. 
You are welcome! Also with your harsh critics or a very strange idea.  


## Versioning

We use version numbers like V02, V03 for public releases. Internally we use
timestamps to identify versions. Such as '20201221' for 21. Dec, 2020. 


## TODO

* Let a web security expert say how much this multi-step-encryption combined with captcha even helps.
* Improve security
* Improve mailing features (HTML, Attachments, ...)
* Limit maximum message size.
* Play with CORS (access from different context)
* Testing, testing, testing, ...

## Authors

* **[Ernesto Sun](http://ernesto-sun.com)** 
* *You are welcome!*


## License

This project is licensed under the JSON License - see the [LICENSE.md](LICENSE.md) file for details

The JSON License is 'same same' to the MIT License; PLUS one ethical clause.

Note: The library PHPMailer uses LGPL: GNU Lesser General Public License v2.1
I am not happy with the Copyleft-Concept enforcing total nihilism upon others.
LGPL, at least, allows other licenses to works of other nature. This project is 
not a library, it is not the same type of software as PHPMailer. 'Confi Mail PHP' 
*uses* PHPMailer, it *links* to PHPMailer, it includes PHPMailer.


## Acknowledgments

Thank you!

Thank you so much to the maintainers of the projects PHPMailer and SecurImage. And thank you to the PHP-team and all the hosting providers that have a good PHP setup, nice SMTP etc. so that automated emailing works great for humanity. Light-speed a human right!   



