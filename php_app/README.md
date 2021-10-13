backend
-------

backend is the core of the Stampede system, it's basically a monolith (although
there are some other bits and bobs floating around). 

<!-- vim-markdown-toc GFM -->

- [Development](#development)
  - [Prerequisites](#prerequisites)
    - [PHP](#php)
    - [Composer](#composer)
    - [Docker](#docker)
    - [Docker login for GitHub package registry](#docker-login-for-github-package-registry)
  - [Running](#running)
    - [Backing Services](#backing-services)
  - [Tips](#tips)
- [Documentation](#documentation)
- [Testing](#testing)
- [Architecture](#architecture)

<!-- vim-markdown-toc -->

## Development
### Prerequisites

#### PHP
backend is written in [PHP][php] (at a minimum version of 7.3) which on the
instructions for installing can be found [here][php-install-no-mac] or on macOS
you can use [homebrew][homebrew] to install php by running.

```shell
brew install php@7.4
```

#### Composer
[Composer][composer] is the package manager used to manage the dependencies of
the backend app, it's a bit like npm, and instructions for installation can be
found [here][install-composer].

#### Docker 
While it is possible to run backend locally without the aid of [docker][docker], it is
much easier to run it with docker ([allbiet slower on macOS][docker-mac-performance])
you'll need to install docker first, for which instructions can be found [here][installing-docker].

#### Docker login for GitHub package registry

In order to pull our base/development docker images, you'll need to have given
the docker daemon a login to access GitHub. To do this you'll need to issue a
GitHub personall access token, instructions for doing this can be found [here][github-token].

You'll need to make sure the token that you issue has at least the following
permissions:
- `write:packages`
- `read:packages`

You can find out how to log into the docker daemon [here][docker-github-login]
or if you don't fancy reading all that you can just run this command after
you've copied your token (on macOS at least).

```shell
pbpaste | docker login https://docker.pkg.github.com -u blackbx --password-stdin
```

### Running
Once you've cloned the repo:
```shell
git clone git@github.com:blackbx/backend.git
```
You'll need to run composer to install all of the dependencies:
```shell
composer install
```

Once this is done, simply run:
```shell
docker-compose up
```

And the entire backend stack will start up.

#### Backing Services
`docker-compose.yml` defines several backing services that are reqired to make
the backend work, these are:

| Service       | Container  | Local access                               | Config      | Docs                 |
|---------------|------------|--------------------------------------------|-------------|----------------------|
| SNS/SQS       | `aws`      | `aws --endpoint-url http://localhost:4100` | `goaws.yml` | [GitHub][goaws]      |
| SMTP          | `email`    | `http://localhost:8025`                    | N/A         | [GitHub][mailhog]    |
| MySQL (DB)    | `database` | `mysql --protocol TCP -u root`             | N/A         | [GitHub][backend-db] |
| Redis (Cache) | `redis`    | `redis-cli`                                | N/A         | [Docker Hub][redis]  | 

### Tips
- Run `docker-compose up aws email database redis` to get just the backing
services, you can then run the app on your local machine using `php -S 127.0.0.1:8080 -t public public/index.php`
this makes things a lot faster on macOS as you no longer have to deal with
docker for mac's filesystem [slowness][docker-mac-performance]
- Use the `email` container's web UI to veiw emails being sent by the
application (very useful)
- The database contains a few users attached to a few different pieces of sample
data, it is by no means complete, but additional data can be added for all to
use [here][db-sample-data]. So if you see something missing, please add it for
all to use! You can see what user are available to use by running 
`mysql --protocol TCP -u root -e "SELECT email FROM core.oauth_users;"` you can
use any of these users email addresses as [Bearer Authentication tokens][bearer-authentication]
in order to access any APIs provided by backend locally.
- If you end up messing up the database, you can run `docker system prune -a` to
remove all data locally, and re-run `docker-compose up` to pull everything
again. Note: ensure you've killed all running containers first (`docker kill $(docker ps -q)`)
- If the change you've made requires changes to the database schema, be careful
to update references to the database docker image in `.circleci/config.yml`

## Documentation
There isn't any documentation on the routes, but you should be able to figure it
out by looking in the `app/routes` directory (appologies for this).

## Testing
Quite a lot of the code is heavily tied to doing actions in the database, and as
such, the tests require an up to data [database][backend-db] running on
`127.0.0.1:3307`, bit of a gotcha this. 

You can run a database for running tests locally by running:
```shell
docker run -p3307:3306 docker.pkg.github.com/blackbx/backend-db/db:latest
```

You will then be able to run the tests by:
```shell
php vendor/phpunit/phpunit/phpunit --no-configuration tests
```
They'll be a bit of rubbish output, try to ignore it (or fix it), you'll feel
better about life that way.

## Architecture
It's a bit of a mess, we're trying to condense most new code into the
`app/src/Package` dir, in domain focused namespaces, with separation between
different layers there, it's not going well...

There's also the massive `app/dependencies.php` which is there because the DI
framework hasn't been setup properly, again annoying, but not crucial to fix.

[php]: https://www.php.net/manual/en/getting-started.php
[php-install-no-mac]: https://www.php.net/manual/en/install.php
[homebrew]: https://brew.sh/
[composer]: https://getcomposer.org/doc/00-intro.md
[install-composer]: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos
[docker]: https://www.docker.com/
[installing-docker]: https://docs.docker.com/engine/install/
[docker-mac-performance]: https://docs.docker.com/docker-for-mac/osxfs/#performance-issues-solutions-and-roadmap
[github-token]: https://docs.github.com/en/github/authenticating-to-github/creating-a-personal-access-token
[docker-github-login]: https://docs.github.com/en/packages/using-github-packages-with-your-projects-ecosystem/configuring-docker-for-use-with-github-packages
[goaws]: https://github.com/p4tin/goaws
[mailhog]: https://github.com/mailhog/MailHog
[backend-db]: https://github.com/blackbx/backend-db
[redis]: https://hub.docker.com/_/redis
[db-sample-data]: https://github.com/blackbx/backend-db/tree/master/sample-data/core
[bearer-authentication]: https://oauth.net/2/bearer-tokens/
