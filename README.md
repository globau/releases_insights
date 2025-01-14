# Release Insights / Firefox Trains Dashboard

This is the code behind https://fx-trains.herokuapp.com/

This application gives the status of Firefox Destop Releases: when do we ship the next release, what are the upcoming milestones for our future Firefox releases, what are the main data points for our past releases.

There are different views and I also added a public JSON API, these endpoints are documented here:

https://fx-trains.herokuapp.com/about/

### Requirements:

The requirements are very simple, no Database, no heavy framework.

- Linux (should work fine on macOS and Windows too, but I didn't test it)

- PHP 8.1 with the `ext-mbstring`, `ext-intl`, `ext-curl`, `ext-dom` extensions

- [Composer](https://getcomposer.org/) to install dependencies

The application is set up to be deployed on Heroku with Apache but there is no need to install Apache for development work, the PHP built-in development server is fine for that purpose.

### Installation

1. Clone this repository
2. install dependencies: `composer install`
3. start PHP development server in a terminal either by launching the `start.sh` script or with this command:<br>
  `php -S localhost:8082 -t public/`

The website will be available at http://localhost:8082

If you want to set the site up with an Apache virtual host, make it point to the `public` folder and make sure that the `cache` folder is writable by Apache.

### Running with Docker

It's possible to use Docker to run in a containerised environment.

Build or update the image using:
```
docker build -t fx-trains .
```
and run it with
```
docker run --rm -p 8000:8000 fx-trains
```

The image is configured to listen on port 8000.

#### Dockerflow

[Dockerflow](https://github.com/mozilla-services/Dockerflow) is supported; with `version.json` optionally generated from build-time variables:

```
docker build . -t fx-trains \
  --build-arg source=https://github.com/pascalchevrel/releases_insights \
  --build-arg version= \
  --build-arg commit=$( git rev-parse HEAD ) \
  --build-arg build=
```

## Testing and CI

We use [Pest](https://pestphp.com/Pest) for unit testing and we have CI via Github Actions to ensure all tests are passing.

You can also run tests with the `start.sh -tests` command, this will run `pest`.

If you want to contribute a patch to an existing class, please make sure that unit tests pass. If there is no unit test yet for the method you are modifying, please add one thanks.
