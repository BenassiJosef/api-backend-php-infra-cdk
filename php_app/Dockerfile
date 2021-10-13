FROM 354378566817.dkr.ecr.eu-west-1.amazonaws.com/stampede/docker-php-dev:latest AS Builder

WORKDIR /workdir 
COPY . .
RUN composer install -n --prefer-dist \
	&& vendor/doctrine/orm/bin/doctrine orm:generate-proxies cache/proxies

RUN chgrp -R www-data cache/proxies \
	&& chmod -R ug+rw cache/proxies

FROM 354378566817.dkr.ecr.eu-west-1.amazonaws.com/stampede/docker-php-prod:latest

ARG APP_BUILD=test
ENV APP_VERSION=$APP_BUILD

COPY --from=Builder /workdir /var/www/html

