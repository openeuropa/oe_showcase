# OpenEuropa showcase

[![Build Status](https://drone.fpfis.eu/api/badges/openeuropa/oe_showcase/status.svg?branch=master)](https://drone.fpfis.eu/openeuropa/oe_showcase)
[![Packagist](https://img.shields.io/packagist/v/openeuropa/oe_showcase.svg)](https://packagist.org/packages/openeuropa/oe_showcase)

Basic installation profile, all it does is:

1. Enable the bare minimum amount of core modules.
2. Setup `seven` as administrative theme.

## Rationale

This profile is aimed to provide developers with a series of examples on how the openeuropa library features are built and configured. Exposing that features, the building of new sites will be easy to get the necessary components to achieve every site's goals.

## Installation

The recommended way of installing the OpenEuropa Profile is via a [Composer-based workflow][1].

In the root of the project, run

```
$ composer install
```

Before setting up and installing the site make sure to customize default configuration values by copying `./runner.yml.dist`
to `./runner.yml` and override relevant properties.

To set up the project run:

```
$ ./vendor/bin/run drupal:site-setup
```

This will:

- Symlink the profile in `./build/profiles/custom/oe_showcase` so that it's available to the target site
- Setup Drush and Drupal's settings using values from `./runner.yml.dist`
- Setup Behat configuration file using values from `./runner.yml.dist`

After a successful setup install the site by running:

```
$ ./vendor/bin/run drupal:site-install
```

This will:

- Install the target site
- Enable development modules

### Using Docker Compose

The setup procedure described above can be sensitively simplified by using Docker Compose.

Requirements:

- [Docker][2]
- [Docker-compose][3]

Copy docker-compose.yml.dist into docker-compose.yml.

You can make any alterations you need for your local Docker setup. However, the defaults should be enough to set the project up.

Run:

```
$ docker-compose up -d
```

Then:

```
$ docker-compose exec web composer install
$ docker-compose exec web ./vendor/bin/run drupal:site-install
```

Your test site will be available at [http://localhost:8080/build](http://localhost:8080/build).

Run tests as follows:

```
$ docker-compose exec web ./vendor/bin/behat
```

#### Step debugging

To enable step debugging from the command line, pass the `XDEBUG_SESSION` environment variable with any value to
the container:

```bash
docker-compose exec -e XDEBUG_SESSION=1 web <your command>
```

Please note that, starting from XDebug 3, a connection error message will be outputted in the console if the variable is
set but your client is not listening for debugging connections. The error message will cause false negatives for PHPUnit
tests.

To initiate step debugging from the browser, set the correct cookie using a browser extension or a bookmarklet
like the ones generated at https://www.jetbrains.com/phpstorm/marklets/.

[1]: https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed
[2]: https://www.docker.com/get-docker
[3]: https://docs.docker.com/compose

