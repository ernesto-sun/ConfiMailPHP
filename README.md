# Confi Mail PHP

A 'kind of safe' simple script for one purpose: 
Enable the public (humans only) to send any text-message to any email-address of your choice.

This is useful e.g. for contact forms. This project is ment to help you make your online contact form on your own website work in little time.

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

An Emaling-Script is security-sensitive. Especially when used e.g. with a 
public contact form, that does not provide a safe AUTH. A hacker could
use the php-mail-script to send massive amounts of emails somewhere, from 
your sever, you woudn't even notice, ...

In short: This API wrapping PHPMailer shall provide an easy to set up and
kind-of-safe SaaS solution for anybody who wants to use good old lightspeed
messaging. 


## Who is it for?

Confi Config PHP is made for developers, hackers, coders, etc. 

## Getting Started

### Prerequisites

* PHP (>=5) running on a web-server like Apache
* File-Access for PHP-scripts (read and write)
* mail() function working / well-setup PHP server
* And/or a well-setup SMTP-server 


### Installing

Copy the project files (as download-able here) to your desired location.

Set your settings at config_script_mail.php and config_script_sec.php.

Change the API-Key at the top of config_script.php itself.

Execute the config-script. 

For more infos about Config Management see: [Confi Config PHP](https://github.com/ernesto-sun/ConfiConfigPHP) 


## How to Use 


## Built With

Confi Config PHP is a one-script solution including the library provided 
by PHPMailer. 


## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) if you want to be part of this project. 
You are welcome! Also with your harsh critics or a very strange idea.  


## Versioning

We use version numbers like V02, V03 for public releases. Internally we use
timestamps to identify versions. Such as '20201221' for 21. Dec, 2020. 


## Authors

* **[Ernesto Sun](http://ernesto-sun.com)** 
* *You are welcome!*


## License

This project is licensed under the JSON License - see the [LICENSE.md](LICENSE.md) file for details

The JSON License is same same to the MIT License, plus one ethical clause. I love it!

Note: The library PHPMailer uses LGPL: GNU Lesser General Public License v2.1
I am not happy with the Copyleft-Concept enforcing total nihilism uppon others.
LGPL, at least, allows other licenses to works of other nature. This project is 
not a libary, it is not the same type of software as PHPMailer. 'Confi Mail PHP' 
*uses* PHPMailer, it *links* to PHPMailer, it includes PHPMailer.


## Acknowledgments

Thank you!

* [PHPMailer at GitHub](https://github.com/PHPMailer/PHPMailer)



