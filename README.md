# Maxmind's GeoIP database update server clone

[![License: EUPL 1.2](https://raw.githubusercontent.com/eClip-/EUPL-badge/master/eupl_1.2.svg)](https://www.gnu.org/licenses/gpl-3.0)

This project goal is to permit easy local redistribution of Maxmind's GeoIP local databases with the same path and protocol as `updates.maxmind.com`

So you can use Maxmind's GeoIP updaters like our [maxmind-geoip-client-update]() by changing host configuration from `updates.maxmind.com` to your own.

[Maxmind rate limits](https://support.maxmind.com/hc/en-us/articles/4408216129947-Download-and-Update-Databases) :
> Every account is limited to 2,000 total direct downloads in a 24 hour period. If you have to distribute your databases across multiple servers, it is advisable that you download databases to a local repository on your network, and distribute them to other servers from there.

## Features

- All https://updates.maxmind.com/geoip/databases/%s/update endpoint features (HEAD, GET, md5sum etc.)

### With some more :

- user/pass management can be completly disabled,
- You can filter per client IP (CIDR) authorization instead of user/pass,

## Requirements

- PHP >= 8.1
- max_memory ~ 150M 

## Install

- Install external dependencies : `composer install`
- Configure your web server 
    - to make `/geoip/databases/` path point into the `web-public` directory
    - to redirect all requests under `/geoip/databases/` path to index.php

### Apache 2 configuration 

Add to your virtualhost : 
```
    Define maxmindwebpath /path/to/project/maxmind-geoip-update-server/web-public
    Alias "/geoip/databases" "${maxmindwebpath}"
    <Directory "${maxmindwebpath}">
        AllowOverride All
        require all granted
    </Directory>
    Undefine maxmindwebpath
```

# License

Licensed under the EUPL, Version 1.2 or – as soon they will be approved by
the European Commission - subsequent versions of the EUPL (the "Licence");
You may not use this work except in compliance with the Licence.
You may obtain a copy of the Licence at:

https://joinup.ec.europa.eu/software/page/eupl

Unless required by applicable law or agreed to in writing, software
distributed under the Licence is distributed on an "AS IS" basis,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the Licence for the specific language governing permissions and
limitations under the Licence.
