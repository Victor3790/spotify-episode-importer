Spotify podcast importer.

Project context:
This WordPress plugin was developed for a company that needed to import their podcast episodes to a WordPress based site. Their podcast was hosted on Spotify and they had a custom post type named “podcast” with a custom field to add the episode iframe in their WP installation; just one image would be used  as a feature image for all the podcasts and some parts of the description had to be modified and or eliminated.

Since there were over a hundred episodes it would be cumbersome to import all of them manually. The solution was to use the Spotify API to get the episode data and WordPress functions to create the content. Additionally Xpath was used to modify and eliminate some parts of the html description provided by the API. 

All the podcast episodes are created when the plugin is activated and a specific published date is generated to maintain the order of the episodes. 
