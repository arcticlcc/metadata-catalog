# Metadata Catalog

The Metadata Catalog provides access to project and product(dataset) _metadata_. While the metadata may be presented to the user in multple formats, the catalog utilizes the [mdJSON](http://www.adiwg.org/projects/#mdjson-schemas) format as a datastore. mdJSON records are parsed to provide information about projects and products. HTML and XML metadata may be produced from the mdJSON using the [ADIwg mdTranslator](http://www.adiwg.org/mdTranslator/about/). The [mdTools](http://mdtools.adiwg.org) application provides an interface for exploring mdJSON and related tools.

## Installation

### Requirements

-  PHP >=5.5.9
    - Composer
    - SQLite3 (for default config)
-  PostgreSQL >=9.4
-  Webserver (for production)

### Installing

The following steps assume you have PostgreSQL 9.4+ installed, and a database and user account created for use with the catalog. See the PostgreSQL [download page](http://www.postgresql.org/download/) for installation info.

The [Ubuntu community wiki](https://help.ubuntu.com/community/PostgreSQL) has info on creating databases and users. If you're on Windows try the [Bitnami WAPP Stack](https://bitnami.com/stack/wapp) for an all-in-one install. There's also [one for Linux](https://bitnami.com/stack/lapp).

From the command line:
 1.  Clone the repo `git clone https://github.com/arcticlcc/metadata-catalog.git`
 1. `cd metadata-catalog`
 1.  Install [Composer](https://getcomposer.org/download/), if needed. You can
 install in the project root, or in a location on your path (i.e. $HOME/bin)
 1. Run `composer install` or `composer.phar install`
 1. Edit the following files in the [config](config) directory:
    - Rename or copy the [db.example.yml](config/db.example.yml)  to `db.yml`. Add your PostgreSQL connection info to the `psql` entry.
    - Rename or copy the [whitelist.example.yml](config/whitelist.example.yml) to `whitelist.yml`.
    - Rename or copy the [aws.example.yml](config/aws.example.yml) to `aws.yml`.
 1. Create the schema in your database: `bin/console doctrine:schema:create`
 1. Type `bin/console assetic:dump` to build the development environment
 1. Type `composer run` and point your browser to http://localhost:8888
 1. You should have an empty catalog!
 1. Import some sample data: `bin/console metadata:import:dbal`

## API

The Catalog application is backed by a simple RESTful API. The API interface is described below using the following notation:

-  ***...*** : the web root for the catalog
-  **{required}** : a required string
-  **[optional]** : an optional string
-  **option1 | option2** : pipe(|) separated list of valid values

### GET

#### .../

Returns the homepage(HTML-only)

---

#### .../{entity}[.{format}]

Returns an array of entities in the desired format

-   _entity_ = [project | product]
-   _format_ = [**json**]

---

#### .../{entity}/[view]

Returns the entity homepage displaying a list of entities

-   _entity_ = [project | product]
-   Note: a request to _.../{entity}/_ will redirect to _.../{entity}/view_

---

#### .../{entity}/{uuid}[.{format}]

Returns a single entity corresponding to the supplied uuid, in the desired format

-   _entity_ = [project | product]
-   _uuid_ = a valid UUID
-   _format_ = [**json** | xml | html]

---

#### .../{entity}/{uuid}/[view]

Returns a web page for a single entity

-   _entity_ = [project | product]
-   _uuid_ = a valid UUID
-   Note: a request to _.../{entity}/{uuid}/_ will redirect to _.../{entity}/{uuid}/view_

---

#### .../{entity1}/{id}/{entity2}[.{format}][?short=true]

Returns an array of related entities in the desired format

-   _entity1_ = [project | product]
-   _id_ = a valid UUID for entity1
-   _entity2_ = [project | product]
-   _format_ = [**json**]
-   _short_ = boolean [**false** | true]: if true, will return an array of citations for entity2

---

#### .../{uuid}[.{format}]

Returns a single entity corresponding to the supplied uuid, in the desired format

-   _uuid_ = a valid UUID
-   _format_ = [**json** | xml | html]

---

#### .../{uuid}/[view]

Returns the entity homepage corresponding to the supplied uuid

-   _uuid_ = a valid UUID

### POST

#### .../sync/[type]

Triggers a sync event.

-   _type_ = one of:
     -  [s3] POST body must be a valid [AWS S3 SNS Event notification](http://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html)
