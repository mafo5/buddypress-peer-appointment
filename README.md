# Buddypress Peers Plugin

## TLDR

Plugin for [Buddypress](https://buddypress.org/) to create Meetings (Sessions) between two Users (Peers) bases on a [Skeleton Plugin for Buddypress](https://github.com/boonebgorges/buddypress-skeleton-component).

## Status

**discontinue**

*Reason:* We have distant ourself from Wordpress and therefor Buddypress.

## Requirements

* Wordpress 4.4 or greater
* Buddypress 1.9 or greater

## Usage

1. Simply copy this repository into your `wp-content/plugins` folder of your Wordpress installation.
2. Activate the Plugin in the Plugin View of Wordpress

## Idea and Purpose

We are creating a social platform for peer mentoring: [PEERS](https://ux-peers.com)

In our first attempt, we created a basic social platform with BuddyPress. On top of this, we looked into the available Plugins for scheduling appointments. All the available Plugins didn't cover our needs. So we decided to develop a Plugin ourself.

Our requirements for the Plugins have been:
a personal list of appointments with other users
an appointment needs to be unique regardless of its date
additional data to each appointment unique for each date
a page for each state of an appointment
"easy" scheduling of an appointment
definition of availability for each user

Our interpretation of "easy" scheduling was:

The first user visits the profile of another user and starts to create an appointment request. The first user selects 3 date suggestions from the merged list of the available date lists of both users. The first user also can add additional context, greeting or any text to the request. The second user gets the notification about the request and selects the suitable date from the 3 suggestions and optionally add additional text. Now the appointment is fixed and both users will get a notification about the appointment details and an additional notification when the appointment reaches its actual date. If no suggestion is suitable the second user can suggest other 3 dates. Now the process is the same as when the second user would have been the first one. 

This process shortens the needed pages for scheduling to one page. All other Plugins relay on a typical wizard approach with a lot more pages. In our case, the predefinitions like available time slots will be done once and the scheduling os more a selection. The usual Plugins doesn't provide this preset of data.

Unfortunately, the serverside rendering and the requirements for the persistence of PHP and WordPress made the UX awful. Maybe the extended usage of Ajax could make the experience smoother.

## Learnings / required Changes

If you like to develop this Plugin feel free to fork this repository.
Regarding the current knowledge gained by creating this Plugin you should consider the following changes:

### create builder structure

Because this Plugin uses JavaScript and CSS for the sake of development convenience you should create a `src` folder and add a builder. For example, the WordPress team themself uses a NodeJS builder structure in their test project. (see: `git://develop.git.wordpress.org/`)

It will enable the usage of SCSS or any other higher CSS framework for generating CSS. Also TypeScript or any other support of additional JavaScript Support.

You can generate artefacts which will be immutable and supports downgrade.

### add tests

Unfortunately, the skeleton Plugin lacks in tests. Therefore all development was done in try and error way. This "code and pray" attitude is highly unrecommended.

In the current status of knowledge of PHP and WordPress, there is no Plugin to easily enable testability to a WordPress Plugin. Please look into [PHPUnit](https://phpunit.de/), which provides the official testing framework for WordPress. Buddypress itself uses PHPUnit as well. (see: https://github.com/buddypress/BuddyPress)

### change folder structure

For me, as a non-PHP developer, the current plugin folder structure is a mess. Logical functions and features are spread over different files. This might be a typical issue with Buddypress, WordPress or even PHP itself. I highly recommend a look into feature orientated folder structure. This may give a clearer view for new and/or different developers.