# CakePHP-GoogleCalendarPlugin
This is a short CakePHP plugin for Google Calendar, still in development. For now, it can:

* create an event on Google Calendar
* get the Google User's calendar list

# Install

## Manual install

Download the .zip file and extract it in your app/Plugin folder, renaming the master folder as "GoogleCalendar"

## Clone with Git

Go to `app/Plugin` folder and give the command

```bash
git clone https://github.com/falco442/CakePHP-GoogleCalendarPlugin.git GoogleCalendar
```

## With Composer

After installing CakePHP 2.x with Composer (follow [this instructions](https://book.cakephp.org/2.0/en/installation/advanced-installation.html)) you can install the plugin with the command

```
composer require falco442/cake-google-calendar
```

# Usage

## Load the component

Load GoogleCalendar in your Components in the `AppController.php`:

```php
public $components = array(
    ...,
    'GoogleCalendar.GoogleCalendar'=>array(
       'id'=>'your-Google-App-id',
        'secret'=>'your-Google-App-secret'        
    )
);
```

and this will load the component located in the plugin.

### Create an event

If you want to create an event on Google Calendar, all you have to do is to use the function `insertEvent()`, passing two arguments: the array of the data of the event, and the Google id (Google mail) of the user for which create the event:

```php
$this->GoogleCalendar->insertEvent($googleAccountID, $event);
```

where the event has to be an array of the form

```php
$event = array(
    'start'=>'2014-12-31 22:00:00',
    'end'=>'2015-01-01 03:30:00',
    'summary'=>'New Years Eve Dinner',
    'description'=>'We will have a great party!!',
    'location'=>'Via Nazionale 6 Roma'
);
```

The function returns the `id` of the event returned by Google.

### Get the User's calendar list

To get a user's calendar list, simply use the function `getCalendarList()`, that accepts a unique parameter: the Google Account ID of the user of which we want to retrieve the calendar list:

```php
$this->GoogleCalendar->getCalendarList($googleAccountID);

```

the function returns an array which contains the calendar list for the `$googleAccountID`.

## Extra

### Map Event fields

If you don't want to map everytime the fields of the event (maybe because your events' table fields are not named as the Google Calendar events' fields are), you can pass the mapping parameters in `AppController.php`:

```php
public $components = array(
    ...,
    'GoogleCalendar.GoogleCalendar'=>array(
       'id'=>'your-Google-App-id',
        'secret'=>'your-Google-App-secret',
        'eventMap'=>array(
            'summary'=>'event_title',
            'description'=>'event_description'
            'location'=>'event_address',
            'start'=>'start_datetime',
            'end'=>'end_datetime'
        )
    )
);
```

### Change Time Zone

Note that events' start and date accepts only a `datetime` format, and post to Google Calendar a datetime in the format RFC3339, with a timezone set by default.

The default timezone is `Europe/Rome`, but you can change it from the `AppController.php`:

```php
public $components = array(
    ...,
    'GoogleCalendar.GoogleCalendar'=>array(
        'id'=>'your-Google-App-id',
        'secret'=>'your-Google-App-secret',
        'timeZone'=>'your-Time-Zone-code'
    )
);
```

You can find [here](http://php.net/manual/en/timezones.php) the list of supported timezones by PHP.
