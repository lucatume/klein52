A Docker PHP LNMP stack with XDebug and Opcache

## Installation
* copy the whole directory over to your project file.
* launch the `setup` script to replace the `domain` placeholder in the relevant files and build the Docker images

## Usage
* to start up the stack navigate to the stack root and run the `up` script
* assuming the domain has been set to `myapp`, the application is now served at `myapp.localhost`

## XDebug
Xdebug is set to auto-start and call back the request machine on port `9001`.

## Application code
While the stack was born to suit my WordPress development needs it does not come with WordPress installed.  
As such the chore of downloading and installing an application in the `www` folder is yours.

## Nginx configuration files
Depending on the application you are running you might need different Nginx configuration files.  
The default ones are in the `build/nginx/conf.d` folder and the only configuration file will successfully manage a multi/single site WordPress installation.  
Replace with your configuration files and remember Google is your friend.
