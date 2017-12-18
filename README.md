# Viper

Viper is a lightweight universal PHP MVC framework with its own models and templating engine

Starting developing on Viper is as easy as typing in the console:
    
    composer create-project arsengoian/viper PROJECT_NAME dev-master
    
and configuring DB credentials

## Configuration

The web application is configured by an array of YAML files at `config/` directory. They contain a list of instructions on every setting. `local.yaml` contains configuration related to the current server environment while `global.config` sets up overall application settings.

Settings may be accessed at any time using `Config` class:
```php
if (Config::get('DEBUG'))     // From local.yaml -> local.yaml and global.yaml don't need a preffix
    return Config::get('Bots.VERIFY_TOKEN')    // From bots.yaml
```
    
## Features    

### YAML routing
Requests are routed automatically, for example a GET request to `http://website.com/chairs` will invoke the `get` method of `ChairsController`

Routing example:

```yaml
welcome: DefaultController         # directs DOMAIN/welcome -> DefaultController::get($http_params)
hello: DefaultController.main      # directs DOMAIN/hello   -> DefaultController::main($http_params)
products:                          # 1-level hierarchies are also supported
    parse: DefaultController
    count: DefaultController.main
```        
### Easy-to-use controllers and built-in validation
A controller function implementing a POST request:
```php
public function post (...$args): ?Viewable
{
    $v = new Required($this -> params());                                 // Create validator
    $v -> email('email');                                                 // Validate "email" field

    Client::registerWithImages($this -> params(), $this -> files());      // Create new model in the database
    return new RedirectView('/');                                         // Redirect to main page
}
```

### Auto-completing models
Viper deals with databases automatically, miminizing the need to edit SQL manually. A YAML setup like this:
```yaml
 allowOverwrite: true

 fields:
  fname: VARCHAR(255) NOT NULL
  lname: VARCHAR(255) NOT NULL
  email: VARCHAR(255) NOT NULL
  age: INT NOT NULL
  img: TEXT NOT NULL
```
will create the needed database structure if needed and update it along with the file if needed.

### Filters
Will be applied to all routes before any controller actions:
```php
class Application extends \Viper\Core\Application
{
    /**
     * Defines the list of filters to be run
     * before any controller actions
     * @return FilterCollection
     */
    protected function declareFilters (): FilterCollection
    {
        return new FilterCollection([
            LocalizationFilter::class,
            Authorization::class
        ]);
    }
}
```

### Logging and utilities 
Viper supports built-in logging and a collection of useful utilities, including caching, advanced string handling etc.

### Caching capabilities
All views and parsed .yaml files are recovered, if possible, from cache

## Features in early development
* Background processes and services
* Windows background tasks
* Viper templating engine
* Console commands
* Full Mysql support
* Other SQL dialects support
* Numerous feature and structure improvements

## Contributing and development
Since the framework is on an early stage of development, it may feature structural irreversable changes without reverse compatibility. 

Please contribute to the project if you also feel passionate about making PHP development more elegant and intuitive =)
