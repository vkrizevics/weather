# Weather API

## Preface

I have chosen for the task Symfony 5.4 framework because Symfony 5 was mentioned both in job advertisement and job interview by your developer as a framework used by your company for many projects and the version 5.4 was the long-term support version. I have chosen nginx to serve the solution for its performance, pcov extension for using with phpUnit for generating code coverage reports for its performance as well.

By default pcov is disabled to speed-up the solution even more. It is enabled before launching phpUnit in tests.sh

I have chosen MariaDB because it was mentioned in the job advertisement and I wanted to show my hands-on experience with it.

For database demonstration, I have chosen phpMyAdmin. I synchronize information on weather stations with my database before the first launch of any service and once an hour to provide quick updates if the data changes at the official open data source: https://data.gov.lv/dati/lv/dataset/hidrometeorologiskie-noverojumi/resource/c32c7afd-0d05-44fd-8b24-1de85b4bf11d

All the software is placed within a single docker image because launch using `docker run` was requested in the task description: https://github.com/saleniex/homework3 that excludes usage of several images and `docker-compose`

## Installation

Clone this repository into an empty directory.

Execute in the project root folder:
`docker build -t weather .`

If `weather` container is not available, use another container name instead.

Free port 80.

After the image will be built (this can take 5-10 minutes) execute from the project root:
`docker run -p 80:80 --name weather weather`

You are ready to test the application

## Testing

Application is available at http://localhost/api/stations

To launch unit tests and get code coverage reports enter the Docker container built and execute:
`./tests.sh`

The code coverage will be available at http://localhost/coverage

OPEN API specification of the service is available at http://localhost/openapi.json Its version is 3.0.0 to suit Swagger and Postman requirements.

You can import openapi.json in Postman and use the Postman generated requests to access the service implemented.

http://localhost/api/stations provides station list and
http://localhost/api/stations/STATION_ID provides detailed info on the weather station with the STATION_ID supplied if such a weather station exists, e.g. http://localhost/api/stations/SIGULDA

You will need to provide bearer token explicitly in Postman requests. Its value is `MY_SECRET_TOKEN`

You can access the database inside the container using phpMyAdmin at http://localhost/phpmyadmin using user `symfony` and password `symfony`