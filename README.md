# LIFX for WordPress

This plugin provides a way to control your LIFX smart lights using WordPress.

Currently this is done using the command line and [WP-CLI](https://wp-cli.org/).

## Installation

1. Download the plugin zip from Github.
1. Upload the zip through the 'Plugins' menu in WordPress
1. Activate the plugin.

## Usage

You can type `wp help lifx` to see all the commands. Here are some common ones:

* `wp lifx get_token` - This will print your LIFX personal access token on the command line.
* `wp lifx check_token` - This checks to see if your LIFX person token authenicates correctly.
* `wp lifx toggle` - This will toggle the power state of all lights.
* `wp lifx power on` - This will turn all lights on.
* `wp lifx power off` - This will turn all lights off.
* `wp lifx toggle --selector=label:"I Love Lamp"` - This will toggle the state of a light with a label of "I Love Lamp".
* `wp lifx toggle --selector=id:d073d56e1d85` - This will toggle the state of a light with an id of `d073d56e1d85`