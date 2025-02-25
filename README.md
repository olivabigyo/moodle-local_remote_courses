MyZHAW courses web service
==========================

This local module provides a web service which returns a given user's courses based on the eduid.

# myCourses Web API

Exposes an API endpoint to query the moodle courses of a given user by edu-ID.

## Installation

This Plugin is installed in `local/mycoursesapi`.

## Setup

- Follow the instructions for adding an external service (`/admin/settings.php?section=webservicesoverview`)
- Specifically, add a service (e.g. "myCourses") with the function `local_mycoursesapi_get_courses_by_eduid` in `/admin/settings.php?section=externalservices`
