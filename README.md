Fuel Tracker
============

Track your fuel consumption.

[More details](http://www.pommepause.com/blog/2010/06/fuel-consumption-tracker/)

Installation
------------

- Import *fueltrack.sql* into your MySQL database:
    ```
    mysql -u root -p -e 'CREATE DATABASE fueltrack'
    mysql -u root -p fueltrack < fueltrack.sql
    ```

- Create a new user:  
    `mysql -u root -p -e "GRANT ALL ON fueltrack.* TO fuel_user@'localhost' identified by 'password_here'"`

- Change the options in config.inc.php to match your MySQL host, user & password.

- Insert new rows in the cars table; one per car you want to track.  
  The owner field should be an email address.

How to use
----------

Enter data using the web UI, or by sending an email to fueltrack@yourdomain.com, if you have your own MX server.

Emails
------

- If you have your own MX server, configure incoming emails to trigger the incoming_email.php script.
  I use Postfix, and configured it like this, in /etc/postfix/master.cf:
    ```
    fueltrack   unix -       n       n       -       1       pipe
      flags=Fq user=gb argv=/var/www/html/fueltrack/incoming_email.php $sender $recipient $mailbox
    ```

- configure *incoming_email.php* to recognize the car the incoming email is for (check near line 12).
- send an email with the keyword configured above in the subject, and in the body, enter 3 numbers: KPL (KM since last fill, price per liter, and number of liters)
