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
* `wp lifx check_token` - This checks to see if your LIFX person token authenticates correctly.
* `wp lifx list_lights` - This will list your the LIFX smart lights.
* `wp lifx toggle` - This will toggle the power state of all lights.
* `wp lifx power on` - This will turn all lights on.
* `wp lifx power off` - This will turn all lights off.
* `wp lifx toggle --selector=label:"I Love Lamp"` - This will toggle the state of a light with a label of "I Love Lamp".
* `wp lifx toggle --selector=id:d073d56e1d85` - This will toggle the state of a light with an id of `d073d56e1d85`
* `wp lifx colour_list` - This will list all the colour names you can use with our plugin.
* `wp lifx validate_colour "darkorchid"` - See if this is a valid colour and if it use output it's hex code.
* `wp lifx colour rebeccapurple` - This will set all your smart lights to `#663399`
* `wp lifx colour rebeccapurple --selector=label:"I Love Lamp" --fast=true` - Set the "I Love Lamp" to `#663399`
* `wp lifx colour random` - Set's all lights to a random colour.
* `wp lifx colour random --selector=label:"I Love Lamp"` - Set the "I Love Lamp" to a random colour.
* `wp lifx colour random --selector=label:"I Love Lamp" --zones=0-9` - Set the "I Love Lamp" to a random colour in zones 0-9.
* `wp lifx brightness 0.5` - Set all lights to 50% brightness.
* `wp lifx brightness 0.5 --zones=0-9` - Set the brightness to 50% in zones 0-9 for all lights that support zones.
* `wp lifx brightness 0.5 --zones=10-19|31-40` - Set the brightness to 50% in zones 0-9 and 31-40 for all lights that support zones.
* `wp lifx brightness 1.0 --fast=true` - Set all lights to 100% brightness and don't receive a response payload.
* `wp lifx brightness 1.0 --duration=5` - Set all lights to 100% brightness over 5 seconds.
* `wp lifx brightness 0.75 --selector=group:Bedroom` - Set all lights to 75% brightness in the Bedroom group.
* `wp lifx brightness 0.75 --selector=label:'I Love Lamp'` - Set all the device called I Love Lamp to 75% brightness.
* `wp lifx breathe rebeccapurple` - Use the breath effect with the colour `rebeccapurple`.
* `wp lifx breathe rebeccapurple --from_colour=deeppink --selector=group:Bedroom` - Use the breath effect with the colour `rebeccapurple`.
* `wp lifx flame` - Use the flame effect on any products that support it i.e. LIFX Tiles.
* `wp lifx flame --selector=label:"Tiles" --cycles=2 --period=2` - Use the flame effect.
* `wp lifx flame --selector=label:"Tiles" --cycles=2 --period=2 --power_on=false --fast=true` - Use the flame effect.
* `wp lifx pulse rebeccapurple` - Use the pulse effect with the colour `rebeccapurple`. 
* `wp lifx pulse deeppink --from_colour=darkblue --cycles=3` - Use the pulse effect with the colour `deeppink` and `darkblue` 3 times. 
* `wp lifx pulse deeppink --from_colour=darkblue --cycles=3 --period=5` - Use the pulse effect with the colour `deeppink` and `darkblue` 3 times over 5 seconds.
* `wp lifx pulse deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false` - Use the pulse effect with the colour `deeppink` and `rebeccapurple` 3 times over 5 seconds but only if the light is already on.
* `wp lifx pulse deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false --persist=true` - Use the pulse effect with the colour `deeppink` and `rebeccapurple` 3 times over 5 seconds but only if the light is already on and leave the light on the starting colour.
* `wp lifx move forward` - Perform a move effect on any lights that can do that effect.
* `wp lifx move forward --cycles=3 --period=5` - Perform a move effect on any lights that can do that effect, three times over five seconds.
* `wp lifx move backward --cycles=10 --period=10 --power_on=false` - Perform a move effect on any lights that can do that effect, ten times over 10 seconds but only if the light is already on.
* `wp lifx move backward --selector=group:"Music Room"` - Perform a move effect on any lights that can do that effect in the group "Music Room".
* `wp lifx move backward --selector=group:"Music Room" --fast=true` - Perform a move effect on any lights that can do that effect in the group "Music Room" but don't return a payload response so it's faster.
* `wp lifx get_multizones` - Determine if any lights on the network support multizones.
* `wp lifx get_multizones --selector=label:"Beam Me Up!"` - Determine if the "I Love Lamp" light supports multizone.