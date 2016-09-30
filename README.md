# Gravity Forms Engaging Networks Connector

## General

This Addon for GravityForms (Wordpress) can send form data to Engaging Networks.

It uses Engaging networks forms that have a limit of 100 entries per 5 minutes and form, so the plugin uses WPCron to send data every 5 minutes. Make sure to configure a cronjob if you have a low traffic page.

Due to this limitation, it can take a while for all entries to end up in the Engaging Networks Database on high traffic websites.

## Installation
- Install like a normal Worpress plugin
- Make sure WPCron is running (use [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/) )
- Set up a cronjob if you have a low traffic page. WPCron only runs if someone visits the website otherwise. 

## Form setup

- Go to your form, then Settings -> Engaging Networks
- Select the checkbox to enable Engaging Networks for that form
- Enter the ClientID and FormID from Engaging Networks (you need to setup a campaign and a form for it to get these)
- At the moment, field mapping has to be edited directly in the code. See Line 43 in class-gfenaddon.php. First level is the form ID, second level is "EN Field Name" => "Gravity Form field name"

-> There's also a page in the admin Dashboard Forms > Engaging Networks that lists all EN enabled forms and the latest status changes. If you are getting new submissions, the latest status changes should not be older than 5 minutes.

## Todo
- Translations
- Configuration of EN form fields (If you have additional form fields to Email, First Name, Last name you have to configure them directly in the code at the moment. Ideally you could enter the corresponding name in EN directly in the field setup of the form.
- Check the response for mismatching field names. Response code from EN is still 200, but it doesn't add the data to the database if that configuration is wrong. 

## License

GPL2
[http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html) http://www.gnu.org/licenses/gpl-2.0.html
