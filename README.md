# CakePHP-GoogleCalendarPlugin
This is a short CakePHP plugin for Google Calendar, still in development. For now, it can:

* create an event on Google Calendar
* get the Google User's calendar list

# Install

## Manual install

Download the .zip file and extract it in your app/Plugin folder, renaming the master folder as "GoogleCalendar"

## Load the component

Load GoogleCalendar in your Components in the `AppController.php`:

```
public $components = array(
    ...,
    'GoogleCalendar.GoogleCalendar'
);
```

and this will load the component located in the plugin.

## Usage

### Create an event

If you want to create an event on Google Calendar, all you have to do is to use the function `insertEvent()`, passing two arguments: the array of the data of the event, and the Google id (Google mail) of the user for which create the event:

```
insertEvent($googleAccountID, $event)
```
